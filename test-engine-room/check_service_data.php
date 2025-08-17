<?php
require_once 'test_db.php';
$pdo = getTestDatabase();

echo "=== SERVICE ITEMS ===\n";
$stmt = $pdo->query('SELECT * FROM service_items');
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($items as $item) {
    echo "Code: " . $item['item_code'] . ", Name: " . $item['item_name'] . ", Category: " . $item['category'] . "\n";
}

echo "\n=== SERVICE SETTINGS ===\n";
$stmt = $pdo->query('SELECT * FROM service_settings WHERE equipment_type = "gearbox" ORDER BY equipment_id, service_item_code');
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($settings as $setting) {
    echo $setting['equipment_id'] . " - " . $setting['service_item_code'] . ": " . $setting['interval_hours'] . " hrs, enabled: " . ($setting['is_enabled'] ? 'YES' : 'NO') . "\n";
}

echo "\n=== SERVICE TRACKING ===\n";
$stmt = $pdo->query('SELECT * FROM service_tracking WHERE equipment_type = "gearbox"');
$tracking = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($tracking as $track) {
    echo $track['equipment_id'] . " - " . $track['service_item_code'] . ": last " . $track['last_service_hours'] . " hrs, next " . $track['next_service_hours'] . " hrs\n";
}
?>
