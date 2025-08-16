<?php
/**
 * Debug script for engine management database issues
 */

require_once 'test_db.php';
require_once 'settings_helper.php';

echo "<h2>Debug Information for Engine Management</h2>";
echo "<hr>";

// Check current working directory
echo "<h3>1. File Paths</h3>";
echo "Current working directory: " . getcwd() . "<br>";
echo "Script file: " . __FILE__ . "<br>";
echo "test_db.php exists: " . (file_exists('test_db.php') ? 'YES' : 'NO') . "<br>";
echo "settings_helper.php exists: " . (file_exists('settings_helper.php') ? 'YES' : 'NO') . "<br>";

// Test database connection
echo "<h3>2. Database Connection</h3>";
try {
    $pdo = getTestDatabase();
    echo "✅ Database connection successful<br>";
    
    // Check database file
    $db_file = $pdo->query("PRAGMA database_list")->fetchAll(PDO::FETCH_ASSOC);
    echo "Database file: <pre>" . print_r($db_file, true) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test engine_hours table
echo "<h3>3. Engine Hours Table</h3>";
try {
    $pdo = getTestDatabase();
    
    // Check if table exists
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='engine_hours'")->fetchAll();
    if (empty($tables)) {
        echo "❌ engine_hours table does NOT exist<br>";
    } else {
        echo "✅ engine_hours table exists<br>";
        
        // Check table structure
        $structure = $pdo->query("PRAGMA table_info(engine_hours)")->fetchAll(PDO::FETCH_ASSOC);
        echo "Table structure:<br>";
        foreach ($structure as $col) {
            echo "  - {$col['name']} ({$col['type']})<br>";
        }
        
        // Check data
        $count = $pdo->query("SELECT COUNT(*) FROM engine_hours")->fetchColumn();
        echo "Records in table: $count<br>";
        
        if ($count > 0) {
            $sample = $pdo->query("SELECT * FROM engine_hours LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            echo "Sample record: <pre>" . print_r($sample, true) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error checking engine_hours table: " . $e->getMessage() . "<br>";
}

// Test the specific query
echo "<h3>4. Test Specific Query</h3>";
try {
    $pdo = getTestDatabase();
    $engine_type = 'port_main';
    
    echo "Testing query: SELECT * FROM engine_hours WHERE engine_type = '$engine_type'<br>";
    
    $stmt = $pdo->prepare("SELECT * FROM engine_hours WHERE engine_type = ?");
    $stmt->execute([$engine_type]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "✅ Query successful<br>";
        echo "Result: <pre>" . print_r($result, true) . "</pre>";
    } else {
        echo "⚠️ Query returned no results<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "<br>";
}

// Test settings
echo "<h3>5. Settings Test</h3>";
try {
    $vessel_settings = getVesselSettings();
    echo "✅ Settings loaded successfully<br>";
    echo "Settings: <pre>" . print_r($vessel_settings, true) . "</pre>";
    
} catch (Exception $e) {
    echo "❌ Settings failed: " . $e->getMessage() . "<br>";
}

?>
