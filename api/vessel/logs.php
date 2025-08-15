<?php
/**
 * Vessel Logs API Endpoint
 * Handles vessel log entries from vessel logger devices
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../../config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (!isset($input['vessel_name']) || !isset($input['log_data']) || !isset($input['action'])) {
        throw new Exception('Missing required fields: vessel_name, log_data, action');
    }
    
    $vessel_name = trim($input['vessel_name']);
    $hin = $input['hin'] ?? null;
    $vessel_id = $input['vessel_id'] ?? null;
    $log_data = $input['log_data'];
    $action = $input['action'];
    
    if ($action === 'add_log') {
        // Validate log data structure
        if (!isset($log_data['log_entry']) || !isset($log_data['logged_by'])) {
            throw new Exception('Invalid log data: missing log_entry or logged_by');
        }
        
        // Find or create vessel record
        $vessel_db_id = getOrCreateVessel($conn, $vessel_name, $hin, $vessel_id);
        
        // Insert log entry with vessel identification
        $stmt = $conn->prepare("
            INSERT INTO vessel_logs (
                vessel_id,
                vessel_name, 
                log_entry, 
                logged_by, 
                vessel_timestamp,
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $vessel_timestamp = $log_data['created_at'] ?? date('Y-m-d H:i:s');
        
        $stmt->bind_param("issss", 
            $vessel_db_id,
            $vessel_name,
            $log_data['log_entry'],
            $log_data['logged_by'],
            $vessel_timestamp
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Log entry added successfully',
                'vessel_id' => $vessel_db_id,
                'main_log_id' => $stmt->insert_id
            ]);
        } else {
            throw new Exception('Failed to insert log entry: ' . $stmt->error);
        }
        
        $stmt->close();
        
    } else {
        throw new Exception('Invalid action: ' . $action);
    }
    
} catch (Exception $e) {
    error_log("Vessel Logs API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Get or create vessel record in main database
 * Uses vessel_name as primary identifier with HIN as secondary
 */
function getOrCreateVessel($conn, $vessel_name, $hin = null, $external_vessel_id = null) {
    // First try to find by vessel_name (primary identifier)
    $stmt = $conn->prepare("SELECT id FROM vessels WHERE vessel_name = ?");
    $stmt->bind_param("s", $vessel_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $vessel = $result->fetch_assoc();
    $stmt->close();
    
    if ($vessel) {
        return $vessel['id'];
    }
    
    // If not found, create new vessel record
    $stmt = $conn->prepare("
        INSERT INTO vessels (
            vessel_name, 
            hin, 
            external_vessel_id,
            status,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, 'active', NOW(), NOW())
    ");
    
    $stmt->bind_param("sss", $vessel_name, $hin, $external_vessel_id);
    
    if ($stmt->execute()) {
        $new_id = $stmt->insert_id;
        $stmt->close();
        return $new_id;
    } else {
        $stmt->close();
        throw new Exception('Failed to create vessel record');
    }
}

$conn->close();
?>
