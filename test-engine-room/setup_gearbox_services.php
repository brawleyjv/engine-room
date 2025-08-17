<?php
/**
 * Setup Gearbox Service Tracking System
 * Creates service items for gearboxes: lube filter, lube strainer, oil change
 */

require_once 'test_db.php';
require_once 'log_helper.php';

echo "<h2>⚙️ Setting up Gearbox Service Tracking</h2>\n";

try {
    $pdo = getTestDatabase();
    
    // Add gearbox service items
    echo "<h3>Adding gearbox service items...</h3>\n";
    
    $gearbox_service_items = [
        ['gearbox_lube_filter', 'Gearbox Lube Filter', 'Filters', 1000, 'Gearbox lube oil filter replacement', 1, null],
        ['gearbox_lube_strainer', 'Gearbox Lube Strainer', 'Maintenance', 2000, 'Gearbox lube oil strainer cleaning/replacement', 1, null],
        ['gearbox_oil_change', 'Gearbox Oil Change', 'Oil Changes', 2000, 'Complete gearbox lube oil change', 1, null]
    ];
    
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO service_items 
        (item_code, item_name, category, default_interval_hours, description, is_universal, engine_models) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($gearbox_service_items as $item) {
        $stmt->execute($item);
        echo "<p>✅ Added: {$item[1]} ({$item[3]} hours)</p>\n";
    }
    
    // Initialize service settings for each active gearbox
    echo "<h3>Initializing service settings for active gearboxes...</h3>\n";
    
    // Get active gearboxes from settings
    $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM vessel_settings 
                                  WHERE setting_key LIKE 'gearbox_%_active'");
    $gearbox_settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $gearboxes = ['port_main', 'center_main', 'starboard_main'];
    $service_settings_stmt = $pdo->prepare("INSERT OR IGNORE INTO service_settings 
        (equipment_type, equipment_id, service_item_code, is_enabled, interval_hours) 
        VALUES ('gearbox', ?, ?, ?, ?)");
    
    foreach ($gearboxes as $gearbox) {
        $setting_key = 'gearbox_' . $gearbox . '_active';
        if (isset($gearbox_settings[$setting_key]) && $gearbox_settings[$setting_key] == '1') {
            echo "<h4>Setting up {$gearbox} gearbox services:</h4>\n";
            
            foreach ($gearbox_service_items as $item) {
                $service_settings_stmt->execute([$gearbox, $item[0], 1, $item[3]]);
                echo "<p style='margin-left: 20px;'>✅ Enabled: {$item[1]}</p>\n";
            }
        }
    }
    
    // Initialize service tracking records for enabled items
    echo "<h3>Initializing gearbox service tracking records...</h3>\n";
    
    $tracking_stmt = $pdo->prepare("INSERT OR IGNORE INTO service_tracking 
        (equipment_type, equipment_id, service_item_code, last_service_hours, next_service_hours) 
        SELECT equipment_type, equipment_id, service_item_code, 0, interval_hours 
        FROM service_settings WHERE equipment_type = 'gearbox' AND is_enabled = 1");
    $tracking_stmt->execute();
    
    echo "<p>✅ Initialized gearbox service tracking for enabled items</p>\n";
    
    // Create log entry
    createLogEntry($pdo, 'system', null, 
        'Gearbox service tracking system initialized with 3 service items: Lube Filter, Lube Strainer, Oil Change.', 
        'System');
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin: 20px 0;'>\n";
    echo "<h3>🎯 Gearbox Service Tracking Ready!</h3>\n";
    echo "<ul style='line-height: 1.8;'>\n";
    echo "<li><strong>3 Service Items:</strong> Lube Filter (1000hrs), Lube Strainer (2000hrs), Oil Change (2000hrs)</li>\n";
    echo "<li><strong>Universal settings</strong> apply to all active gearboxes</li>\n";
    echo "<li><strong>Service tracking</strong> integrated with gearbox hours updates</li>\n";
    echo "<li><strong>Dashboard alerts</strong> will show when services are due</li>\n";
    echo "</ul>\n";
    echo "<p><a href='settings.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Configure Gearbox Services</a></p>\n";
    echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>View Dashboard</a></p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545; margin: 20px 0;'>\n";
    echo "<h3>❌ Error Setting Up Gearbox Service Tracking</h3>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?>
