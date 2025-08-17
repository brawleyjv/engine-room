<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== CHECKING TEST DATABASE TABLES ===\n";

// Get all tables
$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Tables in test database:\n";
foreach ($tables as $table) {
    echo "  $table\n";
}

// Check if we can see any generator-related data
echo "\n=== CHECKING FOR GENERATOR DATA ===\n";

if (in_array('generator_hours', $tables)) {
    echo "Generator Hours:\n";
    $stmt = $pdo->query("SELECT * FROM generator_hours LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
}

if (in_array('service_tracking', $tables)) {
    echo "\nService Tracking (generators):\n";
    $stmt = $pdo->query("SELECT * FROM service_tracking WHERE equipment_type = 'generator' LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
}

if (in_array('service_items', $tables)) {
    echo "\nService Items (generators):\n";
    $stmt = $pdo->query("SELECT * FROM service_items WHERE equipment_type = 'generator' LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
}
?>
