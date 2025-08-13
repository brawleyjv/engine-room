<?php
/**
 * File Audit and Categorization Script
 * Analyzes current codebase and categorizes files for modernization
 */

$base_dir = __DIR__;
$categories = [
    'keep_modern' => [],      // Already modernized, keep as-is
    'keep_migrate' => [],     // Keep but needs updating for new structure
    'office_legacy' => [],    // Old office files to integrate
    'development' => [],      // Debug/test files for dev environment
    'deprecated' => [],       // Can be safely removed
    'config' => [],          // Configuration files
    'documentation' => []    // Documentation and guides
];

// File patterns for categorization
$patterns = [
    'keep_modern' => [
        'office_dashboard.php',
        'welcome.php', 
        'dashboard_nav.php',
        'vessel/engineroom/*.php'
    ],
    'keep_migrate' => [
        'index.php',
        'login.php',
        'logout.php',
        'auth_functions.php',
        'vessel_functions.php',
        'license_manager.php',
        'manage_*.php',
        'select_vessel.php',
        'switch_vessel.php'
    ],
    'office_legacy' => [
        'office/*.php'
    ],
    'development' => [
        'debug_*.php',
        'test_*.php',
        'comprehensive_*.php'
    ],
    'deprecated' => [
        'fix_*.php',
        'setup_*.php',
        'quick_*.php',
        'web_session_fix.php',
        '*backup.php'
    ],
    'config' => [
        'config*.php',
        '*.sql',
        '*.sh'
    ],
    'documentation' => [
        '*.md',
        'README*',
        '*.txt'
    ]
];

function scanDirectory($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath);
            $files[] = $relativePath;
        }
    }
    
    return $files;
}

function categorizeFile($filename, $patterns) {
    foreach ($patterns as $category => $categoryPatterns) {
        foreach ($categoryPatterns as $pattern) {
            if (fnmatch($pattern, $filename)) {
                return $category;
            }
        }
    }
    return 'uncategorized';
}

// Scan all files
$all_files = scanDirectory($base_dir);

// Categorize files
$results = [
    'keep_modern' => [],
    'keep_migrate' => [],
    'office_legacy' => [],
    'development' => [],
    'deprecated' => [],
    'config' => [],
    'documentation' => [],
    'uncategorized' => []
];

foreach ($all_files as $file) {
    // Skip certain directories
    if (strpos($file, '.git/') === 0 || 
        strpos($file, 'install/') === 0 ||
        strpos($file, 'admin/') === 0) {
        continue;
    }
    
    $category = categorizeFile($file, $patterns);
    $results[$category][] = $file;
}

// Generate report
echo "# File Audit Report - " . date('Y-m-d H:i:s') . "\n\n";

foreach ($results as $category => $files) {
    if (empty($files)) continue;
    
    echo "## " . strtoupper(str_replace('_', ' ', $category)) . " (" . count($files) . " files)\n\n";
    
    foreach ($files as $file) {
        $action = getRecommendedAction($category, $file);
        echo "- `{$file}` - {$action}\n";
    }
    echo "\n";
}

function getRecommendedAction($category, $file) {
    switch ($category) {
        case 'keep_modern':
            return "✅ Keep as-is (already modernized)";
        case 'keep_migrate':
            return "🔄 Update paths and integrate with new structure";
        case 'office_legacy':
            return "🏢 Migrate functionality to new office_dashboard.php";
        case 'development':
            return "🔧 Move to development/ folder";
        case 'deprecated':
            return "❌ Archive or remove (no longer needed)";
        case 'config':
            return "⚙️ Review and consolidate";
        case 'documentation':
            return "📚 Keep and update";
        default:
            return "❓ Needs manual review";
    }
}

// Generate migration commands
echo "## Recommended Actions\n\n";
echo "### 1. Create new directory structure:\n";
echo "```bash\n";
echo "mkdir -p development\n";
echo "mkdir -p archive\n";
echo "mkdir -p vessel/offline\n";
echo "mkdir -p office/api\n";
echo "```\n\n";

echo "### 2. Move development files:\n";
echo "```bash\n";
foreach ($results['development'] as $file) {
    echo "mv \"{$file}\" development/\n";
}
echo "```\n\n";

echo "### 3. Archive deprecated files:\n";
echo "```bash\n";
foreach ($results['deprecated'] as $file) {
    echo "mv \"{$file}\" archive/\n";
}
echo "```\n\n";

echo "### 4. Files requiring manual migration:\n";
foreach ($results['keep_migrate'] as $file) {
    echo "- {$file}\n";
}
echo "\n";

echo "### 5. Office files to integrate:\n";
foreach ($results['office_legacy'] as $file) {
    echo "- {$file}\n";
}
?>
