<?php
/**
 * Sync API Endpoint for Offline Data Synchronization
 * Handles data synchronization between client and server
 */

// Include company configuration and authentication
require_once '../config.php';
require_once '../includes/auth.php';

// Require authentication for API access
requireAuth();

// Set JSON content type
header('Content-Type: application/json');

// Handle CORS for API requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Get request data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (!isset($data['type']) || !isset($data['action'])) {
        throw new Exception('Missing required fields: type and action');
    }
    
    $type = $data['type'];
    $action = strtoupper($data['action']);
    $payload = $data['data'] ?? [];
    $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
    
    // Get current user
    $current_user = getCurrentUser();
    
    // Route to appropriate handler based on type
    switch ($type) {
        case 'vessels':
            $result = handleVesselSync($action, $payload, $current_user);
            break;
            
        case 'engines':
            $result = handleEngineSync($action, $payload, $current_user);
            break;
            
        case 'logs':
            $result = handleLogSync($action, $payload, $current_user);
            break;
            
        case 'user_changes':
            $result = handleUserChangeSync($action, $payload, $current_user);
            break;
            
        default:
            throw new Exception("Unknown sync type: $type");
    }
    
    // Log successful sync
    logSyncOperation($type, $action, $current_user['id'], true);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'type' => $type,
        'action' => $action,
        'result' => $result,
        'server_timestamp' => date('Y-m-d H:i:s'),
        'sync_id' => generateSyncId()
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Sync API Error: " . $e->getMessage());
    
    if (isset($type) && isset($action) && isset($current_user)) {
        logSyncOperation($type, $action, $current_user['id'], false, $e->getMessage());
    }
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Handle vessel synchronization
 */
function handleVesselSync($action, $data, $user) {
    global $conn;
    
    switch ($action) {
        case 'CREATE':
            return createVessel($data, $user);
            
        case 'UPDATE':
            return updateVessel($data, $user);
            
        case 'DELETE':
            return deleteVessel($data['id'], $user);
            
        case 'GET':
            return getVessels($user);
            
        default:
            throw new Exception("Unknown vessel action: $action");
    }
}

/**
 * Handle engine synchronization
 */
function handleEngineSync($action, $data, $user) {
    switch ($action) {
        case 'CREATE':
            return createEngine($data, $user);
            
        case 'UPDATE':
            return updateEngine($data, $user);
            
        case 'DELETE':
            return deleteEngine($data['id'], $user);
            
        case 'GET':
            return getEngines($data['vessel_id'] ?? null, $user);
            
        default:
            throw new Exception("Unknown engine action: $action");
    }
}

/**
 * Handle log synchronization
 */
function handleLogSync($action, $data, $user) {
    switch ($action) {
        case 'CREATE':
            return createLog($data, $user);
            
        case 'UPDATE':
            return updateLog($data, $user);
            
        case 'DELETE':
            return deleteLog($data['id'], $user);
            
        case 'GET':
            return getLogs($data['vessel_id'] ?? null, $user);
            
        default:
            throw new Exception("Unknown log action: $action");
    }
}

/**
 * Handle user changes synchronization
 */
function handleUserChangeSync($action, $data, $user) {
    switch ($action) {
        case 'UPDATE_PROFILE':
            return updateUserProfile($data, $user);
            
        case 'CHANGE_PASSWORD':
            return changeUserPassword($data, $user);
            
        default:
            throw new Exception("Unknown user change action: $action");
    }
}

/**
 * Vessel CRUD Operations
 */
