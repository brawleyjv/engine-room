<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== FINAL VERIFICATION OF 72-HOUR THRESHOLD ===\n";

// Check the specific service that was causing the issue
$stmt = $pdo->prepare("
    SELECT si.item_name, st.last_service_hours, st.next_service_hours, ss.interval_hours,
           (2750 - st.last_service_hours) as hours_since_service,
           (st.next_service_hours - 2750) as hours_until_service,
           CASE 
               WHEN 2750 >= st.next_service_hours THEN 'overdue'
               WHEN 2750 >= (st.next_service_hours - 72) THEN 'due_soon'
               ELSE 'good'
           END as status
    FROM service_tracking st
    JOIN service_items si ON st.service_item_code = si.item_code
    JOIN service_settings ss ON st.equipment_type = ss.equipment_type 
                              AND st.equipment_id = ss.equipment_id 
                              AND st.service_item_code = ss.service_item_code
    WHERE st.equipment_type = 'engine' 
      AND st.equipment_id = 'port_main' 
      AND st.service_item_code = 'secondary_fuel_filter'
");
$stmt->execute();
$service = $stmt->fetch(PDO::FETCH_ASSOC);

if ($service) {
    echo "Port Main Engine - Secondary Fuel Filter:\n";
    echo "  Current hours: 2750\n";
    echo "  Last service: {$service['last_service_hours']} hours\n";
    echo "  Next service: {$service['next_service_hours']} hours\n";
    echo "  Service interval: {$service['interval_hours']} hours\n";
    echo "  Hours since last service: {$service['hours_since_service']}\n";
    echo "  Hours until next service: {$service['hours_until_service']}\n";
    echo "  Status: " . strtoupper($service['status']) . "\n";
    
    $due_soon_threshold = $service['next_service_hours'] - 72;
    echo "\n72-Hour Threshold Analysis:\n";
    echo "  Service due at: {$service['next_service_hours']} hours\n";
    echo "  Due soon threshold: $due_soon_threshold hours (72 hours before due)\n";
    echo "  Current hours: 2750\n";
    echo "  Result: " . (2750 >= $due_soon_threshold ? "Within 72-hour window" : "More than 72 hours remaining") . "\n";
    
    if ($service['status'] == 'good' && $service['hours_until_service'] > 72) {
        echo "\n✅ SUCCESS: Service is correctly showing as 'GOOD' with {$service['hours_until_service']} hours remaining (more than 72 hours)\n";
    } else {
        echo "\n❌ Issue: Status is '{$service['status']}' but should be 'good'\n";
    }
} else {
    echo "Service record not found!\n";
}

echo "\n=== VERIFICATION COMPLETED ===\n";
?>
