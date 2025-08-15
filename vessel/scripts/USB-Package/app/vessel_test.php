<?php
// Test vessel logger configuration and initialization
header('Content-Type: text/plain');

// Include the vessel logger config
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/database.php';

try {
    echo "Testing Vessel Logger Configuration\n";
    echo "==================================\n\n";
    
    // Test configuration loading
    echo "✓ Config loaded successfully\n";
    echo "Database path: " . DB_PATH . "\n";
    echo "Installation ID: " . INSTALLATION_ID . "\n\n";
    
    // Test database connection
    $pdo = getDatabaseConnection();
    echo "✓ Database connection successful\n";
    
    // Test table initialization
    initializeDatabase();
    echo "✓ Database tables initialized\n";
    
    // Check if setup is complete
    $isSetupComplete = checkSetupComplete();
    echo "Setup status: " . ($isSetupComplete ? "Complete" : "Pending") . "\n";
    
    // Test vessel configuration
    $vessel_config = getVesselConfig();
    if ($vessel_config) {
        echo "✓ Vessel configuration found\n";
        echo "Vessel Name: " . ($vessel_config['vessel_name'] ?? 'Not set') . "\n";
        echo "Company ID: " . ($vessel_config['company_id'] ?? 'Not set') . "\n";
    } else {
        echo "! Vessel configuration pending (normal for new install)\n";
    }
    
    // Test sync configuration
    $sync_config = getSyncConfig();
    if ($sync_config) {
        echo "✓ Sync configuration found\n";
        echo "Server URL: " . ($sync_config['server_url'] ?? 'Not set') . "\n";
        echo "Last sync: " . ($sync_config['last_sync'] ?? 'Never') . "\n";
    } else {
        echo "! Sync configuration pending (normal for new install)\n";
    }
    
    echo "\n✅ Vessel Logger ready for configuration!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
