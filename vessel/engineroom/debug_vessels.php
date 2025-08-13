<?php
include 'config.php';

echo "<h3>Current vessels in database:</h3>";
$result = mysqli_query($conn, "SELECT VesselID, VesselName, VesselType, IsActive FROM vessels ORDER BY VesselID");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>VesselID</th><th>VesselName</th><th>VesselType</th><th>IsActive</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['VesselID'] . "</td>";
        echo "<td>" . $row['VesselName'] . "</td>";
        echo "<td>" . $row['VesselType'] . "</td>";
        echo "<td>" . ($row['IsActive'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error querying vessels: " . mysqli_error($conn);
}

echo "<h3>Current session vessel:</h3>";
session_start();
if (isset($_SESSION['current_vessel_id'])) {
    echo "Session VesselID: " . $_SESSION['current_vessel_id'];
    
    // Check if this vessel exists
    $check = mysqli_query($conn, "SELECT VesselName FROM vessels WHERE VesselID = " . intval($_SESSION['current_vessel_id']));
    if ($check && mysqli_num_rows($check) > 0) {
        $vessel = mysqli_fetch_assoc($check);
        echo " (Valid - " . $vessel['VesselName'] . ")";
    } else {
        echo " (INVALID - vessel does not exist!)";
    }
} else {
    echo "No vessel selected in session";
}

echo "<h3>Database constraints check:</h3>";
$constraints = mysqli_query($conn, "
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE 
        CONSTRAINT_SCHEMA = 'vesseldata' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
        AND TABLE_NAME IN ('mainengines', 'generators', 'gears')
");

if ($constraints) {
    echo "<table border='1'>";
    echo "<tr><th>Constraint</th><th>Table</th><th>Column</th><th>References</th></tr>";
    while ($row = mysqli_fetch_assoc($constraints)) {
        echo "<tr>";
        echo "<td>" . $row['CONSTRAINT_NAME'] . "</td>";
        echo "<td>" . $row['TABLE_NAME'] . "</td>";
        echo "<td>" . $row['COLUMN_NAME'] . "</td>";
        echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "." . $row['REFERENCED_COLUMN_NAME'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
