<?php
/**
 * Check Database Configuration
 * Check which database files exist and are being used
 */

require_once 'test_db.php';

echo "<h2>Database Configuration Check</h2>\n";

// Check current working directory
echo "<p><strong>Current Directory:</strong> " . __DIR__ . "</p>\n";
echo "<p><strong>Current Working Directory:</strong> " . getcwd() . "</p>\n";

// Check database file paths
$db_files = [
    'test_engine.db' => __DIR__ . '/test_engine.db',
    'vessel.db (vessel-dev)' => dirname(__DIR__) . '/vessel-dev/data/vessel.db',
];

echo "<h3>Database Files:</h3>\n";
foreach ($db_files as $name => $path) {
    echo "<p><strong>$name:</strong> $path</p>\n";
    if (file_exists($path)) {
        echo "<p>✓ File exists (size: " . filesize($path) . " bytes)</p>\n";
        
        // Try to connect and get table info
        try {
            $pdo = new PDO("sqlite:$path");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
            echo "<p>Tables: " . implode(', ', $tables) . "</p>\n";
            
        } catch (Exception $e) {
            echo "<p>❌ Error connecting: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p>❌ File does not exist</p>\n";
    }
    echo "<hr>\n";
}

// Check current database connection from test_db.php
echo "<h3>Current Database Connection:</h3>\n";
try {
    $pdo = getTestDatabase();
    echo "<p>✓ Successfully connected to test database</p>\n";
    
    // Get database file path
    $db_info = $pdo->query("PRAGMA database_list")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($db_info as $db) {
        echo "<p><strong>Database:</strong> {$db['name']} - {$db['file']}</p>\n";
    }
    
    // Check tables
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Tables:</strong> " . implode(', ', $tables) . "</p>\n";
    
} catch (Exception $e) {
    echo "<p>❌ Error with current database: " . $e->getMessage() . "</p>\n";
}

// Check server info
echo "<h3>Server Information:</h3>\n";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>\n";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>\n";
echo "<p><strong>Server Port:</strong> " . ($_SERVER['SERVER_PORT'] ?? 'Unknown') . "</p>\n";
echo "<p><strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "</p>\n";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>\n";
?>
