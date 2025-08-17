<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== REMOVING DUPLICATE SERVICE TRACKING RECORDS ===\n";

// Find and remove duplicates, keeping only the most recent record
$stmt = $pdo->prepare("
    SELECT equipment_type, equipment_id, service_item_code, COUNT(*) as count
    FROM service_tracking 
    GROUP BY equipment_type, equipment_id, service_item_code
    HAVING COUNT(*) > 1
    ORDER BY equipment_type, equipment_id, service_item_code
");
$stmt->execute();
$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_removed = 0;

foreach ($duplicates as $dup) {
    echo "Fixing {$dup['equipment_type']} {$dup['equipment_id']} {$dup['service_item_code']} ({$dup['count']} entries):\n";
    
    // Get all records for this combination, ordered by ID (keep the latest)
    $get_records = $pdo->prepare("
        SELECT id, created_at, updated_at, last_service_hours, next_service_hours
        FROM service_tracking 
        WHERE equipment_type = ? AND equipment_id = ? AND service_item_code = ?
        ORDER BY id
    ");
    $get_records->execute([$dup['equipment_type'], $dup['equipment_id'], $dup['service_item_code']]);
    $records = $get_records->fetchAll(PDO::FETCH_ASSOC);
    
    // Show all records
    foreach ($records as $i => $record) {
        $keep = ($i == count($records) - 1) ? " (KEEP)" : " (DELETE)";
        echo "  ID {$record['id']}: last_service={$record['last_service_hours']}, next_service={$record['next_service_hours']}, created={$record['created_at']}$keep\n";
    }
    
    // Delete all but the last record
    if (count($records) > 1) {
        $ids_to_delete = array_slice(array_column($records, 'id'), 0, -1);
        $placeholders = str_repeat('?,', count($ids_to_delete) - 1) . '?';
        
        $delete_stmt = $pdo->prepare("DELETE FROM service_tracking WHERE id IN ($placeholders)");
        $delete_stmt->execute($ids_to_delete);
        
        $removed = count($ids_to_delete);
        $total_removed += $removed;
        echo "  ✅ Removed $removed duplicate records\n";
    }
    
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "Total duplicate records removed: $total_removed\n";

// Verify cleanup
echo "\nVerifying cleanup...\n";
$stmt = $pdo->prepare("
    SELECT equipment_type, equipment_id, service_item_code, COUNT(*) as count
    FROM service_tracking 
    GROUP BY equipment_type, equipment_id, service_item_code
    HAVING COUNT(*) > 1
");
$stmt->execute();
$remaining_duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($remaining_duplicates) == 0) {
    echo "✅ All duplicates successfully removed!\n";
} else {
    echo "❌ Still " . count($remaining_duplicates) . " duplicate groups remaining\n";
}

echo "\n=== CLEANUP COMPLETED ===\n";
?>
