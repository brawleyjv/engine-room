<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== INVESTIGATING TURBO OIL FILTER SERVICE ===\n";

// Check current hours for starboard main engine
$stmt = $pdo->prepare("SELECT total_hours FROM engine_hours WHERE engine_type = 'starboard_main'");
$stmt->execute();
$current_hours = $stmt->fetchColumn();
echo "Current starboard_main engine hours: $current_hours\n\n";

// Check turbo oil filter service tracking
echo "TURBO OIL FILTER SERVICE TRACKING:\n";
$stmt = $pdo->prepare("
    SELECT st.*, ss.interval_hours,
           (? - st.last_service_hours) as hours_since_service,
           (st.next_service_hours - ?) as hours_until_service,
           CASE 
               WHEN ? >= st.next_service_hours THEN 'overdue'
               WHEN ? >= (st.next_service_hours - 72) THEN 'due_soon'
               ELSE 'good'
           END as status
    FROM service_tracking st
    LEFT JOIN service_settings ss ON ss.equipment_type = st.equipment_type 
                                  AND ss.equipment_id = st.equipment_id 
                                  AND ss.service_item_code = st.service_item_code
    WHERE st.equipment_type = 'engine' 
      AND st.equipment_id = 'starboard_main' 
      AND st.service_item_code = 'turbo_oil_filter'
");
$stmt->execute([$current_hours, $current_hours, $current_hours, $current_hours]);
$tracking = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tracking) {
    echo "Service Item: {$tracking['service_item_code']}\n";
    echo "Last Service Hours: {$tracking['last_service_hours']}\n";
    echo "Next Service Hours: {$tracking['next_service_hours']}\n";
    echo "Service Interval: {$tracking['interval_hours']} hours\n";
    echo "Hours since service: {$tracking['hours_since_service']}\n";
    echo "Hours until service: {$tracking['hours_until_service']}\n";
    echo "Status: {$tracking['status']}\n";
    echo "Last updated: {$tracking['updated_at']}\n";
    
    echo "\nCalculation Check:\n";
    $expected_next = $tracking['last_service_hours'] + $tracking['interval_hours'];
    echo "Expected next service: {$tracking['last_service_hours']} + {$tracking['interval_hours']} = $expected_next\n";
    echo "Recorded next service: {$tracking['next_service_hours']}\n";
    
    if ($expected_next != $tracking['next_service_hours']) {
        echo "❌ CALCULATION ERROR: Next service hours is wrong!\n";
    } else {
        echo "✅ Calculation is correct\n";
    }
    
    echo "\n72-Hour Threshold Check:\n";
    $due_soon_threshold = $tracking['next_service_hours'] - 72;
    echo "Due soon threshold: $due_soon_threshold hours\n";
    echo "Current hours: $current_hours\n";
    echo "Within 72 hours? " . ($current_hours >= $due_soon_threshold ? "YES (should be due_soon)" : "NO (should be good)") . "\n";
    
} else {
    echo "No tracking record found for turbo oil filter!\n";
}

// Check what the dashboard query returns for this service
echo "\n=== DASHBOARD QUERY TEST ===\n";
$stmt = $pdo->prepare("
    SELECT si.item_name, si.category, st.last_service_hours, st.next_service_hours, 
           ss.interval_hours, ss.is_enabled,
           (? - st.last_service_hours) as hours_since_service,
           CASE 
               WHEN ? >= st.next_service_hours THEN 'overdue'
               WHEN ? >= (st.next_service_hours - 72) THEN 'due_soon'
               ELSE 'good'
           END as alert_level
    FROM service_tracking st
    JOIN service_items si ON st.service_item_code = si.item_code  
    JOIN service_settings ss ON st.equipment_type = ss.equipment_type 
                             AND st.equipment_id = ss.equipment_id 
                             AND st.service_item_code = ss.service_item_code
    WHERE st.equipment_type = 'engine' 
      AND st.equipment_id = 'starboard_main'
      AND st.service_item_code = 'turbo_oil_filter'
      AND ss.is_enabled = 1
");
$stmt->execute([$current_hours, $current_hours, $current_hours]);
$dashboard_result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($dashboard_result) {
    echo "Dashboard query result:\n";
    echo "  Item: {$dashboard_result['item_name']}\n";
    echo "  Alert Level: {$dashboard_result['alert_level']}\n";
    echo "  Hours since service: {$dashboard_result['hours_since_service']}\n";
    echo "  Next service: {$dashboard_result['next_service_hours']}\n";
    echo "  Enabled: " . ($dashboard_result['is_enabled'] ? "YES" : "NO") . "\n";
    
    if ($dashboard_result['alert_level'] == 'due_soon' || $dashboard_result['alert_level'] == 'overdue') {
        echo "❌ This service WILL appear in the alert list\n";
    } else {
        echo "✅ This service will NOT appear in alert list\n";
    }
} else {
    echo "Dashboard query returned no results (service won't appear in alerts)\n";
}

echo "\n=== INVESTIGATION COMPLETED ===\n";
?>
