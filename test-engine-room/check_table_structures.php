<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== SERVICE SETTINGS TABLE STRUCTURE ===\n";
$stmt = $pdo->query("PRAGMA table_info(service_settings)");
echo "service_settings columns:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['name']} ({$row['type']})\n";
}

echo "\n=== SERVICE TRACKING TABLE STRUCTURE ===\n";
$stmt = $pdo->query("PRAGMA table_info(service_tracking)");
echo "service_tracking columns:\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  {$row['name']} ({$row['type']})\n";
}

echo "\n=== SAMPLE SERVICE TRACKING DATA ===\n";
$stmt = $pdo->prepare("
    SELECT * FROM service_tracking 
    WHERE equipment_type = 'engine' 
      AND equipment_id = 'port_main' 
      AND service_item_code = 'secondary_fuel_filter'
    LIMIT 1
");
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    foreach ($row as $key => $value) {
        echo "  $key: $value\n";
    }
} else {
    echo "No secondary fuel filter tracking found for port_main\n";
}
?>
