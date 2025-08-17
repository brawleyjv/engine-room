<?php
/**
 * Fix Service Tracking Duplicates
 */

require_once 'test_db.php';

try {
    $pdo = getTestDatabase();
    
    echo "<h2>🧹 Fixing Service Tracking Duplicates</h2>\n";
    
    // Find and remove duplicate tracking entries, keeping the ones with actual service data
    echo "<h3>Removing duplicate service_tracking entries...</h3>\n";
    
    // For each service item, keep only the entry with the most recent service data
    $duplicates_query = "
        SELECT equipment_type, equipment_id, service_item_code, COUNT(*) as count 
        FROM service_tracking 
        WHERE equipment_type = 'engine'
        GROUP BY equipment_type, equipment_id, service_item_code 
        HAVING count > 1
    ";
    
    $stmt = $pdo->query($duplicates_query);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($duplicates as $duplicate) {
        $eq_type = $duplicate['equipment_type'];
        $eq_id = $duplicate['equipment_id'];
        $service_code = $duplicate['service_item_code'];
        
        echo "<p>Fixing duplicates for {$eq_id} - {$service_code}...</p>\n";
        
        // Get all entries for this combination
        $entries_stmt = $pdo->prepare("
            SELECT id, last_service_hours, last_service_date, notes, performed_by, updated_at
            FROM service_tracking 
            WHERE equipment_type = ? AND equipment_id = ? AND service_item_code = ?
            ORDER BY 
                CASE WHEN last_service_hours > 0 THEN 1 ELSE 2 END,
                updated_at DESC
        ");
        $entries_stmt->execute([$eq_type, $eq_id, $service_code]);
        $entries = $entries_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Keep the first one (best data), delete the rest
        $keep_id = $entries[0]['id'];
        $delete_ids = array_slice(array_column($entries, 'id'), 1);
        
        if (!empty($delete_ids)) {
            $placeholders = str_repeat('?,', count($delete_ids) - 1) . '?';
            $delete_stmt = $pdo->prepare("DELETE FROM service_tracking WHERE id IN ($placeholders)");
            $delete_stmt->execute($delete_ids);
            
            echo "<p>✅ Kept ID {$keep_id}, deleted " . count($delete_ids) . " duplicates</p>\n";
        }
    }
    
    // Also clean up any orphaned service_settings (disabled ones we don't need)
    echo "<h3>Cleaning up service settings...</h3>\n";
    
    // Re-enable lube oil change for port main (it got disabled somehow)
    $pdo->exec("UPDATE service_settings SET is_enabled = 1 WHERE equipment_type = 'engine' AND equipment_id = 'port_main' AND service_item_code = 'lube_oil_change'");
    echo "<p>✅ Re-enabled Lube Oil Change for Port Main</p>\n";
    
    // Update coolant conditioner filter interval to a more reasonable value
    $pdo->exec("UPDATE service_settings SET interval_hours = 2000 WHERE equipment_type = 'engine' AND equipment_id = 'port_main' AND service_item_code = 'coolant_conditioner_filter'");
    echo "<p>✅ Fixed Coolant Conditioner Filter interval</p>\n";
    
    // Verify the fix
    echo "<h3>Verification - Service Tracking Counts:</h3>\n";
    $verification_stmt = $pdo->query("
        SELECT equipment_id, service_item_code, COUNT(*) as count
        FROM service_tracking 
        WHERE equipment_type = 'engine'
        GROUP BY equipment_id, service_item_code 
        ORDER BY equipment_id, service_item_code
    ");
    $verification = $verification_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $duplicates_remain = false;
    foreach ($verification as $v) {
        if ($v['count'] > 1) {
            echo "<p>❌ Still duplicate: {$v['equipment_id']} - {$v['service_item_code']} ({$v['count']} entries)</p>\n";
            $duplicates_remain = true;
        } else {
            echo "<p>✅ {$v['equipment_id']} - {$v['service_item_code']} (1 entry)</p>\n";
        }
    }
    
    if (!$duplicates_remain) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 6px; border-left: 4px solid #28a745; margin: 15px 0;'>\n";
        echo "<h4>✅ All Duplicates Fixed!</h4>\n";
        echo "<p>Service tracking table cleaned up successfully. Each service item now has only one tracking entry per engine.</p>\n";
        echo "</div>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
