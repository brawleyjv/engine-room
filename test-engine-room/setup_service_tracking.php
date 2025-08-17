<?php
/**
 * Setup Service Tracking System for Main Engines
 * Creates tables and default service items with intervals and toggles
 */

require_once 'test_db.php';
require_once 'log_helper.php';

echo "<h2>🔧 Setting up Service Tracking System</h2>\n";

try {
    $pdo = getTestDatabase();
    
    // Create service items configuration table
    echo "<h3>Creating service items configuration table...</h3>\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_items (
        id INTEGER PRIMARY KEY,
        item_code TEXT UNIQUE NOT NULL,
        item_name TEXT NOT NULL,
        category TEXT NOT NULL,
        default_interval_hours INTEGER NOT NULL,
        description TEXT,
        is_universal INTEGER DEFAULT 1,
        engine_models TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create service tracking table
    echo "<h3>Creating service tracking table...</h3>\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_tracking (
        id INTEGER PRIMARY KEY,
        equipment_type TEXT NOT NULL,
        equipment_id TEXT NOT NULL,
        service_item_code TEXT NOT NULL,
        last_service_hours INTEGER DEFAULT 0,
        last_service_date DATE,
        next_service_hours INTEGER,
        notes TEXT,
        performed_by TEXT,
        log_entry_id INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (service_item_code) REFERENCES service_items(item_code),
        FOREIGN KEY (log_entry_id) REFERENCES log_entries(id)
    )");
    
    // Create service settings table for enable/disable toggles
    echo "<h3>Creating service settings table...</h3>\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS service_settings (
        id INTEGER PRIMARY KEY,
        equipment_type TEXT NOT NULL,
        equipment_id TEXT NOT NULL,
        service_item_code TEXT NOT NULL,
        is_enabled INTEGER DEFAULT 1,
        interval_hours INTEGER,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(equipment_type, equipment_id, service_item_code),
        FOREIGN KEY (service_item_code) REFERENCES service_items(item_code)
    )");
    
    // Insert the service items from user's list
    echo "<h3>Adding service items...</h3>\n";
    
    $service_items = [
        // Filters
        ['lube_oil_filter', 'Lube Oil Filter', 'Filters', 500, 'Main engine lube oil filter replacement', 1, null],
        ['primary_fuel_filter', 'Primary Fuel Filter', 'Filters', 1000, 'Primary fuel filter replacement', 1, null],
        ['secondary_fuel_filter', 'Secondary Fuel Filter', 'Filters', 500, 'Secondary fuel filter replacement', 1, null],
        ['fuel_prefilter', 'Fuel Prefilter', 'Filters', 250, 'Fuel prefilter replacement', 1, null],
        ['racor_fuel_filter', 'Racor Fuel Filter', 'Filters', 500, 'Racor fuel filter replacement', 0, 'Caterpillar,Detroit,Cummins'],
        ['turbo_oil_filter', 'Turbo Oil Filter', 'Filters', 750, 'Turbocharger oil filter replacement', 0, 'Caterpillar,Detroit'],
        ['soakback_pump_oil_filter', 'Soakback Pump Oil Filter', 'Filters', 1000, 'Soakback pump oil filter replacement', 0, 'Caterpillar'],
        ['air_intake_filter', 'Air Intake Filter', 'Filters', 1000, 'Engine air intake filter replacement', 1, null],
        ['coolant_conditioner_filter', 'Coolant Conditioner Filter', 'Filters', 2000, 'Coolant conditioner filter replacement', 1, null],
        
        // Oil Changes
        ['lube_oil_change', 'Lube Oil Change', 'Oil Changes', 500, 'Complete engine lube oil change', 1, null],
        
        // Maintenance Items
        ['lube_oil_strainer', 'Lube Oil Strainer', 'Maintenance', 1000, 'Lube oil strainer cleaning/replacement', 1, null]
    ];
    
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO service_items 
        (item_code, item_name, category, default_interval_hours, description, is_universal, engine_models) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($service_items as $item) {
        $stmt->execute($item);
        echo "<p>✅ Added: {$item[1]} ({$item[3]} hours)</p>\n";
    }
    
    // Initialize service settings for each active engine
    echo "<h3>Initializing service settings for active engines...</h3>\n";
    
    // Get active engines from settings
    $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM vessel_settings 
                                  WHERE setting_key LIKE 'engine_%_active'");
    $engine_settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $engines = ['port_main', 'center_main', 'starboard_main'];
    $service_settings_stmt = $pdo->prepare("INSERT OR IGNORE INTO service_settings 
        (equipment_type, equipment_id, service_item_code, is_enabled, interval_hours) 
        VALUES ('engine', ?, ?, ?, ?)");
    
    foreach ($engines as $engine) {
        $setting_key = 'engine_' . $engine . '_active';
        if (isset($engine_settings[$setting_key]) && $engine_settings[$setting_key] == '1') {
            echo "<h4>Setting up {$engine} engine services:</h4>\n";
            
            foreach ($service_items as $item) {
                $is_enabled = $item[5]; // Default enabled status
                $service_settings_stmt->execute([$engine, $item[0], $is_enabled, $item[3]]);
                
                $status = $is_enabled ? '✅ Enabled' : '⚪ Disabled (engine-specific)';
                echo "<p style='margin-left: 20px;'>{$status}: {$item[1]}</p>\n";
            }
        }
    }
    
    // Initialize service tracking records for enabled items
    echo "<h3>Initializing service tracking records...</h3>\n";
    
    $tracking_stmt = $pdo->prepare("INSERT OR IGNORE INTO service_tracking 
        (equipment_type, equipment_id, service_item_code, last_service_hours, next_service_hours) 
        SELECT equipment_type, equipment_id, service_item_code, 0, interval_hours 
        FROM service_settings WHERE is_enabled = 1");
    $tracking_stmt->execute();
    
    $tracking_count = $pdo->lastInsertId();
    echo "<p>✅ Initialized service tracking for enabled items</p>\n";
    
    // Create log entry
    createLogEntry($pdo, 'system', null, 
        'Service tracking system initialized with ' . count($service_items) . ' service items. Engine-specific filters configured based on vessel equipment.', 
        'System');
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin: 20px 0;'>\n";
    echo "<h3>🎯 Service Tracking System Ready!</h3>\n";
    echo "<ul style='line-height: 1.8;'>\n";
    echo "<li><strong>" . count($service_items) . " Service Items</strong> configured with default intervals</li>\n";
    echo "<li><strong>Engine-specific toggles</strong> for model-specific filters (Racor, Turbo, Soakback)</li>\n";
    echo "<li><strong>Universal items</strong> enabled for all engines by default</li>\n";
    echo "<li><strong>Service settings</strong> can be customized per engine in settings page</li>\n";
    echo "<li><strong>Dashboard alerts</strong> will show when services are due</li>\n";
    echo "</ul>\n";
    echo "<p><a href='settings.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Configure Service Settings</a></p>\n";
    echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>View Dashboard</a></p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545; margin: 20px 0;'>\n";
    echo "<h3>❌ Error Setting Up Service Tracking</h3>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?>
