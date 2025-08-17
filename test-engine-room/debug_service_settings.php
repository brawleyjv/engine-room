<?php
/**
 * Debug Service Settings - Check for duplicates and inconsistencies
 */

require_once 'test_db.php';

try {
    $pdo = getTestDatabase();
    
    echo "<h2>🔍 Service Settings Debug</h2>\n";
    
    // Check for duplicate service_settings entries
    echo "<h3>All Service Settings for Port Main:</h3>\n";
    $stmt = $pdo->query("
        SELECT ss.id, ss.service_item_code, si.item_name, ss.is_enabled, ss.interval_hours
        FROM service_settings ss
        LEFT JOIN service_items si ON ss.service_item_code = si.item_code
        WHERE ss.equipment_type = 'engine' AND ss.equipment_id = 'port_main'
        ORDER BY ss.service_item_code, ss.id
    ");
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $codes = [];
    foreach ($settings as $setting) {
        if (isset($codes[$setting['service_item_code']])) {
            echo "<p>❌ DUPLICATE: {$setting['item_name']} ({$setting['service_item_code']}) - ID: {$setting['id']}</p>\n";
        } else {
            $codes[$setting['service_item_code']] = true;
            $status = $setting['is_enabled'] ? '✅' : '❌';
            echo "<p>{$status} {$setting['item_name']} ({$setting['service_item_code']}) - {$setting['interval_hours']} hours - ID: {$setting['id']}</p>\n";
        }
    }
    
    // Check service_tracking table
    echo "<h3>Service Tracking Entries for Port Main:</h3>\n";
    $tracking_stmt = $pdo->query("
        SELECT st.id, st.service_item_code, si.item_name, st.last_service_hours, st.next_service_hours
        FROM service_tracking st
        LEFT JOIN service_items si ON st.service_item_code = si.item_code
        WHERE st.equipment_type = 'engine' AND st.equipment_id = 'port_main'
        ORDER BY st.service_item_code, st.id
    ");
    $tracking = $tracking_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tracking_codes = [];
    foreach ($tracking as $track) {
        if (isset($tracking_codes[$track['service_item_code']])) {
            echo "<p>❌ DUPLICATE TRACKING: {$track['item_name']} ({$track['service_item_code']}) - ID: {$track['id']}</p>\n";
        } else {
            $tracking_codes[$track['service_item_code']] = true;
            echo "<p>📊 {$track['item_name']} ({$track['service_item_code']}) - Last: {$track['last_service_hours']}, Next: {$track['next_service_hours']} - ID: {$track['id']}</p>\n";
        }
    }
    
    // Test the exact query used in service_engine.php
    echo "<h3>Testing Service Page Query:</h3>\n";
    $current_engine_hours = 2750;
    $engine_type = 'port_main';
    
    $services_stmt = $pdo->prepare("
        SELECT si.item_code, si.item_name, si.category, si.description,
               st.last_service_hours, st.last_service_date, st.next_service_hours,
               st.notes, st.performed_by, ss.interval_hours, ss.is_enabled,
               (? - st.last_service_hours) as hours_since_service,
               CASE 
                   WHEN (? - st.last_service_hours) >= st.next_service_hours THEN 'overdue'
                   WHEN (? - st.last_service_hours) >= (st.next_service_hours * 0.9) THEN 'due_soon'
                   WHEN (? - st.last_service_hours) >= (st.next_service_hours * 0.8) THEN 'upcoming'
                   ELSE 'good'
               END as status
        FROM service_items si
        JOIN service_settings ss ON si.item_code = ss.service_item_code
        LEFT JOIN service_tracking st ON si.item_code = st.service_item_code 
                                      AND st.equipment_type = 'engine' 
                                      AND st.equipment_id = ?
        WHERE ss.equipment_type = 'engine' 
          AND ss.equipment_id = ? 
          AND ss.is_enabled = 1
        ORDER BY si.category, 
                 CASE status 
                     WHEN 'overdue' THEN 1 
                     WHEN 'due_soon' THEN 2 
                     WHEN 'upcoming' THEN 3 
                     ELSE 4 
                 END,
                 si.item_name
    ");
    $services_stmt->execute([
        $current_engine_hours, $current_engine_hours, $current_engine_hours, $current_engine_hours,
        $engine_type, $engine_type
    ]);
    $services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Query returned " . count($services) . " items:</p>\n";
    foreach ($services as $service) {
        echo "<p>• {$service['item_name']} ({$service['category']}) - Status: {$service['status']}</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
