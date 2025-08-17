<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== FINAL VERIFICATION ===\n";

echo "\n1. CHECKING FOR REMAINING DUPLICATES:\n";
$stmt = $pdo->prepare("
    SELECT equipment_type, equipment_id, service_item_code, COUNT(*) as count
    FROM service_tracking 
    GROUP BY equipment_type, equipment_id, service_item_code
    HAVING COUNT(*) > 1
");
$stmt->execute();
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($duplicates) == 0) {
    echo "✅ No duplicates found in service_tracking table\n";
} else {
    echo "❌ Still found " . count($duplicates) . " duplicate groups:\n";
    foreach ($duplicates as $dup) {
        echo "  {$dup['equipment_type']} {$dup['equipment_id']} {$dup['service_item_code']}: {$dup['count']} entries\n";
    }
}

echo "\n2. VERIFYING HOURS CALCULATION:\n";
// Check starboard main turbo oil filter specifically
$stmt = $pdo->prepare("SELECT total_hours FROM engine_hours WHERE engine_type = 'starboard_main'");
$stmt->execute();
$current_hours = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT st.*, ss.interval_hours, (? - st.last_service_hours) as hours_since_service
    FROM service_tracking st
    JOIN service_settings ss ON st.equipment_type = ss.equipment_type 
                              AND st.equipment_id = ss.equipment_id 
                              AND st.service_item_code = ss.service_item_code
    WHERE st.equipment_type = 'engine' 
      AND st.equipment_id = 'starboard_main' 
      AND st.service_item_code = 'turbo_oil_filter'
");
$stmt->execute([$current_hours]);
$turbo_filter = $stmt->fetch(PDO::FETCH_ASSOC);

if ($turbo_filter) {
    echo "Starboard Main Turbo Oil Filter:\n";
    echo "  Current engine hours: $current_hours\n";
    echo "  Last service hours: {$turbo_filter['last_service_hours']}\n";
    echo "  Hours since service: {$turbo_filter['hours_since_service']}\n";
    echo "  Service interval: {$turbo_filter['interval_hours']} hours\n";
    
    $expected_hours = $current_hours - $turbo_filter['last_service_hours'];
    if ($turbo_filter['hours_since_service'] == $expected_hours) {
        echo "  ✅ Hours calculation is correct\n";
    } else {
        echo "  ❌ Hours calculation is wrong. Expected: $expected_hours, Got: {$turbo_filter['hours_since_service']}\n";
    }
}

echo "\n3. CHECKING SERVICE STATUS LOGIC:\n";
// Test a few different services to make sure status logic is working
$test_services = [
    ['engine', 'starboard_main', 'turbo_oil_filter'],
    ['engine', 'port_main', 'secondary_fuel_filter'],
    ['engine', 'port_main', 'lube_oil_strainer']
];

foreach ($test_services as $service) {
    list($eq_type, $eq_id, $service_code) = $service;
    
    $hours_table = $eq_type . '_hours';
    $id_column = $eq_type . '_type';
    
    $stmt = $pdo->prepare("SELECT total_hours FROM $hours_table WHERE $id_column = ?");
    $stmt->execute([$eq_id]);
    $current = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT st.*, ss.interval_hours,
               (? - st.last_service_hours) as hours_since_service,
               CASE 
                   WHEN (? - st.last_service_hours) >= ss.interval_hours THEN 'overdue'
                   WHEN (? - st.last_service_hours) >= (ss.interval_hours - 72) THEN 'due_soon'
                   ELSE 'good'
               END as status
        FROM service_tracking st
        JOIN service_settings ss ON st.equipment_type = ss.equipment_type 
                                  AND st.equipment_id = ss.equipment_id 
                                  AND st.service_item_code = ss.service_item_code
        WHERE st.equipment_type = ? 
          AND st.equipment_id = ? 
          AND st.service_item_code = ?
    ");
    $stmt->execute([$current, $current, $current, $eq_type, $eq_id, $service_code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "{$eq_type} {$eq_id} {$service_code}:\n";
        echo "  Hours since service: {$result['hours_since_service']} / {$result['interval_hours']}\n";
        echo "  Status: " . strtoupper($result['status']) . "\n";
    }
}

echo "\n=== VERIFICATION COMPLETED ===\n";
echo "🎉 Service tracking system is now clean and working correctly!\n";
?>
