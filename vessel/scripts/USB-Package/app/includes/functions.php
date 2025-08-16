<?php
/**
 * Core Functions for Vessel Logger
 * Shared utility functions used throughout the application
 */

// Prevent direct access
if (!defined('VESSEL_LOGGER')) {
    die('Direct access not permitted');
}

/**
 * Generate secure random token
 */
function generateSecureToken($length = 32) {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes($length / 2));
    } else {
        return bin2hex(openssl_random_pseudo_bytes($length / 2));
    }
}

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) {
        return '';
    }
    
    if ($date instanceof DateTime) {
        return $date->format($format);
    }
    
    return date($format, strtotime($date));
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ip_headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Generate unique ID
 */
function generateUniqueId($prefix = '') {
    return $prefix . uniqid() . '_' . mt_rand(1000, 9999);
}

/**
 * Check if connection to internet is available
 */
function isOnline() {
    $connected = @fsockopen("8.8.8.8", 53, $errno, $errstr, 3);
    if ($connected) {
        fclose($connected);
        return true;
    }
    return false;
}

/**
 * Test company API connection
 */
function testApiConnection() {
    $api_url = getVesselConfig('company_api_url');
    $sync_token = getVesselConfig('sync_token');
    
    if (empty($api_url) || empty($sync_token)) {
        return ['success' => false, 'error' => 'API configuration missing'];
    }
    
    $test_url = rtrim($api_url, '/') . '/vessel/test-connection';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $sync_token
            ],
            'content' => json_encode([
                'vessel_id' => getVesselConfig('vessel_id'),
                'timestamp' => date('Y-m-d H:i:s')
            ]),
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($test_url, false, $context);
    
    if ($response === false) {
        return ['success' => false, 'error' => 'Connection failed'];
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid response format'];
    }
    
    return $data;
}

/**
 * Encrypt sensitive data
 */
