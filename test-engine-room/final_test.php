<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== FINAL COMPREHENSIVE TEST ===\n";

// Get current hours for all equipment
echo "CURRENT EQUIPMENT HOURS:\n";
$stmt = $pdo->query("
    SELECT 'engine' as type, engine_type as id, total_hours FROM engine_hours
    UNION ALL
    SELECT 'gearbox' as type, gearbox_type as id, total_hours FROM gearbox_hours
    UNION ALL
    SELECT 'generator' as type, generator_type as id, total_hours FROM generator_hours
    ORDER BY type, id
");
$equipment_hours = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $equipment_hours["{$row['type']}_{$row['id']}"] = $row['total_hours'];
    echo "  {$row['type']} {$row['id']}: {$row['total_hours']} hours\n";
}

echo "\nSERVICE ALERTS:\n";
// Check alerts for each equipment type
foreach (['engine', 'gearbox', 'generator'] as $equipment_type) {
    echo "\n{$equipment_type}s:\n";
    
    $hours_table = $equipment_type . '_hours';
    $id_column = $equipment_type . '_type';
    
    $stmt = $pdo->prepare("
        SELECT h.{$id_column} as equipment_id, h.total_hours,
               COUNT(CASE WHEN (h.total_hours - st.last_service_hours) >= ss.interval_hours THEN 1 END) as overdue_count,
               COUNT(CASE WHEN (h.total_hours - st.last_service_hours) >= (ss.interval_hours - 72) AND (h.total_hours - st.last_service_hours) < ss.interval_hours THEN 1 END) as due_soon_count
        FROM {$hours_table} h
        LEFT JOIN service_tracking st ON st.equipment_type = ? AND st.equipment_id = h.{$id_column}
        LEFT JOIN service_settings ss ON ss.equipment_type = ? AND ss.equipment_id = st.equipment_id AND ss.service_item_code = st.service_item_code
        GROUP BY h.{$id_column}, h.total_hours
        ORDER BY h.{$id_column}
    ");
    $stmt->execute([$equipment_type, $equipment_type]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $total_alerts = $row['overdue_count'] + $row['due_soon_count'];
        
        if ($total_alerts > 0) {
            echo "  {$row['equipment_id']} ({$row['total_hours']} hrs): ";
            $alerts = [];
            if ($row['overdue_count'] > 0) $alerts[] = "{$row['overdue_count']} overdue";
            if ($row['due_soon_count'] > 0) $alerts[] = "{$row['due_soon_count']} due soon";
            echo implode(', ', $alerts) . "\n";
        } else {
            echo "  {$row['equipment_id']} ({$row['total_hours']} hrs): All services up to date\n";
        }
    }
}

echo "\n=== TEST COMPLETED ===\n";
?>
