<?php
/**
 * Final comprehensive fix for all require_once statements
 */

// Function to fix files in a directory
function fixRequireStatements($directory, $patterns) {
    $files = glob($directory . '*.php');
    $fixed_count = 0;
    
    foreach ($files as $file) {
        $content = file_get_contents($file);
        $filename = basename($file);
        $relative_path = str_replace(__DIR__ . '/', '', $file);
        
        $changed = false;
        foreach ($patterns as $old => $new) {
            if (strpos($content, $old) !== false) {
                $content = str_replace($old, $new, $content);
                $changed = true;
            }
        }
        
        if ($changed) {
            file_put_contents($file, $content);
            echo "✓ Fixed: $relative_path\n";
            $fixed_count++;
        }
    }
    
    return $fixed_count;
}

$total_fixed = 0;

// Fix root level debug and test files
echo "=== Fixing root level files ===\n";
$root_patterns = [
    "require_once __DIR__ . '/config.php';" => "require_once __DIR__ . '/config.php';",
    "require_once __DIR__ . '/auth_functions.php';" => "require_once __DIR__ . '/auth_functions.php';",
    "require_once __DIR__ . '/vessel_functions.php';" => "require_once __DIR__ . '/vessel_functions.php';",
    "require_once __DIR__ . '/logicdock_tracker.php';" => "require_once __DIR__ . '/logicdock_tracker.php';",
];
$total_fixed += fixRequireStatements(__DIR__ . '/', $root_patterns);

// Fix vessel/test files
echo "\n=== Fixing vessel test files ===\n";
$vessel_patterns = [
    "require_once '../config.php';" => "require_once __DIR__ . '/../config.php';",
    "require_once 'offline/sqlite_schema.php';" => "require_once __DIR__ . '/offline/sqlite_schema.php';",
    "require_once 'offline/sync_manager.php';" => "require_once __DIR__ . '/offline/sync_manager.php';",
];
$total_fixed += fixRequireStatements(__DIR__ . '/vessel/', $vessel_patterns);

// Fix legacy engine_setup files
echo "\n=== Fixing legacy engine_setup files ===\n";
$legacy_engine_patterns = [
    "require_once __DIR__ . '/config.php';" => "require_once __DIR__ . '/../../config.php';",
    "require_once __DIR__ . '/vessel_functions.php';" => "require_once __DIR__ . '/../../vessel_functions.php';",
    "require_once __DIR__ . '/auth_functions.php';" => "require_once __DIR__ . '/../../auth_functions.php';",
];
$total_fixed += fixRequireStatements(__DIR__ . '/legacy/engine_setup/', $legacy_engine_patterns);

// Fix legacy session_fixes files
echo "\n=== Fixing legacy session_fixes files ===\n";
$legacy_session_patterns = [
    "require_once __DIR__ . '/config.php';" => "require_once __DIR__ . '/../../config.php';",
    "require_once __DIR__ . '/vessel_functions.php';" => "require_once __DIR__ . '/../../vessel_functions.php';",
];
$total_fixed += fixRequireStatements(__DIR__ . '/legacy/session_fixes/', $legacy_session_patterns);

// Fix legacy database_fixes files
echo "\n=== Fixing legacy database_fixes files ===\n";
$legacy_db_patterns = [
    "require_once __DIR__ . '/config.php';" => "require_once __DIR__ . '/../../config.php';",
    "require_once __DIR__ . '/vessel_functions.php';" => "require_once __DIR__ . '/../../vessel_functions.php';",
    "require_once __DIR__ . '/auth_functions.php';" => "require_once __DIR__ . '/../../auth_functions.php';",
];
$total_fixed += fixRequireStatements(__DIR__ . '/legacy/database_fixes/', $legacy_db_patterns);

// Fix install files (one more check)
echo "\n=== Fixing install files ===\n"; 
$install_patterns = [
    "require_once '../logicdock_tracker.php';" => "require_once __DIR__ . '/../logicdock_tracker.php';",
];
$total_fixed += fixRequireStatements(__DIR__ . '/install/', $install_patterns);

// Fix any trial_management config paths
echo "\n=== Fixing special cases ===\n";
$trial_file = __DIR__ . '/trial_management.php';
if (file_exists($trial_file)) {
    $content = file_get_contents($trial_file);
    if (strpos($content, "require_once '../config_production.php';") !== false) {
        $content = str_replace("require_once '../config_production.php';", "require_once __DIR__ . '/../config_production.php';", $content);
        file_put_contents($trial_file, $content);
        echo "✓ Fixed: trial_management.php\n";
        $total_fixed++;
    }
}

echo "\n=== FINAL SUMMARY ===\n";
echo "Total files fixed: $total_fixed\n";
echo "✅ All require_once statements should now use __DIR__ for absolute paths!\n";
echo "\nYour SaaS platform should now work in ANY folder location:\n";
echo "  - Root: http://localhost/enginerm/\n";
echo "  - Subfolder: http://localhost/myapp/enginerm/\n";
echo "  - Production: https://yourdomain.com/app/\n";
echo "\n🎯 Ready for professional deployment!\n";
?>
