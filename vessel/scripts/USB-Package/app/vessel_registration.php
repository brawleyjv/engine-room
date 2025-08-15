<?php
/**
 * Vessel Registration Setup
 * Validates vessel with main server before allowing system use
 */

// Define constant before including config (only if not already defined)
if (!defined('VESSEL_LOGGER')) {
    define('VESSEL_LOGGER', true);
}

// Start session
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

$error_message = '';
$success_message = '';
$step = $_GET['step'] ?? 'vessel_info';

// Check if vessel is already registered
$config_file = __DIR__ . '/vessel_config.json';
$vessel_registered = false;

if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    if ($config && isset($config['vessel_name']) && isset($config['hin']) && isset($config['registration_verified'])) {
        $vessel_registered = $config['registration_verified'];
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($step == 'vessel_info') {
        $vessel_name = trim($_POST['vessel_name'] ?? '');
        $company_name = trim($_POST['company_name'] ?? '');
        $company_domain = trim($_POST['company_domain'] ?? '');
        $hin = trim($_POST['hin'] ?? '');
        $office_server_url = trim($_POST['office_server_url'] ?? '');
        
        // Remove vessel prefixes from vessel name
        $vessel_name = removeVesselPrefixes($vessel_name);
        
        if (empty($vessel_name) || empty($company_name) || empty($company_domain) || empty($hin) || empty($office_server_url)) {
            $error_message = 'All fields are required.';
        } else {
            // Validate vessel with main server
            $registration_result = validateVesselRegistration($vessel_name, $company_name, $company_domain, $hin, $office_server_url);
            
            if ($registration_result['success']) {
                // Save vessel configuration
                $config = [
                    'vessel_name' => $vessel_name,
                    'company_name' => $company_name,
                    'company_domain' => $company_domain,
                    'company_database' => 'vessel_' . str_replace('-', '_', $company_domain),
                    'hin' => $hin,
                    'office_server_url' => $office_server_url,
                    'registration_verified' => true,
                    'registered_at' => date('Y-m-d H:i:s'),
                    'vessel_id' => $registration_result['vessel_id'],
                    'subscription_expires' => $registration_result['subscription_expires'],
                    'features_enabled' => $registration_result['features_enabled']
                ];
                
                if (file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT))) {
                    $success_message = 'Vessel registered successfully! Redirecting to setup database...';
                    $vessel_registered = true;
                    // Auto-redirect after 2 seconds
                    header("refresh:2;url=?step=database_setup");
                } else {
                    $error_message = 'Failed to save vessel configuration.';
                }
            } else {
                $error_message = $registration_result['error'];
            }
        }
    } elseif ($step == 'database_setup') {
        // Initialize local SQLite database
        if (initializeVesselDatabase()) {
            $success_message = 'Database initialized successfully! Redirecting to vessel logger...';
            header("refresh:2;url=vessel_login.php");
        } else {
            $error_message = 'Failed to initialize database.';
        }
    }
}

/**
 * Remove vessel prefixes (MV, FV, SS, etc.) from vessel name
 */
function removeVesselPrefixes($vessel_name) {
    // Common vessel prefixes to remove
    $prefixes = ['MV ', 'FV ', 'SS ', 'MS ', 'MT ', 'RV ', 'SV ', 'MY ', 'TSV ', 'HSV '];
    
    foreach ($prefixes as $prefix) {
        if (stripos($vessel_name, $prefix) === 0) {
            return trim(substr($vessel_name, strlen($prefix)));
        }
    }
    
    return trim($vessel_name);
}

/**
 * Validate vessel registration with main server
 */
