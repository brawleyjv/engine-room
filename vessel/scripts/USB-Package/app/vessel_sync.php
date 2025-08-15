<?php
/**
 * Vessel Sync Manager
 * Handles synchronization with main server and registration validation
 */

// Define constant before including config (only if not already defined)
if (!defined('VESSEL_LOGGER')) {
    define('VESSEL_LOGGER', true);
}

class VesselSyncManager {
    private $config;
    private $db;
    private $config_file;
    
    public function __construct() {
        $this->config_file = __DIR__ . '/vessel_config.json';
        $this->loadConfig();
        $this->initDatabase();
    }
    
    /**
     * Load vessel configuration
     */
    private function loadConfig() {
        if (file_exists($this->config_file)) {
            $this->config = json_decode(file_get_contents($this->config_file), true);
        } else {
            throw new Exception('Vessel not configured. Please run registration setup.');
        }
    }
    
    /**
     * Initialize database connection
     */
    private function initDatabase() {
        $db_path = __DIR__ . '/vessel_data.sqlite';
        $this->db = new PDO('sqlite:' . $db_path);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    /**
     * Validate vessel registration with main server
     * Only validates with server every 30 minutes before sync, otherwise uses cached validation
     */
    public function validateRegistration($force_server_check = false) {
        if (!$this->config || !isset($this->config['vessel_name']) || !isset($this->config['hin'])) {
            return ['valid' => false, 'error' => 'Vessel configuration incomplete'];
        }
        
        // Check subscription expiry locally first
        if (isset($this->config['subscription_expires'])) {
            if (strtotime($this->config['subscription_expires']) < time()) {
                return ['valid' => false, 'error' => 'Subscription expired. Contact office administrator.'];
            }
        }
        
        // Check if we need to validate with server (every 30 minutes or forced)
        $last_validated = isset($this->config['last_validated']) ? strtotime($this->config['last_validated']) : 0;
        $validation_interval = 30 * 60; // 30 minutes
        $needs_server_validation = $force_server_check || ((time() - $last_validated) >= $validation_interval);
        
        if ($needs_server_validation) {
            // Attempt to validate with main server
            $server_validation = $this->validateWithServer();
            
            if ($server_validation['success']) {
                // Update local config with latest info
                $this->config['last_validated'] = date('Y-m-d H:i:s');
                $this->config['subscription_expires'] = $server_validation['subscription_expires'];
                $this->config['features_enabled'] = $server_validation['features_enabled'];
                $this->saveConfig();
                
                return ['valid' => true, 'config' => $this->config];
            } else {
                // Server validation failed, use grace period
                $grace_period = 24 * 60 * 60; // 24 hours grace period
                
                if ((time() - $last_validated) < $grace_period) {
                    return ['valid' => true, 'config' => $this->config, 'offline_mode' => true];
                } else {
                    return ['valid' => false, 'error' => 'Unable to validate registration. Please check internet connection.'];
                }
            }
        } else {
            // Use cached validation - vessel is valid based on last server check
            return ['valid' => true, 'config' => $this->config, 'cached' => true];
        }
    }
    
    /**
     * Validate with main server
     */
    private function validateWithServer() {
        $endpoint = rtrim($this->config['office_server_url'], '/') . '/api/vessel/validate.php';
        
        $data = [
            'vessel_name' => $this->config['vessel_name'],
            'hin' => $this->config['hin'],
            'vessel_id' => $this->config['vessel_id'] ?? null,
            'action' => 'validate_registration'
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json\r\n' .
                           'User-Agent: VesselLogger/1.0\r\n',
                'content' => json_encode($data),
                'timeout' => 15
            ]
        ]);
        
        $response = @file_get_contents($endpoint, false, $context);
        
        if ($response === false) {
            return ['success' => false, 'error' => 'Connection failed'];
        }
        
