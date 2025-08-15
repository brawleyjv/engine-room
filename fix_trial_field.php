<?php
/**
 * Direct Database Fix for trial_start_date issue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Direct Database Fix</h2>\n";

// Connect to database
$conn = new mysqli('localhost', 'license_admin', 'Zhq4VNrT', 'vessel_license_master');

if ($conn->connect_error) {
    die("<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p style='color: green;'>✅ Database connected</p>\n";

// Check if trial_start_date field exists
$result = $conn->query("SHOW COLUMNS FROM companies LIKE 'trial_start_date'");
if ($result->num_rows == 0) {
    echo "<p style='color: orange;'>⚠️ trial_start_date field does not exist. Adding it...</p>\n";
    
    $sql = "ALTER TABLE companies ADD COLUMN trial_start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✅ Successfully added trial_start_date field</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Error adding trial_start_date: " . $conn->error . "</p>\n";
    }
} else {
    echo "<p style='color: blue;'>✓ trial_start_date field already exists</p>\n";
}

// Check all fields that signup needs
$required_fields = [
    'trial_start_date' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'trial_end_date' => 'TIMESTAMP NULL',
    'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
];

echo "<h3>Checking all required fields:</h3>\n";

foreach ($required_fields as $field => $definition) {
    $result = $conn->query("SHOW COLUMNS FROM companies LIKE '$field'");
    if ($result->num_rows == 0) {
        echo "<p style='color: orange;'>⚠️ Adding missing field: $field</p>\n";
        $sql = "ALTER TABLE companies ADD COLUMN $field $definition";
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✅ Added $field</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Error adding $field: " . $conn->error . "</p>\n";
        }
    } else {
        echo "<p style='color: blue;'>✓ $field exists</p>\n";
    }
}

// Show final table structure
echo "<h3>Final table structure:</h3>\n";
$result = $conn->query("DESCRIBE companies");
echo "<table border='1'>\n";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>\n";
}
echo "</table>\n";

$conn->close();

echo "<hr>\n";
echo "<p><strong>Database fix complete!</strong></p>\n";
echo "<p>Now try the signup process again.</p>\n";
?>
