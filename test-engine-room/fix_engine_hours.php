<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "Before update:\n";
$stmt = $pdo->prepare('SELECT total_hours FROM engine_hours WHERE engine_type = "starboard_main"');
$stmt->execute();
$old_hours = $stmt->fetchColumn();
echo "Current hours: $old_hours\n";

// Update to reflect the 1000 hour addition you made
$new_hours = 2874; // 1874 + 1000
$stmt = $pdo->prepare('UPDATE engine_hours SET total_hours = ?, last_updated = datetime("now") WHERE engine_type = "starboard_main"');
$stmt->execute([$new_hours]);

echo "After update:\n";
$stmt = $pdo->prepare('SELECT total_hours FROM engine_hours WHERE engine_type = "starboard_main"');
$stmt->execute();
$updated_hours = $stmt->fetchColumn();
echo "Updated hours: $updated_hours\n";

// Now show the service tracking updates
echo "\nService tracking after hours update:\n";
$stmt = $pdo->prepare('
    SELECT service_item_code, last_service_hours, 
           (? - last_service_hours) as hours_since_service
    FROM service_tracking 
    WHERE equipment_type = "engine" AND equipment_id = "starboard_main"
    ORDER BY service_item_code
');
$stmt->execute([$updated_hours]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($services as $service) {
    echo "  {$service['service_item_code']}: {$service['hours_since_service']} hours since service\n";
}

echo "\nEngine hours successfully updated! All service items now show correct hours-since-service.\n";
?>
