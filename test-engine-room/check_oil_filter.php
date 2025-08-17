<?php
require_once 'test_db.php';
$pdo = getTestDatabase();

echo "Checking for Oil Filter service item:\n";
$stmt = $pdo->prepare('SELECT * FROM service_items WHERE item_code = "generator_oil_filter"');
$stmt->execute();
$oil_filter = $stmt->fetch(PDO::FETCH_ASSOC);

if ($oil_filter) {
    echo "Oil Filter service item exists: " . $oil_filter['item_name'] . "\n";
    
    // Check if settings exist
    $settings_stmt = $pdo->prepare('SELECT * FROM service_settings WHERE service_item_code = "generator_oil_filter" AND equipment_type = "generator"');
    $settings_stmt->execute();
    $settings = $settings_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Settings records: " . count($settings) . "\n";
    foreach ($settings as $setting) {
        echo "  " . $setting['equipment_id'] . ": " . $setting['interval_hours'] . " hrs, enabled: " . ($setting['is_enabled'] ? 'YES' : 'NO') . "\n";
    }
    
    // Check if tracking exists
    $tracking_stmt = $pdo->prepare('SELECT * FROM service_tracking WHERE service_item_code = "generator_oil_filter" AND equipment_type = "generator"');
    $tracking_stmt->execute();
    $tracking = $tracking_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Tracking records: " . count($tracking) . "\n";
    foreach ($tracking as $track) {
        echo "  " . $track['equipment_id'] . ": last " . $track['last_service_hours'] . " hrs, next " . $track['next_service_hours'] . " hrs\n";
    }
} else {
    echo "Oil Filter service item NOT FOUND!\n";
}
?>