        $result = json_decode($response, true);
        return $result ?? ['success' => false, 'error' => 'Invalid response'];
    }
    
    /**
     * Sync pending data to main server
     * Validates registration every 30 minutes before syncing
     */
    public function syncToServer() {
        // Validate registration before syncing (force server check every 30 minutes)
        $validation = $this->validateRegistration(false); // Will check server if 30 minutes have passed
        if (!$validation['valid']) {
            return ['success' => false, 'error' => 'Registration validation failed: ' . $validation['error']];
        }
        
        // Get vessel info for sync
        $vesselInfo = $this->getVesselInfo();
        if (!$vesselInfo) {
            return ['success' => false, 'error' => 'Vessel info not found'];
        }
        
        $sync_results = [
            'logs_synced' => 0,
            'navigation_synced' => 0,
            'errors' => [],
            'validation_mode' => isset($validation['cached']) ? 'cached' : 
                               (isset($validation['offline_mode']) ? 'offline' : 'server_validated')
        ];
        
        try {
            // Sync vessel logs
            $sync_results['logs_synced'] = $this->syncVesselLogs($vesselInfo);
            
            // Sync navigation data
            $sync_results['navigation_synced'] = $this->syncNavigationData($vesselInfo);
            
            return ['success' => true, 'results' => $sync_results];
            
        } catch (Exception $e) {
            $sync_results['errors'][] = $e->getMessage();
            return ['success' => false, 'results' => $sync_results];
        }
    }
    
    /**
     * Sync vessel logs
     */
    private function syncVesselLogs($vesselInfo) {
        $stmt = $this->db->prepare("SELECT * FROM vessel_logs WHERE sync_status = 'pending' ORDER BY created_at ASC LIMIT 50");
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $synced_count = 0;
        
        foreach ($logs as $log) {
            if ($this->sendLogToServer($log, $vesselInfo)) {
                // Mark as synced
                $update_stmt = $this->db->prepare("UPDATE vessel_logs SET sync_status = 'synced', synced_at = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->execute([$log['id']]);
                $synced_count++;
            }
        }
        
        return $synced_count;
    }

    /**
     * Send log entry to server
     */
    private function sendLogToServer($log, $vesselInfo) {
        // Use company-specific endpoint
        $company_domain = $vesselInfo['company_domain'] ?? '';
        if (empty($company_domain)) {
            throw new Exception('Company domain not configured');
        }
        
        $endpoint = rtrim($this->config['office_server_url'], '/') . '/companies/' . $company_domain . '/api/vessel/logs.php';
        
        $data = [
            'vessel_id' => $vesselInfo['vessel_id'] ?? null,
            'vessel_name' => $vesselInfo['vessel_name'],
            'company_name' => $vesselInfo['company_name'],
            'company_domain' => $vesselInfo['company_domain'],
            'company_database' => $vesselInfo['company_database'],
            'hin' => $vesselInfo['hin'],
            'log_data' => $log,
            'action' => 'add_log'
        ];
        
        return $this->sendToServer($endpoint, $data);
    }

    /**
     * Sync navigation data
     */
    private function syncNavigationData($vesselInfo) {
        $stmt = $this->db->prepare("SELECT * FROM navigation_data WHERE synced_at IS NULL ORDER BY updated_at ASC LIMIT 50");
        $stmt->execute();
        $navigation = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $synced_count = 0;
        
        foreach ($navigation as $nav) {
            if ($this->sendNavigationToServer($nav, $vesselInfo)) {
                // Mark as synced
                $update_stmt = $this->db->prepare("UPDATE navigation_data SET synced_at = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->execute([$nav['id']]);
                $synced_count++;
            }
        }
        
        return $synced_count;
    }
    
    /**
     * Send navigation data to server
     */
    private function sendNavigationToServer($navigation, $vesselInfo) {
        // Use company-specific endpoint
        $company_domain = $vesselInfo['company_domain'] ?? '';
        if (empty($company_domain)) {
            throw new Exception('Company domain not configured');
        }
        
        $endpoint = rtrim($this->config['office_server_url'], '/') . '/companies/' . $company_domain . '/api/vessel/navigation.php';
        
        $data = [
            'vessel_id' => $vesselInfo['vessel_id'] ?? null,
            'vessel_name' => $vesselInfo['vessel_name'],
            'company_name' => $vesselInfo['company_name'],
            'company_domain' => $vesselInfo['company_domain'],
            'company_database' => $vesselInfo['company_database'],
            'hin' => $vesselInfo['hin'],
            'navigation_data' => $navigation,
            'action' => 'add_navigation'
        ];
        
        return $this->sendToServer($endpoint, $data);
    }
    
    /**
     * Generic method to send data to server
     */
    private function sendToServer($endpoint, $data) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json\r\n' .
                           'User-Agent: VesselLogger/1.0\r\n',
                'content' => json_encode($data),
                'timeout' => 30
            ]
        ]);
        
        $response = @file_get_contents($endpoint, false, $context);
        
        if ($response === false) {
            return false;
        }
        
        $result = json_decode($response, true);
        return $result && isset($result['success']) && $result['success'];
    }
    
    /**
     * Save configuration to file
     */
    private function saveConfig() {
        file_put_contents($this->config_file, json_encode($this->config, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get vessel configuration
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Check if vessel is properly registered
     */
    public static function isVesselRegistered() {
        $config_file = __DIR__ . '/vessel_config.json';
        if (!file_exists($config_file)) {
            return false;
        }
        
        $config = json_decode(file_get_contents($config_file), true);
        return $config && isset($config['registration_verified']) && $config['registration_verified'];
    }
}

/**
 * Authentication guard for vessel pages
 * Only checks if vessel is registered initially, not every page load
 */
function requireVesselRegistration() {
    if (!VesselSyncManager::isVesselRegistered()) {
        header('Location: vessel_registration.php');
        exit;
    }
    
    // No need to validate with server on every page load
    // Server validation only happens during sync operations (every 30 minutes)
}
?>
