<?php
require_once 'test_db.php';

try {
    $pdo = getTestDatabase();
    
    echo "=== ENGINE HOURS TABLE STRUCTURE ===\n";
    $result = $pdo->query("PRAGMA table_info(engine_hours)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "❌ engine_hours table does not exist!\n";
    } else {
        echo "✅ engine_hours table exists with columns:\n";
        foreach ($columns as $col) {
            echo "  - {$col['name']} ({$col['type']})\n";
        }
    }
    
    echo "\n=== SAMPLE DATA ===\n";
    $data = $pdo->query("SELECT * FROM engine_hours LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($data)) {
        echo "❌ No data in engine_hours table\n";
    } else {
        foreach ($data as $row) {
            echo "  - " . json_encode($row) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
