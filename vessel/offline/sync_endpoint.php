<?php
/**
 * Sync Endpoint for Vessel Logger Offline Functionality
 * Handles background sync requests from Service Worker
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_functions.php';
require_once __DIR__ . '/sync_manager.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        throw new Exception('Invalid request data');
    }
    
    $action = $input['action'];
    $response = ['success' => false];
    
    switch ($action) {
        case 'sync_pending_data':
            $response = handleSyncPendingData($input);
            break;
            
        case 'check_sync_status':
            $response = handleCheckSyncStatus($input);
            break;
            
        case 'force_sync':
            $response = handleForceSync($input);
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleSyncPendingData($input) {
    global $conn;
    
    // Get vessel and user from session or input
    $vessel_id = $_SESSION['active_vessel_id'] ?? $input['vessel_id'] ?? null;
    $user_id = $_SESSION['user_id'] ?? $input['user_id'] ?? null;
    
    if (!$vessel_id || !$user_id) {
        throw new Exception('Vessel ID and User ID required for sync');
    }
    
    // Initialize sync manager
    $sqlite_path = "data/vessel_{$vessel_id}.db";
    
    if (!file_exists($sqlite_path)) {
        throw new Exception('Offline database not found');
    }
    
    $sync_manager = new OfflineSyncManager($conn, $sqlite_path, $vessel_id, $user_id);
    
    // Perform full sync
    $sync_result = $sync_manager->performFullSync();
    
    return [
        'success' => $sync_result['success'],
        'pushed' => $sync_result['pushed'] ?? 0,
        'pulled' => $sync_result['pulled'] ?? 0,
        'message' => $sync_result['success'] ? 'Sync completed successfully' : $sync_result['error'],
        'log' => $sync_result['log'] ?? []
    ];
}

function handleCheckSyncStatus($input) {
    global $conn;
    
    $vessel_id = $_SESSION['active_vessel_id'] ?? $input['vessel_id'] ?? null;
    $user_id = $_SESSION['user_id'] ?? $input['user_id'] ?? null;
    
    if (!$vessel_id || !$user_id) {
        throw new Exception('Vessel ID and User ID required');
    }
    
    $sqlite_path = "data/vessel_{$vessel_id}.db";
    
    if (!file_exists($sqlite_path)) {
        return [
            'success' => true,
            'status' => 'no_database',
            'pending_changes' => 0,
            'last_sync' => null
        ];
    }
    
    $sync_manager = new OfflineSyncManager($conn, $sqlite_path, $vessel_id, $user_id);
    $status = $sync_manager->getOfflineStatus();
    
    return [
        'success' => true,
        'status' => $status['status'],
        'pending_changes' => $status['pending_changes'],
        'last_sync' => $status['last_sync']
    ];
}

function handleForceSync($input) {
    // Same as sync_pending_data but with higher priority
    return handleSyncPendingData($input);
}

// Utility function to check if online
function isOnline() {
    $connected = @fopen("http://www.google.com:80/", "r");
    if ($connected) {
        fclose($connected);
        return true;
    }
    return false;
}
?>
