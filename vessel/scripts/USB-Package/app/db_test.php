<?php
// Test SQLite database connection and operations
header('Content-Type: text/plain');

try {
    // Create SQLite database connection
    $db_path = __DIR__ . '/vessel_data.db';
    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ SQLite connection successful\n";
    
    // Test table creation
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        entry_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        message TEXT
    )");
    
    echo "✓ Table creation successful\n";
    
    // Test insert
    $stmt = $pdo->prepare("INSERT INTO test_logs (message) VALUES (?)");
    $stmt->execute(['Database test successful at ' . date('Y-m-d H:i:s')]);
    
    echo "✓ Insert operation successful\n";
    
    // Test select
    $stmt = $pdo->query("SELECT * FROM test_logs ORDER BY id DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "✓ Select operation successful\n";
        echo "Last entry: " . $row['message'] . "\n";
    }
    
    // Test database file exists
    if (file_exists($db_path)) {
        echo "✓ Database file created: " . $db_path . "\n";
        echo "File size: " . filesize($db_path) . " bytes\n";
    }
    
    echo "\n✅ All database tests passed!\n";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
