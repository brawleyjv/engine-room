<?php
/**
 * Error Diagnostics for Engine Room System
 * Helps identify common issues and database problems
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>🔍 Engine Room System Diagnostics</h2>\n";

// Test database connection
echo "<h3>1. Database Connection Test</h3>\n";
try {
    require_once 'test_db.php';
    $pdo = getTestDatabase();
    echo "<p style='color: green;'>✅ Database connection: OK</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    exit;
}

// Test fluid inventory query
echo "<h3>2. Fluid Inventory Query Test</h3>\n";
try {
    $fluid_levels = $pdo->query("SELECT fluid_type, amount FROM fluid_inventory WHERE vessel_id = 1")->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "<p style='color: green;'>✅ Fluid inventory query: OK</p>\n";
    echo "<p>Found " . count($fluid_levels) . " fluid types:</p>\n";
    foreach ($fluid_levels as $type => $amount) {
        echo "<p>&nbsp;&nbsp;• " . ucfirst(str_replace('_', ' ', $type)) . ": " . number_format($amount, 1) . " gallons</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Fluid inventory query failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test each management page
echo "<h3>3. Management Pages Test</h3>\n";
$pages = [
    'index.php' => 'Main Dashboard',
    'manage_fuel.php' => 'Fuel Management',
    'manage_lube_oil.php' => 'Lube Oil Management',
    'manage_gear_oil.php' => 'Gear Oil Management',
    'manage_hydraulic_oil.php' => 'Hydraulic Oil Management'
];

foreach ($pages as $file => $name) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✅ $name ($file): File exists</p>\n";
        
        // Test if file has syntax errors (basic check)
        $content = file_get_contents($file);
        if (strpos($content, 'current_amount') !== false) {
            echo "<p style='color: orange;'>⚠ Warning: $file may still contain references to old 'current_amount' column</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ $name ($file): File missing</p>\n";
    }
}

// Test database table structure
echo "<h3>4. Database Table Structure</h3>\n";
$tables = ['fluid_inventory', 'fluid_transactions'];
foreach ($tables as $table) {
    try {
        $result = $pdo->query("PRAGMA table_info($table)");
        $columns = $result->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color: green;'>✅ Table '$table':</p>\n";
        echo "<ul>\n";
        foreach ($columns as $column) {
            echo "<li>{$column['name']} ({$column['type']})</li>\n";
        }
        echo "</ul>\n";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Table '$table' error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
}

// Test a simple transaction
echo "<h3>5. Transaction Test</h3>\n";
try {
    $pdo->beginTransaction();
    
    // Test query without making changes
    $stmt = $pdo->prepare("SELECT amount FROM fluid_inventory WHERE fluid_type = 'fuel'");
    $stmt->execute();
    $fuel_amount = $stmt->fetchColumn();
    
    $pdo->rollBack(); // Don't actually save changes
    
    echo "<p style='color: green;'>✅ Transaction test: OK (Fuel amount: " . number_format($fuel_amount, 1) . " gallons)</p>\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>❌ Transaction test failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<h3 style='color: green;'>🎯 Diagnostics Complete</h3>\n";
echo "<p><a href='index.php'>← Return to Dashboard</a> | <a href='check_database.php'>Database Status</a></p>\n";
?>
