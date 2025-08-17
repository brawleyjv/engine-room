<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== DEBUGGING TURBO OIL FILTER SERVICE LOGIC ===\n";

// Get current engine hours for starboard main
$stmt = $pdo->prepare("SELECT total_hours FROM engine_hours WHERE engine_type = 'starboard_main'");
$stmt->execute();
$current_engine_hours = $stmt->fetchColumn();
echo "Current engine hours: $current_engine_hours\n\n";

// Get service tracking for turbo oil filter
$stmt = $pdo->prepare("
    SELECT st.*, ss.interval_hours
    FROM service_tracking st
    JOIN service_settings ss ON st.equipment_type = ss.equipment_type 
                              AND st.equipment_id = ss.equipment_id 
                              AND st.service_item_code = ss.service_item_code
    WHERE st.equipment_type = 'engine' 
      AND st.equipment_id = 'starboard_main' 
      AND st.service_item_code = 'turbo_oil_filter'
");
$stmt->execute();
$tracking = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tracking) {
    $hours_since_service = $current_engine_hours - $tracking['last_service_hours'];
    $interval = $tracking['interval_hours'];
    $due_soon_threshold = $interval - 72;
    
    echo "CURRENT LOGIC (WRONG):\n";
    echo "Service interval: {$interval} hours\n";
    echo "Last service at: {$tracking['last_service_hours']} engine hours\n";
    echo "Next service at: {$tracking['next_service_hours']} engine hours\n";
    echo "Current engine hours: $current_engine_hours\n";
    echo "Hours since service: $hours_since_service\n";
    
    echo "\nCURRENT STATUS CALCULATION (comparing engine hours to service hours):\n";
    if ($current_engine_hours >= $tracking['next_service_hours']) {
        echo "Status: OVERDUE (engine hours $current_engine_hours >= next service {$tracking['next_service_hours']})\n";
    } elseif ($current_engine_hours >= ($tracking['next_service_hours'] - 72)) {
        echo "Status: DUE SOON (engine hours $current_engine_hours >= due soon threshold " . ($tracking['next_service_hours'] - 72) . ")\n";
    } else {
        echo "Status: GOOD (engine hours $current_engine_hours < due soon threshold " . ($tracking['next_service_hours'] - 72) . ")\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "CORRECT LOGIC (SHOULD BE):\n";
    echo "Service interval: {$interval} hours\n";
    echo "Hours since service: $hours_since_service\n";
    echo "Due soon threshold: $due_soon_threshold hours ({$interval} - 72)\n";
    
    echo "\nCORRECT STATUS CALCULATION (comparing hours since service to interval):\n";
    if ($hours_since_service >= $interval) {
        echo "Status: OVERDUE (hours since service $hours_since_service >= interval {$interval})\n";
    } elseif ($hours_since_service >= $due_soon_threshold) {
        echo "Status: DUE SOON (hours since service $hours_since_service >= due soon threshold $due_soon_threshold)\n";
    } else {
        echo "Status: GOOD (hours since service $hours_since_service < due soon threshold $due_soon_threshold)\n";
    }
    
    echo "\nEXAMPLE SCENARIOS:\n";
    echo "- If filter has 0 hours (just serviced): Status = GOOD\n";
    echo "- If filter has 24 hours: Status = GOOD\n";
    echo "- If filter has 678 hours (750-72): Status = DUE SOON\n";
    echo "- If filter has 750+ hours: Status = OVERDUE\n";
    
} else {
    echo "No tracking record found for turbo oil filter!\n";
}

echo "\n=== DEBUG COMPLETED ===\n";
?>
