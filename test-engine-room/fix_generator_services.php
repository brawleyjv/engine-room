<?php
require_once 'test_db.php';
require_once 'settings_helper.php';

$pdo = getTestDatabase();

echo "=== FIXING GENERATOR SERVICE ISSUES ===\n";

// Add the missing Oil Filter service item for generators
$pdo->prepare("INSERT OR IGNORE INTO service_items (item_code, item_name, category, description)
               VALUES ('generator_oil_filter', 'Oil Filter', 'Filters', 'Generator oil filter replacement')")
    ->execute();
echo "Added generator_oil_filter service item\n";

// Get active generators
$vessel_settings = getVesselSettings($pdo);
$generators = ['port_gen', 'center_gen', 'starboard_gen'];
$active_generators = [];

foreach ($generators as $gen) {
    if (isGeneratorActive($gen, $vessel_settings)) {
        $active_generators[] = $gen;
        echo "Active generator: $gen\n";
    }
}

// Create service settings for Oil Filter
foreach ($active_generators as $generator) {
    $pdo->prepare("INSERT OR REPLACE INTO service_settings 
                  (equipment_type, equipment_id, service_item_code, interval_hours, is_enabled, created_at, updated_at)
                  VALUES ('generator', ?, 'generator_oil_filter', 250, 1, datetime('now'), datetime('now'))")
        ->execute([$generator]);
    
    // Initialize tracking
    $pdo->prepare("INSERT OR REPLACE INTO service_tracking 
                  (equipment_type, equipment_id, service_item_code, last_service_hours, next_service_hours, updated_at)
                  VALUES ('generator', ?, 'generator_oil_filter', 0, 250, datetime('now'))")
        ->execute([$generator]);
    
    echo "Added Oil Filter settings and tracking for $generator\n";
}

// Now check for any completed services that might not be showing up properly
echo "\n=== CHECKING SERVICE COMPLETION LOGIC ===\n";

// Check if there are any services that should be reset
$current_hours_stmt = $pdo->prepare("SELECT total_hours FROM generator_hours WHERE generator_type = 'port_gen'");
$current_hours_stmt->execute();
$current_hours = $current_hours_stmt->fetchColumn();

echo "Current port generator hours: $current_hours\n";

// Check tracking records
$tracking_stmt = $pdo->prepare("
    SELECT st.*, si.item_name
    FROM service_tracking st
    JOIN service_items si ON st.service_item_code = si.item_code
    WHERE st.equipment_type = 'generator' AND st.equipment_id = 'port_gen'
    ORDER BY si.item_name
");
$tracking_stmt->execute();
$tracking = $tracking_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tracking as $track) {
    $hours_since = $current_hours - $track['last_service_hours'];
    $status = 'GOOD';
    if ($hours_since >= $track['next_service_hours']) {
        $status = 'OVERDUE';
    } elseif ($hours_since >= ($track['next_service_hours'] * 0.9)) {
        $status = 'DUE SOON';
    }
    
    echo "{$track['item_name']}: {$hours_since}/{$track['next_service_hours']} hrs - $status\n";
}

echo "\n✅ Generator service issues fixed!\n";
?>
