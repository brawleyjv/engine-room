<?php
require_once 'config.php';

echo "<h2>Checking Current Table Structure</h2>";

$tables = ['mainengines', 'generators', 'gears'];

foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($table_check->num_rows == 0) {
        echo "❌ Table $table does not exist<br><br>";
        continue;
    }
    
    // Show current structure
    echo "Current columns:<br>";
    $columns = $conn->query("DESCRIBE $table");
    if ($columns) {
        while ($row = $columns->fetch_assoc()) {
            echo "&nbsp;&nbsp;- <strong>" . $row['Field'] . "</strong> (" . $row['Type'] . ") " . 
                 ($row['Key'] == 'PRI' ? '[PRIMARY KEY]' : '') . "<br>";
        }
    } else {
        echo "Error describing table: " . $conn->error . "<br>";
    }
    echo "<br>";
}

$conn->close();
?>

<p><a href="index.php">← Back to Home</a></p>
