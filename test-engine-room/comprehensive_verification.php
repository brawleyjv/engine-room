<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== COMPREHENSIVE SERVICE TRACKING VERIFICATION ===\n";

// Check all equipment types for incorrect calculations
$equipment_types = ['engine', 'gearbox', 'generator'];
$total_incorrect = 0;

foreach ($equipment_types as $equipment_type) {
    echo "\n--- CHECKING {$equipment_type}S ---\n";
    
    $stmt = $pdo->prepare("
        SELECT st.equipment_id, st.service_item_code, st.last_service_hours, 
               st.next_service_hours, ss.interval_hours,
               (st.last_service_hours + ss.interval_hours) as correct_next_service
        FROM service_tracking st
        JOIN service_settings ss ON ss.equipment_type = st.equipment_type 
                                  AND ss.equipment_id = st.equipment_id 
                                  AND ss.service_item_code = st.service_item_code
        WHERE st.equipment_type = ?
          AND st.last_service_hours > 0
          AND st.next_service_hours != (st.last_service_hours + ss.interval_hours)
        ORDER BY st.equipment_id, st.service_item_code
    ");
    $stmt->execute([$equipment_type]);
    $incorrect_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($incorrect_records) > 0) {
        echo "❌ Found " . count($incorrect_records) . " incorrect records:\n";
        foreach ($incorrect_records as $record) {
            echo "  {$record['equipment_id']} {$record['service_item_code']}: ";
            echo "next={$record['next_service_hours']} (should be {$record['correct_next_service']})\n";
        }
        $total_incorrect += count($incorrect_records);
    } else {
        echo "✅ All {$equipment_type} service tracking is correct!\n";
    }
    
    // Show summary for each equipment
    $stmt = $pdo->prepare("
        SELECT st.equipment_id, COUNT(*) as total_services,
               COUNT(CASE WHEN st.next_service_hours = (st.last_service_hours + ss.interval_hours) THEN 1 END) as correct_services
        FROM service_tracking st
        JOIN service_settings ss ON ss.equipment_type = st.equipment_type 
                                  AND ss.equipment_id = st.equipment_id 
                                  AND ss.service_item_code = st.service_item_code
        WHERE st.equipment_type = ?
          AND st.last_service_hours > 0
        GROUP BY st.equipment_id
        ORDER BY st.equipment_id
    ");
    $stmt->execute([$equipment_type]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = ($row['total_services'] == $row['correct_services']) ? "✅" : "❌";
        echo "  {$row['equipment_id']}: {$row['correct_services']}/{$row['total_services']} services correct $status\n";
    }
}

echo "\n=== OVERALL RESULTS ===\n";
if ($total_incorrect == 0) {
    echo "🎉 ALL SERVICE TRACKING IS NOW CORRECT!\n";
    echo "✅ All engines, gearboxes, and generators have accurate service calculations\n";
} else {
    echo "❌ Total incorrect records found: $total_incorrect\n";
    echo "These need to be fixed.\n";
}

// Test a few specific examples
echo "\n=== SPECIFIC EXAMPLES ===\n";

$test_cases = [
    ['engine', 'port_main', 'secondary_fuel_filter'],
    ['gearbox', 'port_main', 'lube_oil_change'],
    ['generator', 'starboard_gen', 'primary_fuel_filter']
];

foreach ($test_cases as $case) {
    list($eq_type, $eq_id, $service_code) = $case;
    
    // Get current hours
    $hours_table = $eq_type . '_hours';
    $id_column = $eq_type . '_type';
    
    $stmt = $pdo->prepare("SELECT total_hours FROM $hours_table WHERE $id_column = ?");
    $stmt->execute([$eq_id]);
    $current_hours = $stmt->fetchColumn();
    
    // Get service tracking
    $stmt = $pdo->prepare("
        SELECT st.*, ss.interval_hours
        FROM service_tracking st
        JOIN service_settings ss ON ss.equipment_type = st.equipment_type 
                                  AND ss.equipment_id = st.equipment_id 
                                  AND ss.service_item_code = st.service_item_code
        WHERE st.equipment_type = ? 
          AND st.equipment_id = ? 
          AND st.service_item_code = ?
    ");
    $stmt->execute([$eq_type, $eq_id, $service_code]);
    $tracking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tracking) {
        $hours_since = $current_hours - $tracking['last_service_hours'];
        $hours_until = $tracking['next_service_hours'] - $current_hours;
        $expected_next = $tracking['last_service_hours'] + $tracking['interval_hours'];
        
        echo "{$eq_type} {$eq_id} {$service_code}:\n";
        echo "  Current: {$current_hours}h, Last service: {$tracking['last_service_hours']}h\n";
        echo "  Hours since: {$hours_since}h of {$tracking['interval_hours']}h interval\n";
        echo "  Next due: {$tracking['next_service_hours']}h";
        
        if ($tracking['next_service_hours'] == $expected_next) {
            echo " ✅ (correct: {$tracking['last_service_hours']} + {$tracking['interval_hours']})\n";
            if ($current_hours >= $tracking['next_service_hours']) {
                echo "  Status: OVERDUE by " . ($current_hours - $tracking['next_service_hours']) . "h\n";
            } else {
                echo "  Status: NOT DUE ({$hours_until}h remaining)\n";
            }
        } else {
            echo " ❌ (should be $expected_next)\n";
        }
    } else {
        echo "{$eq_type} {$eq_id} {$service_code}: No tracking record found\n";
    }
    echo "\n";
}

echo "=== VERIFICATION COMPLETED ===\n";
?>
