<?php
/**
 * Core Functions Template
 * This file gets copied to each company's includes folder
 */

/**
 * Get database connection
 */
function getDbConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }
        
        $conn->set_charset("utf8mb4");
    }
    
    return $conn;
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'role' => $_SESSION['user_role'] ?? 'crew'
    ];
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d H:i') {
    if (empty($date)) {
        return '';
    }
    
    return date($format, strtotime($date));
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get vessels for current company
 */
function getVessels() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM vessels WHERE active = 1 ORDER BY vessel_name");
    
    $vessels = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $vessels[] = $row;
        }
    }
    
    return $vessels;
}

/**
 * Get vessel by ID
 */
function getVesselById($vessel_id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM vessels WHERE id = ? AND active = 1");
    $stmt->bind_param("i", $vessel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Get engines for a vessel
 */
function getVesselEngines($vessel_id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM engines WHERE vessel_id = ? AND active = 1 ORDER BY engine_number");
    $stmt->bind_param("i", $vessel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $engines = [];
    while ($row = $result->fetch_assoc()) {
        $engines[] = $row;
    }
    
    return $engines;
}

/**
 * Check if trial is expired
 */
function isTrialExpired() {
    $trial_end = defined('TRIAL_END') ? TRIAL_END : date('Y-m-d', strtotime('+30 days'));
    return strtotime($trial_end) < time();
}

/**
 * Get days remaining in trial
 */
function getTrialDaysRemaining() {
    $trial_end = defined('TRIAL_END') ? TRIAL_END : date('Y-m-d', strtotime('+30 days'));
    $days = ceil((strtotime($trial_end) - time()) / (60 * 60 * 24));
    return max(0, $days);
}

/**
 * Display flash messages
 */
function showFlashMessage($type = 'info', $message = '') {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $type = $flash['type'];
        $message = $flash['message'];
    }
    
    if (!empty($message)) {
        $alert_class = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ][$type] ?? 'alert-info';
        
        echo "<div class='alert $alert_class alert-dismissible fade show' role='alert'>";
        echo htmlspecialchars($message);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>";
        echo "</div>";
    }
}

/**
 * Set flash message for next page load
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}
?>
