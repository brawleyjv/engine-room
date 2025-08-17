<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== FIXING SERVICE TRACKING CALCULATIONS ===\n";

// Find all service tracking records with incorrect next_service_hours
echo "Finding records with incorrect calculations...\n";
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
    echo "Found " . count($incorrect_records) . " records with incorrect next_service_hours:\n\n";
    
    foreach ($incorrect_records as $record) {
        echo "Equipment: {$record['equipment_type']} {$record['equipment_id']}\n";
        echo "Service: {$record['service_item_code']}\n";
        echo "Last Service: {$record['last_service_hours']} hrs\n";
        echo "Interval: {$record['interval_hours']} hrs\n";
        echo "Recorded Next Service: {$record['next_service_hours']} hrs (WRONG)\n";
        echo "Correct Next Service: {$record['correct_next_service']} hrs\n";
        
        // Update the record
        $update_stmt = $pdo->prepare("
            UPDATE service_tracking 
            SET next_service_hours = ?,
                updated_at = datetime('now')
            WHERE id = ?
        ");
        $update_stmt->execute([$record['correct_next_service'], $record['id']]);
        
        echo "✅ FIXED: Updated next_service_hours to {$record['correct_next_service']}\n";
        echo "---\n";
    }
    
    echo "\n=== VERIFICATION ===\n";
    echo "Checking port_main secondary fuel filter after fix...\n";
    
    // Get current hours for port main engine
    $stmt = $pdo->prepare("SELECT total_hours FROM engine_hours WHERE engine_type = 'port_main'");
    $stmt->execute();
    $current_hours = $stmt->fetchColumn();
    
    // Check the fixed record
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
        $hours_since_service = $current_hours - $tracking['last_service_hours'];
        $hours_until_service = $tracking['next_service_hours'] - $current_hours;
        
        echo "Current hours: $current_hours\n";
        echo "Last service: {$tracking['last_service_hours']}\n";
        echo "Next service: {$tracking['next_service_hours']}\n";
        echo "Hours since service: $hours_since_service of {$tracking['interval_hours']} interval\n";
        
        if ($current_hours >= $tracking['next_service_hours']) {
            $overdue_hours = $current_hours - $tracking['next_service_hours'];
            echo "Status: OVERDUE by $overdue_hours hours\n";
        } else {
            echo "Status: NOT DUE (still $hours_until_service hours remaining)\n";
        }
    }
    
} else {
    echo "No records found with incorrect calculations.\n";
}

echo "\n=== COMPLETED ===\n";
?>
