<?php
/**
 * Debug Engine Management Specific Issue
 * Check table structures for engine_type column
 */

require_once 'test_db.php';

try {
    $pdo = getTestDatabase();
    echo "<h2>Checking Tables for engine_type Column</h2>\n";

    // Check engine_hours table
    echo "<h3>engine_hours table:</h3>\n";
    try {
        $result = $pdo->query("PRAGMA table_info(engine_hours)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        $has_engine_type = false;
        foreach ($columns as $column) {
            echo "<p>{$column['name']} ({$column['type']})</p>\n";
            if ($column['name'] === 'engine_type') {
                $has_engine_type = true;
            }
        }
        echo $has_engine_type ? "<p>✅ engine_type column exists</p>\n" : "<p>❌ engine_type column MISSING</p>\n";
        
        // Test the specific query
        $stmt = $pdo->prepare("SELECT * FROM engine_hours WHERE engine_type = ?");
        $stmt->execute(['port_main']);
        echo "<p>✅ Query 'SELECT * FROM engine_hours WHERE engine_type = ?' works</p>\n";
    } catch (Exception $e) {
        echo "<p>❌ Error with engine_hours: " . $e->getMessage() . "</p>\n";
    }

    // Check engine_readings table
    echo "<h3>engine_readings table:</h3>\n";
    try {
        $result = $pdo->query("PRAGMA table_info(engine_readings)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        $has_engine_type = false;
        foreach ($columns as $column) {
            echo "<p>{$column['name']} ({$column['type']})</p>\n";
            if ($column['name'] === 'engine_type') {
                $has_engine_type = true;
            }
        }
        echo $has_engine_type ? "<p>✅ engine_type column exists</p>\n" : "<p>❌ engine_type column MISSING</p>\n";
        
        // Test the specific query
        $stmt = $pdo->prepare("SELECT * FROM engine_readings WHERE engine_type = ? ORDER BY reading_date DESC LIMIT 10");
        $stmt->execute(['port_main']);
        echo "<p>✅ Query 'SELECT * FROM engine_readings WHERE engine_type = ?' works</p>\n";
    } catch (Exception $e) {
        echo "<p>❌ Error with engine_readings: " . $e->getMessage() . "</p>\n";
    }

    // Test the specific lines from manage_engine.php
    echo "<h3>Testing Exact Lines from manage_engine.php:</h3>\n";
    
    $engine_type = 'port_main';
    
    try {
        // Line 81-82
        $stmt = $pdo->prepare("SELECT * FROM engine_hours WHERE engine_type = ?");
        $stmt->execute([$engine_type]);
        $engine_data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>✅ Line 81-82: engine_hours query successful</p>\n";
    } catch (Exception $e) {
        echo "<p>❌ Line 81-82 ERROR: " . $e->getMessage() . "</p>\n";
    }
    
    try {
        // Line 86-87
        $stmt = $pdo->prepare("SELECT * FROM engine_readings WHERE engine_type = ? ORDER BY reading_date DESC LIMIT 10");
        $stmt->execute([$engine_type]);
        $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>✅ Line 86-87: engine_readings query successful</p>\n";
    } catch (Exception $e) {
        echo "<p>❌ Line 86-87 ERROR: " . $e->getMessage() . "</p>\n";
    }

} catch (Exception $e) {
    echo "<p>❌ Database connection error: " . $e->getMessage() . "</p>\n";
}

echo "<hr><p><strong>Current database file path:</strong> " . __DIR__ . "/test_engine.db</p>\n";
echo "<p><strong>File exists:</strong> " . (file_exists(__DIR__ . "/test_engine.db") ? "YES" : "NO") . "</p>\n";
echo "<p><strong>File size:</strong> " . filesize(__DIR__ . "/test_engine.db") . " bytes</p>\n";
?>
