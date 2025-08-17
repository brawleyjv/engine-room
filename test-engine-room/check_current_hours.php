<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "Current Engine Hours:\n";
$stmt = $pdo->prepare('SELECT * FROM engine_hours WHERE engine_type = "starboard_main"');
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($result);

echo "\nService Tracking for Starboard Main:\n";
$stmt = $pdo->prepare('
    SELECT st.*, 
           (SELECT total_hours FROM engine_hours WHERE engine_type = "starboard_main") as current_hours,
           (SELECT total_hours FROM engine_hours WHERE engine_type = "starboard_main") - st.last_service_hours as calculated_hours_since
    FROM service_tracking st 
    WHERE equipment_type = "engine" AND equipment_id = "starboard_main"
    ORDER BY service_item_code
');
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($services as $service) {
    echo "  {$service['service_item_code']}: Last service at {$service['last_service_hours']} hrs, ";
    echo "Current: {$service['current_hours']} hrs, ";
    echo "Hours since: {$service['calculated_hours_since']}\n";
}
?>
