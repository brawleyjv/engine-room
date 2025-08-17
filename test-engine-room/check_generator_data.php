<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== CHECKING GENERATOR DATA ===\n";

// Check generators
echo "Generators:\n";
$stmt = $pdo->query("SELECT * FROM generators");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['generator_type']}: {$row['name']} (enabled: {$row['enabled']})\n";
}

// Check generator hours
echo "\nGenerator Hours:\n";
$stmt = $pdo->query("SELECT * FROM generator_hours");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['generator_type']}: {$row['total_hours']} hours\n";
}

// Check service tracking for generators
echo "\nService Tracking (generators):\n";
$stmt = $pdo->query("SELECT * FROM service_tracking WHERE equipment_type = 'generator'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['equipment_id']}, {$row['service_item_code']}: next at {$row['next_service_hours']}\n";
}

// Check service items for generators
echo "\nService Items (generators):\n";
$stmt = $pdo->query("SELECT * FROM service_items WHERE equipment_type = 'generator'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['item_code']}: {$row['item_name']}\n";
}
?>
