<?php
/**
 * LogicDock Tracking System Setup
 * 
 * This script sets up the complete LogicDock tracking infrastructure:
 * 1. Creates all necessary database tables
 * 2. Sets up the master database
 * 3. Configures the tracking system
 * 4. Tests the notification system
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logicdock_tracker.php';

echo "<h1>🚢 LogicDock Tracking System Setup</h1>\n";
echo "<style>body{font-family:sans-serif;max-width:800px;margin:40px auto;padding:20px;line-height:1.6;} .success{color:#27ae60;} .error{color:#e74c3c;} .warning{color:#f39c12;} pre{background:#f8f9fa;padding:15px;border-radius:8px;overflow-x:auto;}</style>";

function log_step($message, $type = 'info') {
    $colors = [
        'success' => '#27ae60',
        'error' => '#e74c3c', 
        'warning' => '#f39c12',
        'info' => '#3498db'
    ];
    
    $color = $colors[$type] ?? $colors['info'];
    echo "<p style='color: $color;'><strong>$message</strong></p>\n";
    flush();
}

try {
    // Step 1: Connect to MySQL server
    log_step("Step 1: Connecting to MySQL server...");
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    log_step("✅ Connected to MySQL server successfully", 'success');
    
    // Step 2: Create master database if it doesn't exist
    log_step("Step 2: Creating master database...");
    $pdo->exec("CREATE DATABASE IF NOT EXISTS vessellogger_master CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE vessellogger_master");
    log_step("✅ Master database ready", 'success');
    
    // Step 3: Run the database setup script
    log_step("Step 3: Creating tracking tables...");
    $sql_file = __DIR__ . '/logicdock_tracking_tables.sql';
    
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        
        // Remove comments and split by semicolon
        $sql_content = preg_replace('/--.*$/m', '', $sql_content);
        $sql_content = preg_replace('/#.*$/m', '', $sql_content);
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Skip if table already exists
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }
        }
        log_step("✅ Tracking tables created successfully", 'success');
    } else {
        log_step("⚠️ SQL file not found, creating tables manually...", 'warning');
        createTablesManually($pdo);
    }
    
    // Step 4: Initialize module definitions
    log_step("Step 4: Setting up module definitions...");
    setupModuleDefinitions($pdo);
    log_step("✅ Module definitions configured", 'success');
    
    // Step 5: Create sample customer for testing
    log_step("Step 5: Creating test customer...");
    $test_customer_id = createTestCustomer($pdo);
    log_step("✅ Test customer created: $test_customer_id", 'success');
    
    // Step 6: Test the tracking system
    log_step("Step 6: Testing tracking system...");
    $tracker = new LogicDockTracker($pdo);
    $tracker->trackEvent($test_customer_id, 'trial_started', [
        'company_name' => 'Test Vessel Company',
        'database_name' => 'TST_vessellogger',
        'setup_test' => true
    ]);
    log_step("✅ Tracking system test successful", 'success');
    
    // Step 7: Test notification sending
    log_step("Step 7: Testing notification system...");
    $sent_count = $tracker->sendPendingNotifications();
    log_step("✅ Sent $sent_count test notifications", 'success');
    
    // Step 8: Generate sample statistics
    log_step("Step 8: Generating initial statistics...");
    generateInitialStats($pdo);
    log_step("✅ Initial statistics generated", 'success');
    
    // Step 9: Create logs directory
    log_step("Step 9: Setting up logging directory...");
    $logs_dir = __DIR__ . '/logs';
    if (!file_exists($logs_dir)) {
        mkdir($logs_dir, 0755, true);
    }
    file_put_contents("$logs_dir/logicdock_setup.log", date('Y-m-d H:i:s') . " - LogicDock tracking system setup completed\n", FILE_APPEND);
    log_step("✅ Logging directory configured", 'success');
    
    echo "<hr>";
    echo "<h2 style='color: #27ae60;'>🎉 LogicDock Tracking System Setup Complete!</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Visit the <a href='logicdock_dashboard.php?access_key=logicdock_admin_2024' target='_blank'>LogicDock Dashboard</a></li>";
    echo "<li>✅ Set up cron job: <code>*/15 * * * * /usr/bin/php " . __DIR__ . "/logicdock_cron.php</code></li>";
    echo "<li>✅ Configure your LogicDock API credentials in <code>logicdock_tracker.php</code></li>";
    echo "<li>✅ Test customer onboarding with the <a href='install/' target='_blank'>Installation Wizard</a></li>";
    echo "</ul>";
    
    echo "<h3>📊 System Status:</h3>";
    $stats = $pdo->query("SELECT * FROM logicdock_dashboard")->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    echo "Total Customers: " . ($stats['total_customers'] ?? 0) . "\n";
    echo "Active Trials: " . ($stats['active_trials'] ?? 0) . "\n";
    echo "Paying Customers: " . ($stats['paying_customers'] ?? 0) . "\n";
    echo "Signups Today: " . ($stats['signups_today'] ?? 0) . "\n";
    echo "</pre>";
    
} catch (Exception $e) {
    log_step("❌ Setup failed: " . $e->getMessage(), 'error');
    echo "<pre style='background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'>";
    echo "Error Details:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}

function createTablesManually($pdo) {
    // Create essential tables if SQL file is missing
    $tables = [
        "CREATE TABLE IF NOT EXISTS customer_licenses (
            customer_id VARCHAR(50) PRIMARY KEY,
            company_name VARCHAR(255) NOT NULL,
            admin_email VARCHAR(255) NOT NULL,
            plan ENUM('trial', 'basic', 'professional', 'enterprise') DEFAULT 'trial',
            status ENUM('active', 'suspended', 'canceled') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            database_name VARCHAR(100) NULL,
            database_prefix VARCHAR(4) NULL
        )",
        
        "CREATE TABLE IF NOT EXISTS logicdock_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id VARCHAR(50) NOT NULL,
            event_type ENUM('trial_started', 'subscription_started', 'subscription_canceled') NOT NULL,
            event_data JSON,
            sent_to_logicdock BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS module_definitions (
            module_code VARCHAR(50) PRIMARY KEY,
            module_name VARCHAR(255) NOT NULL,
            category ENUM('basic', 'premium') DEFAULT 'basic',
            monthly_price DECIMAL(10,2) DEFAULT 0.00,
            description TEXT
        )"
    ];
    
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
}

