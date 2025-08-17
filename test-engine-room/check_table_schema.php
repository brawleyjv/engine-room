<?php
require_once 'test_db.php';
$pdo = getTestDatabase();
$stmt = $pdo->prepare('PRAGMA table_info(service_tracking)');
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Service tracking table columns:\n";
foreach ($columns as $col) {
    echo "- " . $col['name'] . " (" . $col['type'] . ")\n";
}
?>
