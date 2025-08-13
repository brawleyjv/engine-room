<?php
// Production Configuration for IONOS Hosting
// Replace with your actual IONOS database credentials

// IONOS Database Configuration
$servername = "db5000123456.db.1and1.com";  // Replace with your IONOS DB host
$username = "dbs123456";                     // Replace with your DB username  
$password = "your_secure_password";          // Replace with your DB password
$dbname = "db123456789";                     // Replace with your DB name

// Create connection with enhanced error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        // Log error instead of displaying it in production
        error_log("Database connection failed: " . $conn->connect_error);
        die("Database connection error. Please contact administrator.");
    }
    
    // Set charset for security
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("Database error. Please contact administrator.");
}

// Production Security Settings
ini_set('session.cookie_httponly', 1);     // Prevent XSS attacks on sessions
ini_set('session.cookie_secure', 1);       // Require HTTPS for sessions (enable after SSL setup)
ini_set('session.use_strict_mode', 1);     // Prevent session fixation

// Error Reporting (DISABLE in production)
// error_reporting(0);                      // Uncomment this line for production
// ini_set('display_errors', 0);           // Uncomment this line for production

// For debugging only (REMOVE in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Email Configuration for IONOS
// Uncomment and configure these for password reset emails
/*
ini_set('SMTP', 'smtp.ionos.com');
ini_set('smtp_port', '587');
ini_set('sendmail_from', 'noreply@yourdomain.com');
*/

// Application Settings
define('APP_NAME', 'Vessel Data Logger');
define('APP_VERSION', '1.0');
define('BASE_URL', 'https://yourdomain.com/vessels/'); // Update with your actual URL

// Security: Regenerate session ID periodically
if (!isset($_SESSION)) {
    session_start();
}

// Regenerate session ID every 30 minutes for security
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}
?>
