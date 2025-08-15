<?php
/**
 * Vessel Data API for Office Dashboard
 * Provides vessel data for office viewing
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database configuration
require_once 'config.php';

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_vessels':
            getVessels($conn);
            break;
            
        case 'get_vessel':
            $vesselId = $_GET['id'] ?? '';
            if (!$vesselId) {
                throw new Exception('Vessel ID required');
            }
            getVessel($conn, $vesselId);
            break;
            
        case 'get_navigation':
            $vesselId = $_GET['vessel_id'] ?? '';
            if (!$vesselId) {
                throw new Exception('Vessel ID required');
            }
            getNavigation($conn, $vesselId);
            break;
            
        case 'get_logs':
            $vesselId = $_GET['vessel_id'] ?? '';
            if (!$vesselId) {
                throw new Exception('Vessel ID required');
            }
            getLogs($conn, $vesselId);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getVessels($conn) {
    $stmt = $conn->prepare("SELECT id, vessel_name, hin, status, created_at, updated_at FROM vessels ORDER BY vessel_name");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $vessels = [];
    while ($row = $result->fetch_assoc()) {
        $vessels[] = $row;
    }
    
    echo json_encode(['success' => true, 'vessels' => $vessels]);
    $stmt->close();
}

function getVessel($conn, $vesselId) {
    $stmt = $conn->prepare("SELECT * FROM vessels WHERE id = ?");
    $stmt->bind_param("i", $vesselId);
    $stmt->execute();
    $result = $stmt->get_result();
    $vessel = $result->fetch_assoc();
    
    if (!$vessel) {
        throw new Exception('Vessel not found');
    }
    
    echo json_encode(['success' => true, 'vessel' => $vessel]);
    $stmt->close();
}

function getNavigation($conn, $vesselId) {
    $stmt = $conn->prepare("
        SELECT * FROM navigation_data 
        WHERE vessel_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $vesselId);
    $stmt->execute();
    $result = $stmt->get_result();
    $navigation = $result->fetch_assoc();
    
    echo json_encode(['success' => true, 'navigation' => $navigation]);
    $stmt->close();
}

function getLogs($conn, $vesselId) {
    $limit = $_GET['limit'] ?? 50;
    
    $stmt = $conn->prepare("
        SELECT * FROM vessel_logs 
        WHERE vessel_id = ? 
        ORDER BY vessel_timestamp DESC 
        LIMIT ?
    ");
    $stmt->bind_param("ii", $vesselId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    echo json_encode(['success' => true, 'logs' => $logs]);
    $stmt->close();
}

$conn->close();
?>
