<?php
require_once 'test_db.php';
$pdo = getTestDatabase();

echo "Oil Filter service settings:\n";
$stmt = $pdo->query('SELECT * FROM service_settings WHERE service_item_code = "generator_oil_filter"');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  " . $row['equipment_id'] . ": " . $row['interval_hours'] . " hrs, enabled: " . ($row['is_enabled'] ? 'YES' : 'NO') . "\n";
}

echo "\nOil Filter tracking:\n";
$stmt = $pdo->query('SELECT * FROM service_tracking WHERE service_item_code = "generator_oil_filter"');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  " . $row['equipment_id'] . ": last " . $row['last_service_hours'] . " hrs, next " . $row['next_service_hours'] . " hrs\n";
}
?>
