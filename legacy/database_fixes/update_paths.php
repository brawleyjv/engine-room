<?php
// Script to update file paths in moved engine room files

$engineroom_dir = 'C:\xampp\htdocs\enginerm\vessel\engineroom';
$files = glob($engineroom_dir . '/*.php');

foreach ($files as $file) {
    echo "Processing: " . basename($file) . "\n";
    
    $content = file_get_contents($file);
    $original_content = $content;
    
    // Update require/include paths
    $content = str_replace("require_once __DIR__ . '/../../config.php';", "require_once '../../config.php';", $content);
    $content = str_replace("require_once __DIR__ . '/../../auth_functions.php';", "require_once '../../auth_functions.php';", $content);
    $content = str_replace("require_once __DIR__ . '/../../vessel_functions.php';", "require_once '../../vessel_functions.php';", $content);
    $content = str_replace("require_once 'license_manager.php';", "require_once '../../license_manager.php';", $content);
    $content = str_replace("include_once 'config.php';", "include_once '../../config.php';", $content);
    
    // Update CSS and favicon paths
    $content = str_replace('href="style.css"', 'href="../../style.css"', $content);
    $content = str_replace('href="favicon.svg"', 'href="../../favicon.svg"', $content);
    $content = str_replace('href="favicon.ico"', 'href="../../favicon.ico"', $content);
    
    // Update main navigation links (but not internal engine room links)
    $content = str_replace('href="index.php"', 'href="../../index.php"', $content);
    $content = str_replace('href="login.php"', 'href="../../login.php"', $content);
    $content = str_replace('href="logout.php"', 'href="../../logout.php"', $content);
    $content = str_replace('href="manage_vessels.php"', 'href="../../manage_vessels.php"', $content);
    $content = str_replace('href="manage_users.php"', 'href="../../manage_users.php"', $content);
    $content = str_replace('href="select_vessel.php"', 'href="../../select_vessel.php"', $content);
    $content = str_replace('href="switch_vessel.php"', 'href="../../switch_vessel.php"', $content);
    
    // Update API calls that should stay relative
    $content = str_replace("'get_equipment_sides.php", "'get_equipment_sides.php", $content);
    $content = str_replace("'get_engine_data.php", "'get_engine_data.php", $content);
    
    // Write back if changed
    if ($content !== $original_content) {
        file_put_contents($file, $content);
        echo "  ✓ Updated\n";
    } else {
        echo "  - No changes needed\n";
    }
}

echo "\nAll files processed!\n";
?>
