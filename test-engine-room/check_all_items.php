<?php
require_once 'test_db.php';
$pdo = getTestDatabase();

echo "All service items:\n";
$stmt = $pdo->query('SELECT * FROM service_items ORDER BY item_name');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  " . $row['item_code'] . " - " . $row['item_name'] . " (" . $row['category'] . ")\n";
}

echo "\nGenerator-related service items:\n";
$stmt = $pdo->query("SELECT * FROM service_items WHERE item_code LIKE '%oil%' OR item_code LIKE '%filter%' ORDER BY item_name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  " . $row['item_code'] . " - " . $row['item_name'] . " (" . $row['category'] . ")\n";
}
?>
