<?php
require_once 'config.php';

echo "<h2>Database Schema Analysis</h2>";

// Check the structure of all tables
$tables = ['mainengines', 'generators', 'gears'];

foreach ($tables as $table) {
    echo "<h3>$table Table Structure:</h3>";
    
    $result = $conn->query("DESCRIBE $table");
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $highlight = ($row['Field'] === 'Side') ? 'background-color: yellow;' : '';
            echo "<tr style='$highlight'>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?: 'NULL') . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "Error describing table: " . $conn->error . "<br><br>";
    }
}

// Check if there's something wrong with the Side column specifically
echo "<h3>Side Column Comparison:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Table</th><th>Side Column Details</th></tr>";

foreach ($tables as $table) {
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE 'Side'");
    if ($result && $row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td>";
        foreach ($row as $key => $value) {
            echo "<strong>$key:</strong> " . ($value ?: 'NULL') . "<br>";
        }
        echo "</td>";
        echo "</tr>";
    } else {
        echo "<tr><td>$table</td><td>No Side column found or error</td></tr>";
    }
}
echo "</table>";

echo "<br><p><a href='add_log.php'>← Back to Add Log</a></p>";
?>
