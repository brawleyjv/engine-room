<?php
$pdo = new PDO('sqlite:vessel_data.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Debugging engine log insert...\n";
echo "==============================\n";

// Check vessel_logs table structure
echo "vessel_logs table structure:\n";
$columns = $pdo->query("PRAGMA table_info(vessel_logs)")->fetchAll();
foreach($columns as $col) {
    $nullable = $col[3] ? 'NOT NULL' : 'NULL';
    $default = $col[4] ? "DEFAULT {$col[4]}" : '';
    echo "  {$col[1]} ({$col[2]}) {$nullable} {$default}\n";
}

// Try the exact insert that's failing
echo "\nTrying minimal insert...\n";
try {
    $stmt = $pdo->prepare("INSERT INTO vessel_logs (log_type, entry_text, author, created_at) VALUES ('engine', 'Test entry', 'vessel_logger', ?)");
    $result = $stmt->execute([date('Y-m-d H:i:s')]);
    echo "Minimal insert: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
} catch (Exception $e) {
    echo "Minimal insert error: " . $e->getMessage() . "\n";
}

// Try with all possible fields
echo "\nTrying full insert...\n";
try {
    $stmt = $pdo->prepare("INSERT INTO vessel_logs (log_type, entry_text, author, author_role, position, created_at) VALUES ('engine', 'Test entry full', 'vessel_logger', 'system', '', ?)");
    $result = $stmt->execute([date('Y-m-d H:i:s')]);
    echo "Full insert: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
} catch (Exception $e) {
    echo "Full insert error: " . $e->getMessage() . "\n";
}

echo "\nDebug complete.\n";
?>
