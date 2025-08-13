<?php
/**
 * Update all require_once statements to use __DIR__ for dynamic paths
 */

$rootDir = __DIR__;

function updateRequireStatements($directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
    $phpFiles = new RegexIterator($iterator, '/\.php$/', RecursiveRegexIterator::GET_MATCH);
    
    foreach ($phpFiles as $file) {
        $filePath = $file[0];
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Skip this update script
        if (basename($filePath) === 'update_require_paths.php') {
            continue;
        }
        
        // Skip files that already use __DIR__
        if (strpos($content, "__DIR__") !== false) {
            continue;
        }
        
        // Update different patterns of require_once statements
        $patterns = [
            // Basic relative paths from root
            "/require_once\s+['\"]([^'\"]+\.php)['\"];/",
            // Relative paths with ../
            "/require_once\s+['\"]\.\.\/([^'\"]+\.php)['\"];/",
            // Relative paths with ../../
            "/require_once\s+['\"]\.\.\/\.\.\/([^'\"]+\.php)['\"];/",
        ];
        
        $updated = false;
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $originalRequire = $match[0];
                    $filePath = $match[1];
                    
                    // Determine the relative path from current file to target
                    $currentDir = dirname($file[0]);
                    $targetPath = $filePath;
                    
                    // Handle different cases
                    if (strpos($originalRequire, '../..') !== false) {
                        $newRequire = "require_once __DIR__ . '/../../" . $targetPath . "';";
                    } elseif (strpos($originalRequire, '../') !== false) {
                        $newRequire = "require_once __DIR__ . '/../" . $targetPath . "';";
                    } else {
                        $newRequire = "require_once __DIR__ . '/" . $targetPath . "';";
                    }
                    
                    $content = str_replace($originalRequire, $newRequire, $content);
                    $updated = true;
                }
            }
        }
        
        if ($updated && $content !== $originalContent) {
            file_put_contents($file[0], $content);
            echo "Updated: " . $file[0] . "\n";
        }
    }
}

echo "Starting require_once path updates...\n";
updateRequireStatements($rootDir);
echo "Done!\n";
?>
