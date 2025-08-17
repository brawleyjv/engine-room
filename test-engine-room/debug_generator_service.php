<?php
require_once 'test_db.php';
require_once 'settings_helper.php';

$pdo = getTestDatabase();

echo "=== GENERATOR SERVICE DEBUG ===\n";

// Check current generator hours
$hours_stmt = $pdo->prepare("SELECT * FROM generator_hours WHERE generator_type = 'port_gen'");
$hours_stmt->execute();
$generator_data = $hours_stmt->fetch(PDO::FETCH_ASSOC);
echo "Port Generator Hours: " . ($generator_data ? $generator_data['total_hours'] : 'NO RECORD') . "\n";

// Check service settings
echo "\nService Settings for port_gen:\n";
$settings_stmt = $pdo->prepare("
    SELECT ss.service_item_code, ss.interval_hours, ss.is_enabled, si.item_name
    FROM service_settings ss
    JOIN service_items si ON ss.service_item_code = si.item_code
    WHERE ss.equipment_type = 'generator' AND ss.equipment_id = 'port_gen'
");
$settings_stmt->execute();
$settings = $settings_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($settings as $setting) {
    echo "  {$setting['item_name']}: {$setting['interval_hours']} hrs, enabled: " . ($setting['is_enabled'] ? 'YES' : 'NO') . "\n";
}

// Check service tracking
echo "\nService Tracking for port_gen:\n";
$tracking_stmt = $pdo->prepare("
    SELECT st.*, si.item_name
    FROM service_tracking st
    JOIN service_items si ON st.service_item_code = si.item_code
    WHERE st.equipment_type = 'generator' AND st.equipment_id = 'port_gen'
");
$tracking_stmt->execute();
$tracking = $tracking_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tracking as $track) {
    echo "  {$track['item_name']}:\n";
    echo "    Last service: {$track['last_service_hours']} hrs on {$track['last_service_date']}\n";
    echo "    Next service: {$track['next_service_hours']} hrs\n";
    echo "    Notes: {$track['notes']}\n";
}

// Test alert calculation
$current_hours = $generator_data ? $generator_data['total_hours'] : 0;
echo "\nAlert Calculation (current hours: $current_hours):\n";

$alert_stmt = $pdo->prepare("
    SELECT si.item_name, st.last_service_hours, st.next_service_hours,
           (? - st.last_service_hours) as hours_since_service,
           CASE 
               WHEN (? - st.last_service_hours) >= st.next_service_hours THEN 'overdue'
               WHEN (? - st.last_service_hours) >= (st.next_service_hours * 0.9) THEN 'due_soon'
               WHEN (? - st.last_service_hours) >= (st.next_service_hours * 0.8) THEN 'upcoming'
               ELSE 'good'
           END as status
    FROM service_tracking st
    JOIN service_items si ON st.service_item_code = si.item_code
    WHERE st.equipment_type = 'generator' AND st.equipment_id = 'port_gen'
");
$alert_stmt->execute([$current_hours, $current_hours, $current_hours, $current_hours]);
$alerts = $alert_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($alerts as $alert) {
    echo "  {$alert['item_name']}: {$alert['hours_since_service']}/{$alert['next_service_hours']} hrs - " . strtoupper($alert['status']) . "\n";
}
?>
