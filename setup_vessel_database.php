<?php
/**
 * Setup Vessel Database Tables
 * Run this script to create the necessary tables for vessel data sync
 */

require_once 'config.php';

echo "<h2>Setting up Vessel Database Tables</h2>\n";

// Read and execute the SQL schema
$sql_file = 'vessel_database_schema.sql';

if (!file_exists($sql_file)) {
    die("Error: SQL file not found: $sql_file\n");
}

$sql = file_get_contents($sql_file);

// Split SQL into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

echo "<pre>\n";

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    echo "Executing: " . substr($statement, 0, 50) . "...\n";
    
    if ($conn->query($statement)) {
        echo "✓ Success\n\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n\n";
    }
}

echo "</pre>\n";

// Test the setup by checking tables
echo "<h3>Verifying Tables</h3>\n";
echo "<pre>\n";

$tables = ['vessels', 'vessel_logs', 'navigation_data'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ Table '$table' exists\n";
        
        // Show count
        $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $count_result->fetch_assoc()['count'];
        echo "  - Contains $count records\n";
    } else {
        echo "✗ Table '$table' missing\n";
    }
}

echo "\n</pre>\n";

echo "<h3>Database Setup Complete!</h3>\n";
echo "<p><a href='office_vessel_dashboard.php'>View Office Vessel Dashboard</a></p>\n";

$conn->close();
?>
