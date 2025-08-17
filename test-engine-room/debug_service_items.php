<?php
/**
 * Debug Service Items - Check for duplicates
 */

require_once 'test_db.php';

try {
    $pdo = getTestDatabase();
    
    echo "<h2>🔍 Service Items Debug</h2>\n";
    
    // Check all service items
    $stmt = $pdo->query("SELECT item_code, item_name, category, description FROM service_items ORDER BY category, item_name");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Service Items:</h3>\n";
    $categories = [];
    foreach ($items as $item) {
        $categories[$item['category']][] = $item;
    }
    
    foreach ($categories as $category => $category_items) {
        echo "<h4>{$category}:</h4>\n";
        foreach ($category_items as $item) {
            echo "<p>• <strong>{$item['item_name']}</strong> ({$item['item_code']}) - {$item['description']}</p>\n";
        }
    }
    
    // Check for duplicates by name
    echo "<h3>Checking for duplicate names:</h3>\n";
    $names = array_column($items, 'item_name');
    $name_counts = array_count_values($names);
    $duplicates = array_filter($name_counts, function($count) { return $count > 1; });
    
    if (empty($duplicates)) {
        echo "<p>✅ No duplicate names found</p>\n";
    } else {
        echo "<p>❌ Duplicates found:</p>\n";
        foreach ($duplicates as $name => $count) {
            echo "<p>• <strong>{$name}</strong> appears {$count} times</p>\n";
        }
    }
    
    // Check service settings for port_main
    echo "<h3>Service Settings for Port Main Engine:</h3>\n";
    $settings_stmt = $pdo->query("
        SELECT ss.service_item_code, si.item_name, ss.is_enabled, ss.interval_hours
        FROM service_settings ss
        JOIN service_items si ON ss.service_item_code = si.item_code
        WHERE ss.equipment_type = 'engine' AND ss.equipment_id = 'port_main'
        ORDER BY si.category, si.item_name
    ");
    $settings = $settings_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($settings as $setting) {
        $status = $setting['is_enabled'] ? '✅ Enabled' : '❌ Disabled';
        echo "<p>{$status}: {$setting['item_name']} ({$setting['service_item_code']}) - {$setting['interval_hours']} hours</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