function createVessel($data, $user) {
    global $conn;
    
    // Validate required fields
    $required_fields = ['name', 'type'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Check if vessel already exists (by temporary ID or name)
    if (isset($data['id']) && strpos($data['id'], 'temp_') === 0) {
        // This is a temporary ID from offline creation
        $stmt = $conn->prepare("
            SELECT id FROM vessels 
            WHERE name = ? AND company_id = ? AND created_by = ?
        ");
        $stmt->bind_param("sii", $data['name'], $user['company_id'], $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            return ['id' => $existing['id'], 'action' => 'already_exists'];
        }
    }
    
    // Insert new vessel
    $stmt = $conn->prepare("
        INSERT INTO vessels (name, type, description, specifications, company_id, created_by, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $description = $data['description'] ?? '';
    $specifications = isset($data['specifications']) ? json_encode($data['specifications']) : '{}';
    
    $stmt->bind_param("ssssii", 
        $data['name'], 
        $data['type'], 
        $description, 
        $specifications, 
        $user['company_id'], 
        $user['id']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create vessel: " . $stmt->error);
    }
    
    $vessel_id = $conn->insert_id;
    
    return [
        'id' => $vessel_id,
        'action' => 'created',
        'temp_id' => $data['id'] ?? null
    ];
}

function updateVessel($data, $user) {
    global $conn;
    
    if (!isset($data['id'])) {
        throw new Exception("Vessel ID is required for update");
    }
    
    // Verify ownership
    $stmt = $conn->prepare("
        SELECT id FROM vessels 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->bind_param("ii", $data['id'], $user['company_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Vessel not found or access denied");
    }
    
    // Build update query dynamically
    $update_fields = [];
    $params = [];
    $types = '';
    
    $allowed_fields = ['name', 'type', 'description', 'specifications'];
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $update_fields[] = "$field = ?";
            if ($field === 'specifications') {
                $params[] = json_encode($data[$field]);
            } else {
                $params[] = $data[$field];
            }
            $types .= 's';
        }
    }
    
    if (empty($update_fields)) {
        throw new Exception("No valid fields to update");
    }
    
    $update_fields[] = "updated_at = NOW()";
    $params[] = $data['id'];
    $types .= 'i';
    
    $sql = "UPDATE vessels SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update vessel: " . $stmt->error);
    }
    
    return [
        'id' => $data['id'],
        'action' => 'updated',
        'affected_rows' => $stmt->affected_rows
    ];
}

function deleteVessel($vessel_id, $user) {
    global $conn;
    
    // Verify ownership
    $stmt = $conn->prepare("
        SELECT id FROM vessels 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->bind_param("ii", $vessel_id, $user['company_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Vessel not found or access denied");
    }
    
    // Delete vessel (this should cascade to related records)
    $stmt = $conn->prepare("DELETE FROM vessels WHERE id = ?");
    $stmt->bind_param("i", $vessel_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete vessel: " . $stmt->error);
    }
    
    return [
        'id' => $vessel_id,
        'action' => 'deleted',
        'affected_rows' => $stmt->affected_rows
    ];
}

function getVessels($user) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT id, name, type, description, specifications, created_at, updated_at
        FROM vessels 
        WHERE company_id = ?
        ORDER BY name
    ");
    $stmt->bind_param("i", $user['company_id']);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $vessels = [];
    
    while ($row = $result->fetch_assoc()) {
        $row['specifications'] = json_decode($row['specifications'], true);
        $vessels[] = $row;
    }
    
    return [
        'vessels' => $vessels,
        'count' => count($vessels)
    ];
}

/**
 * Log CRUD Operations (simplified for example)
 */
function createLog($data, $user) {
    global $conn;
    
    // Validate required fields
    $required_fields = ['vessel_id', 'engine_id', 'timestamp'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Verify vessel ownership
    $stmt = $conn->prepare("
        SELECT id FROM vessels 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->bind_param("ii", $data['vessel_id'], $user['company_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Vessel not found or access denied");
    }
    
    // Insert log entry
    $stmt = $conn->prepare("
        INSERT INTO logs (vessel_id, engine_id, timestamp, temperature, pressure, hours, notes, created_by, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("iisddsi", 
        $data['vessel_id'],
        $data['engine_id'],
        $data['timestamp'],
        $data['temperature'] ?? null,
        $data['pressure'] ?? null,
        $data['hours'] ?? null,
        $data['notes'] ?? '',
        $user['id']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create log: " . $stmt->error);
    }
    
    return [
        'id' => $conn->insert_id,
        'action' => 'created',
        'temp_id' => $data['id'] ?? null
    ];
}

/**
 * Utility functions
 */
function logSyncOperation($type, $action, $user_id, $success, $error = null) {
    global $conn;
    
    $stmt = $conn->prepare("
        INSERT INTO sync_log (type, action, user_id, success, error_message, timestamp) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("ssiss", $type, $action, $user_id, $success, $error);
    $stmt->execute();
}

function generateSyncId() {
    return uniqid('sync_', true);
}

/**
 * Additional helper functions for engines, user changes, etc.
 * (Simplified for brevity - implement as needed)
 */
function createEngine($data, $user) {
    // Implementation similar to createVessel
    return ['action' => 'not_implemented'];
}

function updateEngine($data, $user) {
    return ['action' => 'not_implemented'];
}

function deleteEngine($id, $user) {
    return ['action' => 'not_implemented'];
}

function getEngines($vessel_id, $user) {
    return ['engines' => [], 'count' => 0];
}

function updateLog($data, $user) {
    return ['action' => 'not_implemented'];
}

function deleteLog($id, $user) {
    return ['action' => 'not_implemented'];
}

function getLogs($vessel_id, $user) {
    return ['logs' => [], 'count' => 0];
}

function updateUserProfile($data, $user) {
    return ['action' => 'not_implemented'];
}

function changeUserPassword($data, $user) {
    return ['action' => 'not_implemented'];
}
?>