function validateVesselRegistration($vessel_name, $company_name, $company_domain, $hin, $server_url) {
    // Remove trailing slash from server URL
    $server_url = rtrim($server_url, '/');
    $endpoint = $server_url . '/api/vessel/validate.php';
    
    $data = [
        'vessel_name' => $vessel_name,
        'company_name' => $company_name,
        'company_domain' => $company_domain,
        'hin' => $hin,
        'action' => 'validate_registration'
    ];
    
    // In production, this would make an actual HTTP request to the main server
    // For now, we'll simulate the validation
    $demo_vessels = [
        'Ocean Explorer' => ['company' => 'Acme Shipping', 'domain' => 'acme-shipping', 'hin' => 'ABC123456789', 'vessel_id' => 'VES001'],
        'Atlantic Star' => ['company' => 'Maritime Corp', 'domain' => 'maritime-corp', 'hin' => 'DEF987654321', 'vessel_id' => 'VES002'],
        'Northern Wind' => ['company' => 'Fisher Fleet', 'domain' => 'fisher-fleet', 'hin' => 'GHI456789123', 'vessel_id' => 'VES003'],
        'Minnow' => ['company' => 'Gilligan Tours', 'domain' => 'gilligan-tours', 'hin' => 'MIN123456789', 'vessel_id' => 'VES004']
    ];
    
    // Simulate network delay
    sleep(1);
    
    if (isset($demo_vessels[$vessel_name]) && 
        $demo_vessels[$vessel_name]['hin'] === $hin &&
        $demo_vessels[$vessel_name]['domain'] === $company_domain) {
        return [
            'success' => true,
            'vessel_id' => $demo_vessels[$vessel_name]['vessel_id'],
            'company_database' => 'vessel_' . str_replace('-', '_', $company_domain),
            'subscription_expires' => date('Y-m-d', strtotime('+1 year')),
            'features_enabled' => ['logging', 'sync', 'reports', 'maintenance']
        ];
    } else {
        return [
            'success' => false,
            'error' => 'Vessel not found or information does not match. Please contact your office administrator to register this vessel with the correct company domain.'
        ];
    }
    
    /* Production code would be:
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data),
            'timeout' => 30
        ]
    ]);
    
    $response = @file_get_contents($endpoint, false, $context);
    
    if ($response === false) {
        return [
            'success' => false,
            'error' => 'Unable to connect to office server. Please check your internet connection and server URL.'
        ];
    }
    
    $result = json_decode($response, true);
    return $result ?? ['success' => false, 'error' => 'Invalid response from server.'];
    */
}

/**
 * Initialize vessel SQLite database
 */
