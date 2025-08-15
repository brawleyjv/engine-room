<?php
/**
 * Multi-Tenant SaaS Configuration System
 * Similar to WordPress multi-site but for vessel logging companies
 */

// Define base application paths
define('APP_ROOT', __DIR__);
define('APP_URL_PATH', str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__));
define('BASE_URL', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . APP_URL_PATH);

// Master license/tenant database configuration
// Default development configuration
$license_db_config = [
    'host' => 'localhost',
    'database' => 'vessel_license_master',
    'username' => 'license_admin',
    'password' => 'master_license_key_2024'
];

// Production configuration override
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'logicdock.org') {
    $license_db_config = [
        'host' => 'localhost',
        'database' => 'vessel_license_master',
        'username' => 'license_admin',
        'password' => 'Zhq4VNrT'
    ];
}

// Alternative: Include production config file if it exists
if (file_exists(__DIR__ . '/config_production.php')) {
    include_once __DIR__ . '/config_production.php';
}

/**
 * Get company database configuration from license system
 */
function getCompanyDatabaseConfig($company_domain_or_id) {
    global $license_db_config;
    
    try {
        // Connect to license database
        $license_conn = new mysqli(
            $license_db_config['host'],
            $license_db_config['username'],
            $license_db_config['password'],
            $license_db_config['database']
        );
        
        if ($license_conn->connect_error) {
            throw new Exception("License system unavailable");
        }
        
        // Get company configuration
        $sql = "SELECT 
                    id as CompanyID,
                    company_name as CompanyName,
                    database_host as DatabaseHost,
                    database_name as DatabaseName, 
                    database_username as DatabaseUsername,
                    database_password as DatabasePassword,
                    subscription_status as SubscriptionStatus,
                    subscription_end_date as ExpiryDate,
                    max_vessels as MaxVessels,
                    max_users as MaxUsers,
                    enabled_features as Features
                FROM companies 
                WHERE (company_domain = ? OR id = ?) 
                AND subscription_status = 'active' 
                AND (subscription_end_date IS NULL OR subscription_end_date > NOW())";
        
        $stmt = $license_conn->prepare($sql);
        $stmt->bind_param('ss', $company_domain_or_id, $company_domain_or_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($company = $result->fetch_assoc()) {
            $license_conn->close();
            
            // Decrypt database password
            $company['DatabasePassword'] = decryptPassword($company['DatabasePassword']);
            
            return [
                'success' => true,
                'company' => $company,
                'database' => [
                    'host' => $company['DatabaseHost'],
                    'database' => $company['DatabaseName'],
                    'username' => $company['DatabaseUsername'],
                    'password' => $company['DatabasePassword']
                ]
            ];
        } else {
            $license_conn->close();
            return [
                'success' => false,
                'error' => 'Company not found or subscription expired'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'License system error: ' . $e->getMessage()
        ];
    }
}

/**
 * Determine company from current request
 */
function determineCompanyContext() {
    // Method 1: Subdomain (preferred)
    // acme-marine.vessellogger.com → company: acme-marine
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (preg_match('/^([^.]+)\.vessellogger\.com$/', $host, $matches)) {
        return $matches[1];
    }
    
    // Method 2: URL parameter
    if (isset($_GET['company'])) {
        return $_GET['company'];
    }
    
    // Method 3: Session (already logged in)
    if (isset($_SESSION['company_domain'])) {
        return $_SESSION['company_domain'];
    }
    if (isset($_SESSION['company_id'])) {
        return $_SESSION['company_id'];
    }
    
    // Method 4: Installation domain mapping
    // For single-domain installations with path-based separation
    $install_mappings = [
        'localhost' => 'demo_company',
        'vessel.company-a.com' => 'company_a',
        'vessel.company-b.com' => 'company_b',
        'logicdock.org' => 'logicdock'
    ];
    
    return $install_mappings[$host] ?? null;
}

/**
 * Initialize database connection for current company
 */
function initializeCompanyDatabase() {
    $company_context = determineCompanyContext();
    
    if (!$company_context) {
        // Redirect to company selection or main landing page
        header('Location: /select-company.php');
        exit;
    }
    
    $config_result = getCompanyDatabaseConfig($company_context);
    
    if (!$config_result['success']) {
        // Handle license issues
        if (strpos($config_result['error'], 'expired') !== false) {
            header('Location: /subscription-expired.php');
        } else {
            header('Location: /company-not-found.php');
        }
        exit;
    }
    
    // Store company info in session
    $_SESSION['company_id'] = $config_result['company']['CompanyID'];
    $_SESSION['company_name'] = $config_result['company']['CompanyName'];
    $_SESSION['subscription_features'] = json_decode($config_result['company']['Features'], true);
    $_SESSION['max_vessels'] = $config_result['company']['MaxVessels'];
    $_SESSION['max_users'] = $config_result['company']['MaxUsers'];
    
    // Connect to company database
    $db_config = $config_result['database'];
    
    try {
        $conn = new mysqli(
            $db_config['host'],
            $db_config['username'],
            $db_config['password'],
            $db_config['database']
        );
        
        if ($conn->connect_error) {
            throw new Exception("Company database connection failed");
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
        
    } catch (Exception $e) {
        error_log("Company DB connection error for {$company_context}: " . $e->getMessage());
        header('Location: /database-error.php');
        exit;
    }
}

/**
 * Check if company has access to specific feature
 */
function companyHasFeature($feature) {
    $features = $_SESSION['subscription_features'] ?? [];
    return in_array($feature, $features);
}

/**
 * Check company limits
 */
function checkCompanyLimits($type, $current_count) {
    $limits = [
        'vessels' => $_SESSION['max_vessels'] ?? 0,
        'users' => $_SESSION['max_users'] ?? 0
    ];
    
    $limit = $limits[$type] ?? 0;
    
    return [
        'within_limit' => $limit == 0 || $current_count < $limit, // 0 = unlimited
        'limit' => $limit,
        'current' => $current_count,
        'remaining' => $limit == 0 ? 'unlimited' : max(0, $limit - $current_count)
    ];
}

/**
 * Simple password encryption for database credentials
 */
function encryptPassword($password) {
    $key = 'vessel_logger_encryption_key_2024'; // In production, use environment variable
    return base64_encode(openssl_encrypt($password, 'AES-256-CBC', $key, 0, substr(md5($key), 0, 16)));
}

function decryptPassword($encrypted_password) {
    $key = 'vessel_logger_encryption_key_2024';
    return openssl_decrypt(base64_decode($encrypted_password), 'AES-256-CBC', $key, 0, substr(md5($key), 0, 16));
}

/**
 * Get current company information
 */
function getCurrentCompany() {
    return [
        'id' => $_SESSION['company_id'] ?? null,
        'name' => $_SESSION['company_name'] ?? null,
        'features' => $_SESSION['subscription_features'] ?? [],
        'max_vessels' => $_SESSION['max_vessels'] ?? 0,
        'max_users' => $_SESSION['max_users'] ?? 0
    ];
}

?>
