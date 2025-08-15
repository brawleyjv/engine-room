<?php
/**
 * Vessel Logger Configuration
 * Core configuration for vessel-side application
 */

// Prevent direct access
if (!defined('VESSEL_LOGGER')) {
    die('Direct access not permitted');
}

// Application constants
define('VESSEL_APP_VERSION', '1.0.0');
define('VESSEL_APP_NAME', 'Vessel Logger');

// Path constants (adjust for USB deployment)
define('VESSEL_ROOT', dirname(dirname(__FILE__)));
define('VESSEL_DATA_DIR', VESSEL_ROOT . '/data');
define('VESSEL_LOGS_DIR', VESSEL_DATA_DIR . '/logs');
define('VESSEL_TEMP_DIR', VESSEL_DATA_DIR . '/temp');

// Database configuration
define('VESSEL_DB_FILE', VESSEL_DATA_DIR . '/vessel.db');
define('VESSEL_DB_BACKUP_DIR', VESSEL_DATA_DIR . '/backups');

// Security configuration
define('VESSEL_SESSION_TIMEOUT', 3600); // 1 hour
define('VESSEL_MAX_LOGIN_ATTEMPTS', 5);
define('VESSEL_LOCKOUT_TIME', 900); // 15 minutes

// Sync configuration
define('VESSEL_SYNC_INTERVAL', 1800); // 30 minutes
define('VESSEL_RETRY_INTERVAL', 900);  // 15 minutes
define('VESSEL_MAX_SYNC_RETRIES', 5);

// Default vessel configuration (will be overridden during setup)
$vessel_config = [
    'vessel_id' => '',
    'vessel_name' => '',
    'hull_number' => '',
    'company_id' => '',
    'company_name' => '',
    'company_api_url' => '',
    'sync_token' => '',
    'license_key' => '',
    'setup_completed' => false
];

// Load vessel-specific configuration if it exists
$vessel_config_file = VESSEL_ROOT . '/app/config/vessel_config.php';
if (file_exists($vessel_config_file)) {
    include $vessel_config_file;
}

// Timezone configuration
date_default_timezone_set($vessel_config['timezone'] ?? 'UTC');

// Error reporting (disable in production)
if (($vessel_config['debug_mode'] ?? false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', VESSEL_LOGS_DIR . '/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', VESSEL_LOGS_DIR . '/php_errors.log');
}

// Session configuration
ini_set('session.cookie_lifetime', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.name', 'VESSEL_SESSION');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include core functions
require_once VESSEL_ROOT . '/app/includes/functions.php';
require_once VESSEL_ROOT . '/app/includes/database.php';
require_once VESSEL_ROOT . '/app/security/auth.php';

// Initialize application
initializeVesselApp();

/**
 * Initialize vessel application
 */
function initializeVesselApp() {
    global $vessel_config;
    
    // Create required directories
    $required_dirs = [
        VESSEL_DATA_DIR,
        VESSEL_LOGS_DIR,
        VESSEL_TEMP_DIR,
        VESSEL_DB_BACKUP_DIR
    ];
    
    foreach ($required_dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Initialize database if needed
    initializeDatabase();
    
    // Log application start
    logMessage('Application started - Version ' . VESSEL_APP_VERSION);
    
    // Check if setup is required
    if (!$vessel_config['setup_completed']) {
        // Redirect to setup if not accessing setup page
        if (!strpos($_SERVER['REQUEST_URI'], 'setup.php')) {
            header('Location: setup.php');
            exit;
        }
    }
}

/**
 * Get vessel configuration value
 */
function getVesselConfig($key, $default = null) {
    global $vessel_config;
    return $vessel_config[$key] ?? $default;
}

/**
 * Set vessel configuration value
 */
function setVesselConfig($key, $value) {
    global $vessel_config;
    $vessel_config[$key] = $value;
}

/**
 * Save vessel configuration to file
 */
function saveVesselConfig() {
    global $vessel_config;
    
    $config_file = VESSEL_ROOT . '/app/config/vessel_config.php';
    $config_content = "<?php\n";
    $config_content .= "// Vessel-specific configuration\n";
    $config_content .= "// Generated on " . date('Y-m-d H:i:s') . "\n\n";
    $config_content .= "if (!defined('VESSEL_LOGGER')) {\n";
    $config_content .= "    die('Direct access not permitted');\n";
    $config_content .= "}\n\n";
    $config_content .= "\$vessel_config = " . var_export($vessel_config, true) . ";\n";
    
    return file_put_contents($config_file, $config_content) !== false;
}

/**
 * Log message to application log
 */
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message\n";
    
    $log_file = VESSEL_LOGS_DIR . '/application.log';
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
    // Rotate log if it gets too large (>10MB)
    if (file_exists($log_file) && filesize($log_file) > 10 * 1024 * 1024) {
        rotateLogFile($log_file);
    }
}

/**
 * Rotate log file
 */
function rotateLogFile($log_file) {
    $backup_file = $log_file . '.' . date('Y-m-d-H-i-s');
    rename($log_file, $backup_file);
    
    // Compress old log
    if (function_exists('gzopen')) {
        $compressed = gzopen($backup_file . '.gz', 'w9');
        $uncompressed = file_get_contents($backup_file);
        gzwrite($compressed, $uncompressed);
        gzclose($compressed);
        unlink($backup_file);
    }
}

/**
 * Get application status
 */
function getApplicationStatus() {
    global $vessel_config;
    
    return [
        'version' => VESSEL_APP_VERSION,
        'setup_completed' => $vessel_config['setup_completed'],
        'vessel_name' => $vessel_config['vessel_name'],
        'company_name' => $vessel_config['company_name'],
        'database_size' => file_exists(VESSEL_DB_FILE) ? filesize(VESSEL_DB_FILE) : 0,
        'last_sync' => $vessel_config['last_sync'] ?? null,
        'sync_status' => $vessel_config['sync_status'] ?? 'unknown'
    ];
}
?>
