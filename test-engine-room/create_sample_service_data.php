<?php
/**
 * Create Sample Service Data for Testing
 * Updates engine hours and creates service scenarios
 */

require_once 'test_db.php';
require_once 'log_helper.php';

echo "<h2>🔧 Creating Sample Service Data</h2>\n";

try {
    $pdo = getTestDatabase();
    
    // Update engine hours to create service scenarios
    echo "<h3>Updating engine hours for realistic service scenarios...</h3>\n";
    
    // Port engine - some services overdue, some due soon
    $pdo->exec("UPDATE engine_hours SET total_hours = 2750, hours_since_overhaul = 2750 WHERE engine_type = 'port_main'");
    echo "<p>✅ Port Main Engine: 2,750 hours (some filters due)</p>\n";
    
    // Starboard engine - fewer hours, mostly upcoming services
    $pdo->exec("UPDATE engine_hours SET total_hours = 1850, hours_since_overhaul = 1850 WHERE engine_type = 'starboard_main'");
    echo "<p>✅ Starboard Main Engine: 1,850 hours (services upcoming)</p>\n";
    
    // Create realistic service history for port engine
    echo "<h3>Creating service history for Port Main Engine...</h3>\n";
    
    // Lube oil filter - overdue (last done at 2250 hrs, 500 hr interval)
    $pdo->exec("UPDATE service_tracking SET 
        last_service_hours = 2250, 
        last_service_date = DATE('now', '-45 days'),
        next_service_hours = 2750,
        notes = 'Filter changed during routine maintenance',
        performed_by = 'Chief Engineer'
        WHERE equipment_type = 'engine' AND equipment_id = 'port_main' AND service_item_code = 'lube_oil_filter'");
    
    // Primary fuel filter - due soon (last done at 1800 hrs, 1000 hr interval) 
    $pdo->exec("UPDATE service_tracking SET 
        last_service_hours = 1800,
        last_service_date = DATE('now', '-95 days'), 
        next_service_hours = 2800,
        notes = 'Primary filter replaced with OEM part',
        performed_by = 'Marine Engineer'
        WHERE equipment_type = 'engine' AND equipment_id = 'port_main' AND service_item_code = 'primary_fuel_filter'");
    
    // Lube oil change - overdue (last done at 2200 hrs, 500 hr interval)
    $pdo->exec("UPDATE service_tracking SET 
        last_service_hours = 2200,
        last_service_date = DATE('now', '-60 days'),
        next_service_hours = 2700, 
        notes = 'Full oil change with 15W-40 marine grade',
        performed_by = 'Chief Engineer'
        WHERE equipment_type = 'engine' AND equipment_id = 'port_main' AND service_item_code = 'lube_oil_change'");
    
    // Secondary fuel filter - just completed
    $pdo->exec("UPDATE service_tracking SET 
        last_service_hours = 2740,
        last_service_date = DATE('now', '-5 days'),
        next_service_hours = 3240,
        notes = 'Secondary filter replaced due to contamination',
        performed_by = 'Marine Engineer' 
        WHERE equipment_type = 'engine' AND equipment_id = 'port_main' AND service_item_code = 'secondary_fuel_filter'");
    
    echo "<p>✅ Port Main service history created (2 overdue, 1 due soon)</p>\n";
    
    // Create service history for starboard engine - all good
    echo "<h3>Creating service history for Starboard Main Engine...</h3>\n";
    
    // Recent services for starboard engine
    $services = [
        ['lube_oil_filter', 1600, 2100, 'Filter replaced during PM'],
        ['primary_fuel_filter', 1200, 2200, 'Primary filter changed'],
        ['lube_oil_change', 1650, 2150, 'Oil changed with synthetic blend'],
        ['secondary_fuel_filter', 1550, 2050, 'Secondary filter maintenance'],
        ['air_intake_filter', 950, 1950, 'Air filter cleaned and inspected']
    ];
    
    foreach ($services as $service) {
        $pdo->prepare("UPDATE service_tracking SET 
            last_service_hours = ?, 
            last_service_date = DATE('now', '-' || CAST((2750 - ?) / 24 AS INTEGER) || ' days'),
            next_service_hours = ?,
            notes = ?,
            performed_by = 'Marine Engineer'
            WHERE equipment_type = 'engine' AND equipment_id = 'starboard_main' AND service_item_code = ?")
            ->execute([$service[1], $service[1], $service[2], $service[3], $service[0]]);
    }
    
    echo "<p>✅ Starboard Main service history created (all services current)</p>\n";
    
    // Create log entries for the service updates
    createLogEntry($pdo, 'system', null, 
        'Service tracking system populated with realistic maintenance history. Port Main: 2 overdue services, Starboard Main: all services current.', 
        'System');
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin: 20px 0;'>\n";
    echo "<h3>🎯 Sample Service Data Created!</h3>\n";
    echo "<ul style='line-height: 1.8;'>\n";
    echo "<li><strong>Port Main Engine:</strong> 2,750 hours with overdue services (oil change, lube filter)</li>\n";
    echo "<li><strong>Starboard Main Engine:</strong> 1,850 hours with current services</li>\n";
    echo "<li><strong>Service History:</strong> Realistic dates, notes, and performed-by data</li>\n";
    echo "<li><strong>Dashboard Alerts:</strong> Will show service status with color coding</li>\n";
    echo "<li><strong>Service Pages:</strong> Ready for completing maintenance tasks</li>\n";
    echo "</ul>\n";
    echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>View Dashboard</a></p>\n";
    echo "<p><a href='service_engine.php?type=port_main' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Port Main Service Page</a></p>\n";
    echo "</div>\n";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545; margin: 20px 0;'>\n";
    echo "<h3>❌ Error Creating Sample Data</h3>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "</div>\n";
}
?>
