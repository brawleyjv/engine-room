<?php
/**
 * License Management Functions
 * Handles subscription status, module access, and trial management
 */

class LicenseManager {
    private $conn;
    private $customer_id;
    
    public function __construct($database_connection, $customer_id) {
        $this->conn = $database_connection;
        $this->customer_id = $customer_id;
    }
    
    /**
     * Check if customer is in trial period
     */
    public function isInTrial() {
        $stmt = $this->conn->prepare("
            SELECT trial_end_date, subscription_status 
            FROM license_info 
            WHERE customer_id = ?
        ");
        $stmt->bind_param('s', $this->customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['subscription_status'] === 'trial' && 
                   strtotime($row['trial_end_date']) > time();
        }
        
        return false;
    }
    
    /**
     * Check if customer has active subscription
     */
    public function hasActiveSubscription() {
        $stmt = $this->conn->prepare("
            SELECT subscription_status 
            FROM license_info 
            WHERE customer_id = ?
        ");
        $stmt->bind_param('s', $this->customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return in_array($row['subscription_status'], ['trial', 'active']);
        }
        
        return false;
    }
    
    /**
     * Check if customer can add more vessels
     */
    public function canAddVessel() {
        $stmt = $this->conn->prepare("
            SELECT vessel_limit, 
                   (SELECT COUNT(*) FROM vessels WHERE customer_id = ?) as current_vessels
            FROM license_info 
            WHERE customer_id = ?
        ");
        $stmt->bind_param('ss', $this->customer_id, $this->customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['current_vessels'] < $row['vessel_limit'];
        }
        
        return false;
    }
    
    /**
     * Check if module is enabled for customer
     */
    public function isModuleEnabled($module_code) {
        $stmt = $this->conn->prepare("
            SELECT modules_enabled 
            FROM license_info 
            WHERE customer_id = ?
        ");
        $stmt->bind_param('s', $this->customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $modules = json_decode($row['modules_enabled'], true);
            return in_array($module_code, $modules);
        }
        
        return false;
    }
    
    /**
     * Get license status summary
     */
    public function getLicenseStatus() {
        $stmt = $this->conn->prepare("
            SELECT *, 
                   (SELECT COUNT(*) FROM vessels WHERE customer_id = ?) as current_vessels
            FROM license_info 
            WHERE customer_id = ?
        ");
        $stmt->bind_param('ss', $this->customer_id, $this->customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $row['modules_enabled'] = json_decode($row['modules_enabled'], true);
            $row['days_remaining'] = max(0, ceil((strtotime($row['trial_end_date']) - time()) / 86400));
            return $row;
        }
        
        return null;
    }
    
    /**
     * Activate subscription (called after payment)
     */
    public function activateSubscription($vessel_limit, $modules) {
        $stmt = $this->conn->prepare("
            UPDATE license_info 
            SET subscription_status = 'active',
                vessel_limit = ?,
                modules_enabled = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE customer_id = ?
        ");
        $modules_json = json_encode($modules);
        $stmt->bind_param('iss', $vessel_limit, $modules_json, $this->customer_id);
        
        return $stmt->execute();
    }
    
    /**
     * Suspend subscription (for non-payment)
     */
    public function suspendSubscription() {
        $stmt = $this->conn->prepare("
            UPDATE license_info 
            SET subscription_status = 'suspended',
                updated_at = CURRENT_TIMESTAMP
            WHERE customer_id = ?
        ");
        $stmt->bind_param('s', $this->customer_id);
        
        return $stmt->execute();
    }
    
    /**
     * Add vessel if within limits
     */
    public function addVessel($vessel_data) {
        if (!$this->canAddVessel()) {
            throw new Exception('Vessel limit reached. Please upgrade your subscription.');
        }
        
        if (!$this->hasActiveSubscription()) {
            throw new Exception('Active subscription required to add vessels.');
        }
        
        // Add customer_id to vessel data
        $vessel_data['customer_id'] = $this->customer_id;
        
        // Insert vessel (you'll customize this based on your vessels table structure)
        $stmt = $this->conn->prepare("
            INSERT INTO vessels (customer_id, VesselName, VesselType, EngineConfig, created_at)
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->bind_param('ssss', 
            $vessel_data['customer_id'],
            $vessel_data['vessel_name'],
            $vessel_data['vessel_type'],
            $vessel_data['engine_config']
        );
        
        return $stmt->execute();
    }
}

/**
 * Global license check function
 */
function check_license_access($required_module = null) {
    global $conn;
    
    if (!defined('CUSTOMER_ID')) {
        die('License configuration error. Please contact support.');
    }
    
    $license = new LicenseManager($conn, CUSTOMER_ID);
    
    // Check if subscription is active
    if (!$license->hasActiveSubscription()) {
        header('Location: /subscription_expired.php');
        exit;
    }
    
    // Check specific module access
    if ($required_module && !$license->isModuleEnabled($required_module)) {
        header('Location: /module_not_available.php?module=' . $required_module);
        exit;
    }
    
    return $license;
}

/**
 * Display license warning banner
 */
function show_license_banner() {
    global $conn;
    
    if (!defined('CUSTOMER_ID')) return;
    
    $license = new LicenseManager($conn, CUSTOMER_ID);
    $status = $license->getLicenseStatus();
    
    if ($status['subscription_status'] === 'trial' && $status['days_remaining'] <= 7) {
        echo '<div style="background: #ff9800; color: white; padding: 10px; text-align: center; margin-bottom: 20px;">';
        echo '<strong>Trial Ending Soon!</strong> ';
        echo $status['days_remaining'] . ' days remaining. ';
        echo '<a href="/subscription.php" style="color: white; text-decoration: underline;">Upgrade Now</a>';
        echo '</div>';
    }
}
?>
