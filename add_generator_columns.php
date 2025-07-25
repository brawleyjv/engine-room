<?php
// Add generator scale columns using PHP
require_once 'config.php';

echo "Adding generator scale columns...\n";

// Check if columns already exist
$check_sql = "SHOW COLUMNS FROM vessels LIKE 'GenMin'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    // Add GenMin column
    $sql1 = "ALTER TABLE vessels ADD COLUMN GenMin INT DEFAULT 20";
    if ($conn->query($sql1)) {
        echo "GenMin column added successfully.\n";
    } else {
        echo "Error adding GenMin column: " . $conn->error . "\n";
    }
    
    // Add GenMax column
    $sql2 = "ALTER TABLE vessels ADD COLUMN GenMax INT DEFAULT 400";
    if ($conn->query($sql2)) {
        echo "GenMax column added successfully.\n";
    } else {
        echo "Error adding GenMax column: " . $conn->error . "\n";
    }
    
    // Update existing vessels
    $sql3 = "UPDATE vessels SET GenMin = 20, GenMax = 400 WHERE GenMin IS NULL OR GenMax IS NULL";
    if ($conn->query($sql3)) {
        echo "Existing vessels updated with default generator scales.\n";
    } else {
        echo "Error updating existing vessels: " . $conn->error . "\n";
    }
} else {
    echo "GenMin column already exists.\n";
    
    // Check GenMax too
    $check_sql2 = "SHOW COLUMNS FROM vessels LIKE 'GenMax'";
    $result2 = $conn->query($check_sql2);
    if ($result2->num_rows == 0) {
        $sql2 = "ALTER TABLE vessels ADD COLUMN GenMax INT DEFAULT 400";
        if ($conn->query($sql2)) {
            echo "GenMax column added successfully.\n";
        } else {
            echo "Error adding GenMax column: " . $conn->error . "\n";
        }
    } else {
        echo "GenMax column already exists.\n";
    }
}

echo "Done!\n";
$conn->close();
?>
