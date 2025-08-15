<?php
// Test config loading with proper constant
define('VESSEL_LOGGER', true);

echo "Testing config loading...\n";

try {
    require_once __DIR__ . '/config/config.php';
    echo "✓ Config loaded successfully\n";
    
    require_once __DIR__ . '/includes/functions.php';
    echo "✓ Functions loaded successfully\n";
    
    // Test if functions exist
    if (function_exists('isSetupComplete')) {
        echo "✓ isSetupComplete function exists\n";
    } else {
        echo "✗ isSetupComplete function missing\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
