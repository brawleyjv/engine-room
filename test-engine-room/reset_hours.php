<?php
require_once 'test_db.php';
$pdo = getTestDatabase();
$pdo->exec('UPDATE gearbox_hours SET total_hours = 250 WHERE gearbox_type = "port_gearbox"');
$pdo->exec('UPDATE gearbox_hours SET total_hours = 150 WHERE gearbox_type = "starboard_gearbox"');
echo "Reset gearbox hours to reasonable testing levels.\n";
?>
