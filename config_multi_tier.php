<?php
/**
 * Multi-Tier Database Configuration
 * Handles both user authentication and database connectivity
 */

// Shore-side database configuration (Universal for shore access)
$shore_db_config = [
    'host' => 'localhost',
    'database' => 'VesselData', 
    'username' => 'chief',
    'password' => 'rustyzeller'
];

// Vessel-specific database configurations
// Each vessel gets its own database credentials for security isolation
$vessel_db_configs = [
    // Example vessel configurations
    1 => [
        'host' => 'localhost',
        'database' => 'vessel_001_data',
        'username' => 'vessel_001_user',
        'password' => 'vessel_001_pass_2024'
    ],
    2 => [
        'host' => 'localhost', 
        'database' => 'vessel_002_data',
        'username' => 'vessel_002_user', 
        'password' => 'vessel_002_pass_2024'
    ]
    // Add more vessels as needed
];

// Current environment detection
$is_shore_system = !isset($_SESSION['vessel_mode']) || $_SESSION['vessel_mode'] === false;
$current_vessel_id = $_SESSION['active_vessel_id'] ?? null;

// Select appropriate database configuration
if ($is_shore_system) {
    // Shore system - use universal shore credentials
    $db_config = $shore_db_config;
} else {
    // Vessel system - use vessel-specific credentials
    if ($current_vessel_id && isset($vessel_db_configs[$current_vessel_id])) {
        $db_config = $vessel_db_configs[$current_vessel_id];
    } else {
        // Fallback to shore config if vessel config not found
        $db_config = $shore_db_config;
    }
}

// Establish database connection using selected configuration
try {
    $conn = new mysqli(
        $db_config['host'],
        $db_config['username'], 
        $db_config['password'],
        $db_config['database']
    );
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Set charset for security
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    // Log error but don't expose database details to users
    error_log("Database connection error: " . $e->getMessage());
    
    // For vessel systems, fall back to offline mode
    if (!$is_shore_system) {
        $_SESSION['offline_mode'] = true;
        $conn = null; // Will trigger offline SQLite usage
    } else {
        // Shore systems need database access
        die("System temporarily unavailable. Please contact support.");
    }
}

/**
 * Get database configuration for a specific vessel
 * Used during vessel setup and sync operations
 */
function getVesselDatabaseConfig($vessel_id) {
    global $vessel_db_configs;
    return $vessel_db_configs[$vessel_id] ?? null;
}

/**
 * Get shore database configuration
 * Used for shore-side operations and sync endpoints
 */
function getShoreDatabaseConfig() {
    global $shore_db_config;
    return $shore_db_config;
}

/**
 * Check if current system is in offline mode
 */
function isOfflineMode() {
    return isset($_SESSION['offline_mode']) && $_SESSION['offline_mode'] === true;
}

/**
 * Check if current system is shore-based
 */
function isShoreSystem() {
    return !isset($_SESSION['vessel_mode']) || $_SESSION['vessel_mode'] === false;
}

/**
 * Switch to vessel mode (for testing or vessel deployment)
 */
function setVesselMode($vessel_id) {
    $_SESSION['vessel_mode'] = true;
    $_SESSION['active_vessel_id'] = $vessel_id;
    $_SESSION['offline_mode'] = false;
}

/**
 * Switch to shore mode
 */
function setShoreMode() {
    $_SESSION['vessel_mode'] = false;
    unset($_SESSION['active_vessel_id']);
    $_SESSION['offline_mode'] = false;
}
?>
