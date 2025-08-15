<?php
/**
 * Database Setup Script for Vessel Sync System
 * Creates necessary tables for storing vessel data from logger devices
 */

require_once '../config.php';

echo "Setting up vessel sync database tables...\n";

try {
    // Read the SQL schema file
    $sql_file = __DIR__ . '/vessel_sync_schema.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("SQL schema file not found: $sql_file");
    }
    
    $sql = file_get_contents($sql_file);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            
            if ($conn->query($statement) === TRUE) {
                echo "✓ Success\n";
            } else {
                echo "✗ Error: " . $conn->error . "\n";
            }
        }
    }
    
    echo "\nDatabase setup completed!\n";
    
    // Test the connection by showing tables
    echo "\nCreated tables:\n";
    $result = $conn->query("SHOW TABLES LIKE 'vessel%'");
    if ($result) {
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
echo "\nSetup complete!\n";
?>
