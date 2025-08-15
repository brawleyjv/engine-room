<?php
echo "Vessel Logger PHP Test\n";
echo "PHP Version: " . phpversion() . "\n";
echo "SQLite Available: " . (extension_loaded('sqlite3') ? 'YES' : 'NO') . "\n";
echo "PDO SQLite Available: " . (extension_loaded('pdo_sqlite') ? 'YES' : 'NO') . "\n";
echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Path: " . __FILE__ . "\n";
?>