function encryptData($data, $key = null) {
    if ($key === null) {
        $key = getVesselConfig('encryption_key', 'default_vessel_key');
    }
    
    $cipher = 'AES-256-CBC';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
    $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
    
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt sensitive data
 */
function decryptData($encrypted_data, $key = null) {
    if ($key === null) {
        $key = getVesselConfig('encryption_key', 'default_vessel_key');
    }
    
    $data = base64_decode($encrypted_data);
    $cipher = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($cipher);
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
}

/**
 * Validate vessel credentials
 */
function validateVesselCredentials($vessel_id, $hull_number) {
    // Basic validation
    if (empty($vessel_id) || empty($hull_number)) {
        return false;
    }
    
    // Check format (customize as needed)
    if (strlen($vessel_id) < 3 || strlen($hull_number) < 7) {
        return false;
    }
    
    return true;
}

/**
 * Get hardware fingerprint for security
 */
function getHardwareFingerprint() {
    $fingerprint_data = [];
    
    // Get computer name
    $fingerprint_data[] = gethostname();
    
    // Get Windows-specific information
    if (PHP_OS_FAMILY === 'Windows') {
        // Get motherboard serial number
        $wmi_query = 'wmic baseboard get serialnumber /value 2>nul';
        $output = shell_exec($wmi_query);
        if ($output) {
            $fingerprint_data[] = trim(str_replace('SerialNumber=', '', $output));
        }
        
        // Get processor ID
        $wmi_query = 'wmic cpu get processorid /value 2>nul';
        $output = shell_exec($wmi_query);
        if ($output) {
            $fingerprint_data[] = trim(str_replace('ProcessorId=', '', $output));
        }
    }
    
    // Get disk serial (C: drive)
    if (PHP_OS_FAMILY === 'Windows') {
        $wmi_query = 'wmic logicaldisk where size!=0 get size,volumeserialnumber /value 2>nul';
        $output = shell_exec($wmi_query);
        if ($output) {
            $fingerprint_data[] = substr($output, 0, 100); // Truncate for consistency
        }
    }
    
    // Create hash of all fingerprint data
    return hash('sha256', implode('|', array_filter($fingerprint_data)));
}

/**
 * Get system information
 */
function getSystemInfo() {
    return [
        'os' => PHP_OS,
        'php_version' => PHP_VERSION,
        'hostname' => gethostname(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'disk_space_free' => disk_free_space('.'),
        'disk_space_total' => disk_total_space('.'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize')
    ];
}

/**
 * Clean old log files
 */
function cleanOldLogs($days = 30) {
    $log_dir = VESSEL_LOGS_DIR;
    $cutoff_time = time() - ($days * 24 * 60 * 60);
    $cleaned_files = 0;
    
    if (is_dir($log_dir)) {
        $files = scandir($log_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $file_path = $log_dir . '/' . $file;
                if (is_file($file_path) && filemtime($file_path) < $cutoff_time) {
                    if (unlink($file_path)) {
                        $cleaned_files++;
                    }
                }
            }
        }
    }
    
    logMessage("Cleaned $cleaned_files old log files");
    return $cleaned_files;
}

/**
 * Get application uptime
 */
function getApplicationUptime() {
    $start_time = getSetting('app_start_time');
    if ($start_time) {
        return time() - strtotime($start_time);
    }
    return 0;
}

/**
 * Format uptime in human readable format
 */
function formatUptime($seconds) {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    $parts = [];
    if ($days > 0) $parts[] = "$days day" . ($days != 1 ? 's' : '');
    if ($hours > 0) $parts[] = "$hours hour" . ($hours != 1 ? 's' : '');
    if ($minutes > 0) $parts[] = "$minutes minute" . ($minutes != 1 ? 's' : '');
    
    return empty($parts) ? 'Less than a minute' : implode(', ', $parts);
}

/**
 * Emergency shutdown procedure
 */
function emergencyShutdown($reason = 'Unknown') {
    logMessage("Emergency shutdown initiated: $reason", 'CRITICAL');
    
    // Close database connection
    global $vessel_db;
    if ($vessel_db) {
        $vessel_db = null;
    }
    
    // Clear sensitive session data
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    
    // Display emergency message
    http_response_code(503);
    echo "System temporarily unavailable. Please contact support.";
    exit;
}

/**
 * Validate JSON data
 */
function validateJSON($json_string) {
    json_decode($json_string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Safe JSON encode with error handling
 */
function safeJsonEncode($data) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage('JSON encode error: ' . json_last_error_msg(), 'ERROR');
        return false;
    }
    return $json;
}

/**
 * Safe JSON decode with error handling
 */
function safeJsonDecode($json, $assoc = true) {
    $data = json_decode($json, $assoc);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logMessage('JSON decode error: ' . json_last_error_msg(), 'ERROR');
        return false;
    }
    return $data;
}

/**
 * Get daily statistics for dashboard
 */
function getDailyStats() {
    $db = getDatabase();
    
    try {
        // Get basic log counts
        $stmt = $db->prepare("
            SELECT 
                COUNT(CASE WHEN DATE(created_at) = DATE('now') THEN 1 END) as logs_today,
                COUNT(*) as total_logs
            FROM engine_logs
        ");
        $stmt->execute();
        $basic_stats = $stmt->fetch();
        
        return [
            'logs_today' => (int)$basic_stats['logs_today'],
            'total_logs' => (int)$basic_stats['total_logs']
        ];
    } catch (PDOException $e) {
        logMessage('Error getting daily stats: ' . $e->getMessage(), 'ERROR');
        return [
            'logs_today' => 0,
            'total_logs' => 0
        ];
    }
}

/**
 * Get recent engine logs for dashboard
 */
function getRecentLogs($limit = 10) {
    $db = getDatabase();
    
    try {
        $stmt = $db->prepare("
            SELECT 
                el.*,
                e.name as engine_name,
                e.position as engine_position,
                v.name as vessel_name
            FROM engine_logs el
            LEFT JOIN engines e ON el.engine_id = e.id
            LEFT JOIN vessels v ON el.vessel_id = v.id
            ORDER BY el.log_datetime DESC, el.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $logs = $stmt->fetchAll();
        
        // Format logs for display
        foreach ($logs as &$log) {
            $log['summary'] = sprintf(
                "%s - %.1f°F, %d PSI Oil, %d RPM",
                $log['engine_name'] ?: 'Engine ' . $log['engine_id'],
                $log['temperature'] ?: 0,
                $log['oil_pressure'] ?: 0,
                $log['rpm'] ?: 0
            );
            $log['equipment_type'] = $log['engine_position'] ?: 'Main Engine';
            $log['engine_number'] = $log['engine_name'] ?: 'Engine ' . $log['engine_id'];
        }
        
        return $logs;
    } catch (PDOException $e) {
        logMessage('Error getting recent logs: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * Get engine-specific statistics for dashboard
 */
function getEngineStats() {
    $db = getDatabase();
    
    try {
        // Get individual engine statistics from recent logs (last 24 hours)
        $stmt = $db->prepare("
            SELECT 
                e.id,
                e.name,
                e.position,
                COUNT(el.id) as log_count,
                AVG(CASE WHEN el.temperature > 0 THEN el.temperature END) as avg_temp,
                AVG(CASE WHEN el.oil_pressure > 0 THEN el.oil_pressure END) as avg_oil_pressure,
                AVG(CASE WHEN el.coolant_pressure > 0 THEN el.coolant_pressure END) as avg_coolant_pressure,
                MAX(el.log_datetime) as last_log
            FROM engines e
            LEFT JOIN engine_logs el ON e.id = el.engine_id 
                AND el.log_datetime >= datetime('now', '-24 hours')
            GROUP BY e.id, e.name, e.position
            ORDER BY e.position, e.name
        ");
        $stmt->execute();
        $engines = $stmt->fetchAll();
        
        // Format engine data
        foreach ($engines as &$engine) {
            $engine['display_name'] = $engine['name'] ?: 
                ($engine['position'] ? $engine['position'] . ' Engine' : 'Engine ' . $engine['id']);
            $engine['avg_temp'] = $engine['avg_temp'] ? round($engine['avg_temp'], 1) : null;
            $engine['avg_oil_pressure'] = $engine['avg_oil_pressure'] ? round($engine['avg_oil_pressure'], 1) : null;
            $engine['avg_coolant_pressure'] = $engine['avg_coolant_pressure'] ? round($engine['avg_coolant_pressure'], 1) : null;
        }
        
        return $engines;
    } catch (PDOException $e) {
        logMessage('Error getting engine stats: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * Get pending sync count
 */
function getPendingSyncCount() {
    $db = getDatabase();
    
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM engine_logs WHERE synced = 0
        ");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        logMessage('Error getting pending sync count: ' . $e->getMessage(), 'ERROR');
        return 0;
    }
}

/**
 * Check database health
 */
function checkDatabaseHealth() {
    try {
        $db = getDatabase();
        // Simple query to test database connection
        $stmt = $db->prepare("SELECT 1");
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        logMessage('Database health check failed: ' . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Get storage information
 */
function getStorageInfo() {
    $db_file = VESSEL_DB_FILE;
    $total_space = disk_total_space('.');
    $free_space = disk_free_space('.');
    $db_size = file_exists($db_file) ? filesize($db_file) : 0;
    
    return [
        'total_space' => $total_space,
        'free_space' => $free_space,
        'free_percent' => $total_space > 0 ? ($free_space / $total_space) * 100 : 0,
        'database_size' => $db_size
    ];
}

/**
 * Get sync status
 */
function getSyncStatus() {
    return [
        'last_sync' => getSetting('last_sync_time'),
        'sync_enabled' => getSetting('sync_enabled', '1') === '1',
        'sync_interval' => (int)getSetting('sync_interval', VESSEL_SYNC_INTERVAL)
    ];
}
?>
