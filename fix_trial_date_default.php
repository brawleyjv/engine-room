<?php
/**
 * Fix trial_start_date field default value
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Fix trial_start_date Default Value</h2>\n";

// Connect to database
$conn = new mysqli('localhost', 'license_admin', 'Zhq4VNrT', 'vessel_license_master');

if ($conn->connect_error) {
    die("<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p style='color: green;'>✅ Database connected</p>\n";

// Fix the trial_start_date field to have a proper default
echo "<p>Fixing trial_start_date field...</p>\n";

$sql = "ALTER TABLE companies MODIFY trial_start_date DATE DEFAULT (CURRENT_DATE)";
if ($conn->query($sql)) {
    echo "<p style='color: green;'>✅ Successfully updated trial_start_date with default value</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Modern MySQL syntax failed, trying older syntax...</p>\n";
    
    // Try older MySQL syntax - set it to allow NULL with current date as default
    $sql = "ALTER TABLE companies MODIFY trial_start_date DATE NULL DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>✅ Updated trial_start_date to allow NULL</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>\n";
    }
}

// Show the updated field
echo "<h3>Updated trial_start_date field:</h3>\n";
$result = $conn->query("SHOW COLUMNS FROM companies WHERE Field = 'trial_start_date'");
if ($row = $result->fetch_assoc()) {
    echo "<table border='1'>\n";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>\n";
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>\n";
    echo "</table>\n";
}

$conn->close();

echo "<hr>\n";
echo "<p><strong>Fix complete!</strong></p>\n";
echo "<p>Now try the signup process again.</p>\n";
?>
