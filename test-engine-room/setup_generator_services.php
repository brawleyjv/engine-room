<?php
/**
 * Generator Service Setup Script
 * Creates service items and tracking for generators
 */

require_once 'test_db.php';
require_once 'settings_helper.php';

$pdo = getTestDatabase();
$vessel_settings = getVesselSettings($pdo);

try {
    echo "Setting up generator service tracking...\n";
    
    // Generator service items with realistic intervals
    $generator_services = [
        'racor_fuel_filter' => ['name' => 'Racor Fuel Filter', 'category' => 'Filters', 'interval' => 500],
        'primary_fuel_filter' => ['name' => 'Primary Fuel Filter', 'category' => 'Filters', 'interval' => 500],
        'secondary_fuel_filter' => ['name' => 'Secondary Fuel Filter', 'category' => 'Filters', 'interval' => 500],
        'generator_oil_filter' => ['name' => 'Oil Filter', 'category' => 'Filters', 'interval' => 250],
        'air_intake_filter' => ['name' => 'Air Intake Filter', 'category' => 'Filters', 'interval' => 250]
    ];
    
    // Add service items (if they don't exist)
    $item_stmt = $pdo->prepare("
        INSERT OR IGNORE INTO service_items (item_code, item_name, category, description)
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($generator_services as $code => $details) {
        $description = "Generator " . strtolower($details['name']) . " replacement";
        $item_stmt->execute([$code, $details['name'], $details['category'], $description]);
        echo "  Added service item: {$details['name']}\n";
    }
    
    // Get active generators
    $generators = ['port_gen', 'center_gen', 'starboard_gen'];
    $active_generators = [];
    
    foreach ($generators as $gen) {
        if (isGeneratorActive($gen, $vessel_settings)) {
            $active_generators[] = $gen;
        }
    }
    
    echo "Active generators: " . implode(', ', $active_generators) . "\n";
    
    // Create service settings for each active generator
    $settings_stmt = $pdo->prepare("
        INSERT OR REPLACE INTO service_settings 
        (equipment_type, equipment_id, service_item_code, interval_hours, is_enabled, created_at, updated_at)
        VALUES (?, ?, ?, ?, 1, datetime('now'), datetime('now'))
    ");
    
    foreach ($active_generators as $generator) {
        foreach ($generator_services as $code => $details) {
            $settings_stmt->execute(['generator', $generator, $code, $details['interval']]);
            echo "  Created settings: {$generator} - {$details['name']} ({$details['interval']} hrs)\n";
        }
    }
    
    // Initialize service tracking records
    $tracking_stmt = $pdo->prepare("
        INSERT OR REPLACE INTO service_tracking 
        (equipment_type, equipment_id, service_item_code, last_service_hours, last_service_date, 
         next_service_hours, notes, performed_by, created_at, updated_at)
        VALUES (?, ?, ?, 0, NULL, ?, 'Initial setup', 'System', datetime('now'), datetime('now'))
    ");
    
    foreach ($active_generators as $generator) {
        foreach ($generator_services as $code => $details) {
            $tracking_stmt->execute(['generator', $generator, $code, $details['interval']]);
            echo "  Initialized tracking: {$generator} - {$details['name']}\n";
        }
    }
    
    echo "\n✅ Generator service tracking setup complete!\n";
    echo "Service items created: " . count($generator_services) . "\n";
    echo "Active generators: " . count($active_generators) . "\n";
    echo "Tracking records created: " . (count($generator_services) * count($active_generators)) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up generator services: " . $e->getMessage() . "\n";
}
?>
