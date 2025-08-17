<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== CHECKING FOR DUPLICATE SERVICE ITEMS ===\n";

$equipment_types = ['engine', 'gearbox', 'generator'];

foreach ($equipment_types as $equipment_type) {
    echo "\n--- CHECKING {$equipment_type}S FOR DUPLICATES ---\n";
    
    // Check for duplicates in service_tracking
    $stmt = $pdo->prepare("
        SELECT equipment_id, service_item_code, COUNT(*) as count
        FROM service_tracking 
        WHERE equipment_type = ?
        GROUP BY equipment_id, service_item_code
        HAVING COUNT(*) > 1
        ORDER BY equipment_id, service_item_code
    ");
    $stmt->execute([$equipment_type]);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "❌ Found duplicates in service_tracking:\n";
        foreach ($duplicates as $dup) {
            echo "  {$dup['equipment_id']} - {$dup['service_item_code']}: {$dup['count']} entries\n";
        }
    } else {
        echo "✅ No duplicates found in service_tracking\n";
    }
    
    // Check for duplicates in service_settings
    $stmt = $pdo->prepare("
        SELECT equipment_id, service_item_code, COUNT(*) as count
        FROM service_settings 
        WHERE equipment_type = ?
        GROUP BY equipment_id, service_item_code
        HAVING COUNT(*) > 1
        ORDER BY equipment_id, service_item_code
    ");
    $stmt->execute([$equipment_type]);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "❌ Found duplicates in service_settings:\n";
        foreach ($duplicates as $dup) {
            echo "  {$dup['equipment_id']} - {$dup['service_item_code']}: {$dup['count']} entries\n";
        }
    } else {
        echo "✅ No duplicates found in service_settings\n";
    }
}

echo "\n=== CHECKING HOURS UPDATE ISSUE ===\n";

// Check current starboard main engine hours
$stmt = $pdo->prepare("SELECT total_hours FROM engine_hours WHERE engine_type = 'starboard_main'");
$stmt->execute();
$current_hours = $stmt->fetchColumn();
echo "Current starboard_main engine hours: $current_hours\n";

// Check turbo oil filter tracking
$stmt = $pdo->prepare("
    SELECT st.*, (? - st.last_service_hours) as calculated_hours_since_service
    FROM service_tracking st
    WHERE st.equipment_type = 'engine' 
      AND st.equipment_id = 'starboard_main' 
      AND st.service_item_code = 'turbo_oil_filter'
");
$stmt->execute([$current_hours]);
$tracking = $stmt->fetch(PDO::FETCH_ASSOC);

if ($tracking) {
    echo "\nTurbo Oil Filter tracking:\n";
    echo "  Last service hours: {$tracking['last_service_hours']}\n";
    echo "  Current engine hours: $current_hours\n";
    echo "  Calculated hours since service: {$tracking['calculated_hours_since_service']}\n";
    echo "  Next service hours: {$tracking['next_service_hours']}\n";
    
    if ($tracking['calculated_hours_since_service'] == 0) {
        echo "  ⚠️ Hours since service is 0 - this means last_service_hours equals current engine hours\n";
        echo "  This suggests the service was just completed and hours haven't been updated since\n";
    } else {
        echo "  ✅ Hours since service is updating correctly\n";
    }
}

echo "\n=== ANALYSIS COMPLETED ===\n";
?>
