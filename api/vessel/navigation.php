<?php
/**
 * Vessel Navigation API Endpoint
 * Handles navigation data (Destination/ETA) from vessel logger devices
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
    if (!isset($input['vessel_name']) || !isset($input['navigation_data']) || !isset($input['action'])) {
        throw new Exception('Missing required fields: vessel_name, navigation_data, action');
    }
    
    $vessel_name = trim($input['vessel_name']);
    $hin = $input['hin'] ?? null;
    $vessel_id = $input['vessel_id'] ?? null;
    $navigation_data = $input['navigation_data'];
    $action = $input['action'];
    
    if ($action === 'add_navigation') {
        // Find or create vessel record
        $vessel_db_id = getOrCreateVessel($conn, $vessel_name, $hin, $vessel_id);
        
        // Insert or update navigation data
        // Check if we have existing navigation data for this vessel
        $check_stmt = $conn->prepare("SELECT id FROM navigation_data WHERE vessel_id = ?");
        $check_stmt->bind_param("i", $vessel_db_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $existing = $result->fetch_assoc();
        $check_stmt->close();
        
        if ($existing) {
            // Update existing record
            $stmt = $conn->prepare("
                UPDATE navigation_data SET 
                    vessel_name = ?,
                    destination = ?, 
                    eta = ?, 
                    vessel_timestamp = ?,
                    updated_at = NOW()
                WHERE vessel_id = ?
            ");
            
            $vessel_timestamp = $navigation_data['updated_at'] ?? date('Y-m-d H:i:s');
            
            $stmt->bind_param("ssssi", 
                $vessel_name,
                $navigation_data['destination'] ?? '',
                $navigation_data['eta'] ?? '',
                $vessel_timestamp,
                $vessel_db_id
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Navigation data updated successfully',
                    'vessel_id' => $vessel_db_id,
                    'action' => 'updated'
                ]);
            } else {
                throw new Exception('Failed to update navigation data: ' . $stmt->error);
            }
            
        } else {
            // Insert new record
            $stmt = $conn->prepare("
                INSERT INTO navigation_data (
                    vessel_id,
                    vessel_name, 
                    destination, 
                    eta, 
                    vessel_timestamp,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $vessel_timestamp = $navigation_data['updated_at'] ?? date('Y-m-d H:i:s');
            
            $stmt->bind_param("issss", 
                $vessel_db_id,
                $vessel_name,
                $navigation_data['destination'] ?? '',
                $navigation_data['eta'] ?? '',
                $vessel_timestamp
            );
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Navigation data added successfully',
                    'vessel_id' => $vessel_db_id,
                    'navigation_id' => $stmt->insert_id,
                    'action' => 'created'
                ]);
            } else {
                throw new Exception('Failed to insert navigation data: ' . $stmt->error);
            }
        }
        
        $stmt->close();
        
    } else {
        throw new Exception('Invalid action: ' . $action);
    }
    
} catch (Exception $e) {
    error_log("Vessel Navigation API Error: " . $e->getMessage());
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