function initializeVesselDatabase() {
    try {
        $db_path = __DIR__ . '/vessel_data.sqlite';
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create tables
        $sql = "
        CREATE TABLE IF NOT EXISTS vessel_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            log_type VARCHAR(50) NOT NULL,
            entry_text TEXT NOT NULL,
            author VARCHAR(100) NOT NULL,
            author_role VARCHAR(50) NOT NULL,
            position VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            synced_at DATETIME NULL,
            sync_status VARCHAR(20) DEFAULT 'pending'
        );
        
        CREATE TABLE IF NOT EXISTS fuel_levels (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tank_name VARCHAR(100) NOT NULL,
            level_percentage DECIMAL(5,2) NOT NULL,
            capacity_liters INTEGER NOT NULL,
            recorded_by VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            synced_at DATETIME NULL
        );
        
        CREATE TABLE IF NOT EXISTS maintenance_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            equipment_name VARCHAR(100) NOT NULL,
            maintenance_type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            performed_by VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            next_due_date DATE,
            synced_at DATETIME NULL
        );
        
        CREATE TABLE IF NOT EXISTS sync_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            table_name VARCHAR(50) NOT NULL,
            record_id INTEGER NOT NULL,
            action VARCHAR(20) NOT NULL DEFAULT 'insert',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            attempts INTEGER DEFAULT 0,
            last_error TEXT NULL
        );
        
        CREATE TABLE IF NOT EXISTS navigation_data (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            destination VARCHAR(200),
            eta DATETIME,
            updated_by VARCHAR(100) NOT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            synced_at DATETIME NULL
        );
        ";
        
        $pdo->exec($sql);
        return true;
    } catch (Exception $e) {
        error_log("Database initialization error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check vessel registration status with main server
 */
function checkRegistrationStatus() {
    $config_file = __DIR__ . '/vessel_config.json';
    if (!file_exists($config_file)) {
        return ['valid' => false, 'error' => 'Vessel not configured'];
    }
    
    $config = json_decode(file_get_contents($config_file), true);
    if (!$config || !$config['registration_verified']) {
        return ['valid' => false, 'error' => 'Vessel registration not verified'];
    }
    
    // Check if subscription is still valid
    if (isset($config['subscription_expires'])) {
        if (strtotime($config['subscription_expires']) < time()) {
            return ['valid' => false, 'error' => 'Subscription expired'];
        }
    }
    
    // In production, this would periodically validate with the main server
    // For now, we'll consider it valid if locally verified
    return ['valid' => true, 'config' => $config];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Registration Setup</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .setup-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 20px;
        }
        
        .step {
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.inactive {
            background: #e9ecef;
            color: #6c757d;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        input[type="text"], input[type="url"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .help-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .demo-info {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }
        
        .demo-info h4 {
            margin-bottom: 10px;
        }
        
        .demo-vessels {
            font-family: monospace;
            font-size: 0.9rem;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="header">
            <h1>🚢 Vessel Registration</h1>
            <p>Register your vessel with the main office system</p>
        </div>
        
        <div class="step-indicator">
            <div class="step <?php echo $step == 'vessel_info' ? 'active' : ($vessel_registered ? 'completed' : 'inactive'); ?>">
                1. Vessel Info
            </div>
            <div class="step <?php echo $step == 'database_setup' ? 'active' : 'inactive'; ?>">
                2. Database Setup
            </div>
            <div class="step inactive">
                3. Complete
            </div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($step == 'vessel_info'): ?>
            <div class="demo-info">
                <h4>Demo Mode - Test Vessels:</h4>
                <div class="demo-vessels">
                    • Ocean Explorer (Acme Shipping / acme-shipping, HIN: ABC123456789)<br>
                    • Atlantic Star (Maritime Corp / maritime-corp, HIN: DEF987654321)<br>
                    • Northern Wind (Fisher Fleet / fisher-fleet, HIN: GHI456789123)<br>
                    • Minnow (Gilligan Tours / gilligan-tours, HIN: MIN123456789)
                </div>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="vessel_name">Vessel Name:</label>
                    <input type="text" id="vessel_name" name="vessel_name" required 
                           placeholder="e.g., Ocean Explorer (without MV, SS, FV prefixes)"
                           value="<?php echo htmlspecialchars($_POST['vessel_name'] ?? ''); ?>">
                    <div class="help-text">Vessel name only - do not include prefixes like MV, SS, or FV</div>
                </div>
                
                <div class="form-group">
                    <label for="company_name">Company Name:</label>
                    <input type="text" id="company_name" name="company_name" required 
                           placeholder="e.g., Gilligan Tours"
                           value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>">
                    <div class="help-text">Full company name as registered with your service</div>
                </div>
                
                <div class="form-group">
                    <label for="company_domain">Company Domain:</label>
                    <input type="text" id="company_domain" name="company_domain" required 
                           placeholder="e.g., gilligan-tours"
                           value="<?php echo htmlspecialchars($_POST['company_domain'] ?? ''); ?>">
                    <div class="help-text">Company identifier provided by your administrator (lowercase, use dashes)</div>
                </div>
                
                <div class="form-group">
                    <label for="hin">Hull Identification Number (HIN):</label>
                    <input type="text" id="hin" name="hin" required 
                           placeholder="e.g., ABC123456789"
                           value="<?php echo htmlspecialchars($_POST['hin'] ?? ''); ?>">
                    <div class="help-text">12-character HIN located on the vessel's transom</div>
                </div>
                
                <div class="form-group">
                    <label for="office_server_url">Office Server URL:</label>
                    <input type="url" id="office_server_url" name="office_server_url" required 
                           placeholder="https://fleet.yourcompany.com"
                           value="<?php echo htmlspecialchars($_POST['office_server_url'] ?? 'https://demo.enginerm.com'); ?>">
                    <div class="help-text">Main office server URL provided by your administrator</div>
                </div>
                
                <button type="submit" class="btn">Validate Vessel Registration</button>
            </form>
            
        <?php elseif ($step == 'database_setup'): ?>
            <div class="loading">
                <div class="spinner"></div>
                <h3>Setting up vessel database...</h3>
                <p>Initializing local storage for offline operations</p>
            </div>
            
            <form method="POST">
                <button type="submit" class="btn">Initialize Database</button>
            </form>
            
        <?php endif; ?>
    </div>
</body>
</html>