function setupModuleDefinitions($pdo) {
    $modules = [
        ['engine_logging', 'Engine Performance Logging', 'basic', 0.00, 'Track engine hours, RPM, temperature, and maintenance'],
        ['fuel_monitoring', 'Fuel Monitoring System', 'basic', 0.00, 'Monitor fuel consumption and efficiency'],
        ['gps_tracking', 'GPS Vessel Tracking', 'premium', 29.99, 'Real-time GPS tracking and route history'],
        ['maintenance_alerts', 'Maintenance Alert System', 'premium', 19.99, 'Automated maintenance scheduling and alerts'],
        ['crew_management', 'Crew Management', 'premium', 39.99, 'Crew scheduling, certification tracking, and compliance'],
        ['advanced_analytics', 'Advanced Analytics Dashboard', 'premium', 49.99, 'AI-powered insights and predictive analytics'],
        ['api_integration', 'API Integration & Sync', 'premium', 24.99, 'Sync data with external systems and APIs'],
        ['multi_vessel', 'Multi-Vessel Management', 'premium', 19.99, 'Manage multiple vessels from one account']
    ];
    
    foreach ($modules as $module) {
        $sql = "INSERT INTO module_definitions (module_code, module_name, category, monthly_price, description) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                module_name = VALUES(module_name),
                category = VALUES(category),
                monthly_price = VALUES(monthly_price),
                description = VALUES(description)";
        $pdo->prepare($sql)->execute($module);
    }
}

function createTestCustomer($pdo) {
    $test_id = 'TST' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    $sql = "INSERT INTO customer_licenses 
            (customer_id, company_name, admin_email, plan, database_name, database_prefix, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE customer_id = customer_id";
    
    $pdo->prepare($sql)->execute([
        $test_id,
        'Test Vessel Company',
        'test@example.com',
        'trial',
        $test_id . '_vessellogger',
        substr($test_id, 0, 3),
        date('Y-m-d H:i:s', strtotime('+14 days'))
    ]);
    
    return $test_id;
}

function generateInitialStats($pdo) {
    $today = date('Y-m-d');
    
    $sql = "INSERT INTO daily_statistics 
            (stat_date, total_customers, active_trials, active_subscriptions, new_trials_today) 
            VALUES (?, 1, 1, 0, 1)
            ON DUPLICATE KEY UPDATE
            total_customers = VALUES(total_customers),
            active_trials = VALUES(active_trials)";
    
    $pdo->prepare($sql)->execute([$today]);
}
?>
