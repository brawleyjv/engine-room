<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== FINAL VERIFICATION ===\n";

// Check for any remaining incorrect calculations
echo "Checking for any remaining incorrect service tracking calculations...\n";
$stmt = $pdo->prepare("
    SELECT st.*, ss.interval_hours,
           (st.last_service_hours + ss.interval_hours) as correct_next_service
    FROM service_tracking st
    JOIN service_settings ss ON ss.equipment_type = st.equipment_type 
                              AND ss.equipment_id = st.equipment_id 
                              AND ss.service_item_code = st.service_item_code
    WHERE st.next_service_hours != (st.last_service_hours + ss.interval_hours)
      AND st.last_service_hours > 0
");
$stmt->execute();
$incorrect_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($incorrect_records) > 0) {
    echo "❌ Found " . count($incorrect_records) . " records still with incorrect calculations:\n";
    foreach ($incorrect_records as $record) {
        echo "  {$record['equipment_type']} {$record['equipment_id']} {$record['service_item_code']}: {$record['next_service_hours']} (should be {$record['correct_next_service']})\n";
    }
} else {
    echo "✅ All service tracking calculations are now correct!\n";
}

// Test specific case mentioned by user
echo "\nChecking port_main engine secondary fuel filter:\n";
$stmt = $pdo->prepare("SELECT total_hours FROM engine_hours WHERE engine_type = 'port_main'");
$stmt->execute();
$current_hours = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT st.*, ss.interval_hours
    FROM service_tracking st
    JOIN service_settings ss ON ss.equipment_type = st.equipment_type 
                              AND ss.equipment_id = st.equipment_id 
                              AND ss.service_item_code = st.service_item_code
    WHERE st.equipment_type = 'engine' 
      AND st.equipment_id = 'port_main' 
      AND st.service_item_code = 'secondary_fuel_filter'
");
$stmt->execute();
$tracking = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tracking) {
    $hours_since_service = $current_hours - $tracking['last_service_hours'];
    $hours_until_next = $tracking['next_service_hours'] - $current_hours;
    
    echo "Current hours: $current_hours\n";
    echo "Last service: {$tracking['last_service_hours']} hrs\n";
    echo "Next service due: {$tracking['next_service_hours']} hrs\n";
    echo "Service interval: {$tracking['interval_hours']} hrs\n";
    echo "Hours since last service: $hours_since_service of {$tracking['interval_hours']} interval\n";
    
    if ($current_hours >= $tracking['next_service_hours']) {
        $overdue_hours = $current_hours - $tracking['next_service_hours'];
        echo "Status: ❌ OVERDUE by $overdue_hours hours\n";
    } else {
        echo "Status: ✅ NOT DUE (still $hours_until_next hours remaining)\n";
    }
    
    // Verify calculation is correct
    $expected_next = $tracking['last_service_hours'] + $tracking['interval_hours'];
    if ($tracking['next_service_hours'] == $expected_next) {
        echo "✅ Calculation is correct: {$tracking['last_service_hours']} + {$tracking['interval_hours']} = {$tracking['next_service_hours']}\n";
    } else {
        echo "❌ Calculation is still wrong: should be {$tracking['last_service_hours']} + {$tracking['interval_hours']} = $expected_next, but is {$tracking['next_service_hours']}\n";
    }
}

echo "\n=== VERIFICATION COMPLETED ===\n";
?>
