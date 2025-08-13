<?php
/**
 * Fix remaining require_once statements in vessel/offline folder
 */

$offline_path = __DIR__ . '/vessel/offline/';
$files = glob($offline_path . '*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    $filename = basename($file);
    
    echo "Processing: $filename\n";
    
    // Fix relative paths to use __DIR__
    $patterns = [
        "require_once '../../config.php';" => "require_once __DIR__ . '/../../config.php';",
        "require_once '../../auth_functions.php';" => "require_once __DIR__ . '/../../auth_functions.php';",
        "require_once '../../vessel_functions.php';" => "require_once __DIR__ . '/../../vessel_functions.php';",
        "require_once '../offline/sync_manager.php';" => "require_once __DIR__ . '/sync_manager.php';",
    ];
    
    $changed = false;
    foreach ($patterns as $old => $new) {
        if (strpos($content, $old) !== false) {
            $content = str_replace($old, $new, $content);
            $changed = true;
            echo "  - Fixed: $old\n";
        }
    }
    
    if ($changed) {
        file_put_contents($file, $content);
        echo "  ✓ Updated $filename\n";
    } else {
        echo "  - No changes needed\n";
    }
}

echo "\nDone fixing vessel/offline files!\n";
?>
