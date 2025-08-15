<?php
$pdo = new PDO('sqlite:vessel_data.sqlite');

echo "Current database schema:\n";
echo "========================\n";

$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();

foreach($tables as $table) {
    echo "\nTable: " . $table[0] . "\n";
    echo str_repeat('-', strlen($table[0]) + 7) . "\n";
    
    $columns = $pdo->query("PRAGMA table_info(" . $table[0] . ")")->fetchAll();
    foreach($columns as $col) {
        echo "  " . $col[1] . " (" . $col[2] . ")" . ($col[5] ? " PRIMARY KEY" : "") . "\n";
    }
}

echo "\nChecking for specific columns:\n";
echo "==============================\n";

// Check navigation_data table specifically
try {
    $result = $pdo->query("SELECT * FROM navigation_data LIMIT 1");
    echo "✅ navigation_data table exists and accessible\n";
} catch (Exception $e) {
    echo "❌ navigation_data error: " . $e->getMessage() . "\n";
}

// Check vessel_logs table
try {
    $result = $pdo->query("SELECT * FROM vessel_logs LIMIT 1");
    echo "✅ vessel_logs table exists and accessible\n";
} catch (Exception $e) {
    echo "❌ vessel_logs error: " . $e->getMessage() . "\n";
}

// Check crew_members table
try {
    $result = $pdo->query("SELECT * FROM crew_members LIMIT 1");
    echo "✅ crew_members table exists and accessible\n";
} catch (Exception $e) {
    echo "❌ crew_members error: " . $e->getMessage() . "\n";
}
?>
