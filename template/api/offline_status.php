<?php
/**
 * Offline Status API Endpoint
 * Provides connection testing and server status for offline functionality
 */

// Include company configuration and authentication
require_once '../config.php';
require_once '../includes/auth.php';

// Set JSON content type
header('Content-Type: application/json');

// Handle CORS for API requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Check if user is authenticated (optional for status check)
    $authenticated = false;
    $user = null;
    
    try {
        $user = getCurrentUser();
        $authenticated = true;
    } catch (Exception $e) {
        // Not authenticated, but still allow status check
        $authenticated = false;
    }
    
    // Get server status
    $status = [
        'online' => true,
        'server_time' => date('Y-m-d H:i:s'),
        'server_timezone' => date_default_timezone_get(),
        'timestamp' => time(),
        'authenticated' => $authenticated,
        'sync_available' => $authenticated, // Only allow sync if authenticated
        'version' => '1.0.0'
    ];
    
    // Add database connection status
    try {
        $db_check = $conn->ping();
        $status['database_connected'] = $db_check;
    } catch (Exception $e) {
        $status['database_connected'] = false;
        $status['database_error'] = $e->getMessage();
    }
    
    // Add user-specific info if authenticated
    if ($authenticated && $user) {
        $status['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'company_id' => $user['company_id'],
            'role' => $user['role']
        ];
        
        // Check for pending sync operations
        try {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as pending_count 
                FROM sync_log 
                WHERE user_id = ? AND success = 0 
                AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $status['pending_sync_operations'] = (int)$row['pending_count'];
            
        } catch (Exception $e) {
            $status['pending_sync_operations'] = 0;
        }
        
        // Get last successful sync time
        try {
            $stmt = $conn->prepare("
                SELECT MAX(timestamp) as last_sync 
                FROM sync_log 
                WHERE user_id = ? AND success = 1
            ");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            $status['last_successful_sync'] = $row['last_sync'];
            
        } catch (Exception $e) {
            $status['last_successful_sync'] = null;
        }
    }
    
    // Add server load info (optional)
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $status['server_load'] = [
            '1min' => $load[0],
            '5min' => $load[1],
            '15min' => $load[2]
        ];
    }
    
    // Add memory usage info
    $status['memory_usage'] = [
        'current' => memory_get_usage(true),
        'peak' => memory_get_peak_usage(true),
        'limit' => ini_get('memory_limit')
    ];
    
    // Return success response
    http_response_code(200);
    echo json_encode($status);
    
} catch (Exception $e) {
    // Log error
    error_log("Offline Status API Error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'online' => false,
        'error' => 'Server error occurred',
        'server_time' => date('Y-m-d H:i:s'),
        'timestamp' => time()
    ]);
}
?>
