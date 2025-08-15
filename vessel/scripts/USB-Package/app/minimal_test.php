<?php
// Minimal config test
define('VESSEL_LOGGER', true);

echo "Step 1: Constant defined\n";

try {
    include __DIR__ . '/config/config.php';
    echo "Step 2: Config included\n";
    echo "Step 3: VESSEL_ROOT = " . (defined('VESSEL_ROOT') ? VESSEL_ROOT : 'NOT DEFINED') . "\n";
} catch (Exception $e) {
    echo "Error in config: " . $e->getMessage() . "\n";
}
?>
