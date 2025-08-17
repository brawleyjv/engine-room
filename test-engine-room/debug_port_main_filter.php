<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== DEBUGGING PORT MAIN ENGINE SERVICE TRACKING ===\n";

// Get current hours for port main engine
$stmt = $pdo->prepare("SELECT total_hours FROM engine_hours WHERE engine_type = 'port_main'");
$stmt->execute();
$current_hours = $stmt->fetchColumn();
echo "Current hours for port_main engine: $current_hours\n\n";

// Get service tracking for Secondary Fuel Filter
echo "SERVICE TRACKING FOR SECONDARY FUEL FILTER:\n";
$stmt = $pdo->prepare("
    SELECT st.*, ss.interval_hours
    FROM service_tracking st
    LEFT JOIN service_settings ss ON ss.equipment_type = st.equipment_type 
                                  AND ss.equipment_id = st.equipment_id 
                                  AND ss.service_item_code = st.service_item_code
    WHERE st.equipment_type = 'engine' 
      AND st.equipment_id = 'port_main' 
      AND st.service_item_code = 'secondary_fuel_filter'
");
$stmt->execute();
$tracking = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tracking) {
    echo "Service Item Code: {$tracking['service_item_code']}\n";
    echo "Last Service Hours: {$tracking['last_service_hours']}\n";
    echo "Next Service Hours: {$tracking['next_service_hours']}\n";
    echo "Service Interval: {$tracking['interval_hours']} hours\n";
    
    $hours_since_service = $current_hours - $tracking['last_service_hours'];
    $hours_until_service = $tracking['next_service_hours'] - $current_hours;
    
    echo "\nCALCULATIONS:\n";
    echo "Hours since last service: $current_hours - {$tracking['last_service_hours']} = $hours_since_service\n";
    echo "Hours until next service: {$tracking['next_service_hours']} - $current_hours = $hours_until_service\n";
    
    if ($current_hours >= $tracking['next_service_hours']) {
        $overdue_hours = $current_hours - $tracking['next_service_hours'];
        echo "Status: OVERDUE by $overdue_hours hours\n";
    } else {
        echo "Status: NOT DUE (still $hours_until_service hours remaining)\n";
    }
    
    echo "\nPROBLEM ANALYSIS:\n";
    $correct_next_service = $tracking['last_service_hours'] + $tracking['interval_hours'];
    echo "Correct next service should be: {$tracking['last_service_hours']} + {$tracking['interval_hours']} = $correct_next_service\n";
    echo "Recorded next service: {$tracking['next_service_hours']}\n";
    
    if ($correct_next_service != $tracking['next_service_hours']) {
        echo "MISMATCH FOUND! Next service hours is incorrect.\n";
        echo "Should be: $correct_next_service, but recorded as: {$tracking['next_service_hours']}\n";
        
        echo "\nThis explains why it shows as overdue:\n";
        echo "- System thinks next service is at {$tracking['next_service_hours']} hours\n";
        echo "- Current hours are $current_hours\n";
        echo "- So it calculates as " . ($current_hours - $tracking['next_service_hours']) . " hours overdue\n";
        echo "- But the correct calculation should be:\n";
        echo "- Next service at $correct_next_service hours\n";
        echo "- Current hours $current_hours\n";
        echo "- Status: " . ($current_hours >= $correct_next_service ? "OVERDUE by " . ($current_hours - $correct_next_service) : "NOT DUE, " . ($correct_next_service - $current_hours) . " hours remaining") . "\n";
    }
} else {
    echo "No tracking record found for secondary fuel filter on port_main engine!\n";
}
?>
