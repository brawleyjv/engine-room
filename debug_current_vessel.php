<?php
session_start();
require_once 'config.php';
require_once 'vessel_functions.php';

echo "<h2>Current Vessel Debug Information</h2>";

// Show session variables
echo "<h3>Session Variables:</h3>";
echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'NOT SET') . "<br>";
echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'NOT SET') . "<br>";
echo "current_vessel_name: " . ($_SESSION['current_vessel_name'] ?? 'NOT SET') . "<br>";

// Show current vessel from get_current_vessel function
echo "<h3>get_current_vessel() result:</h3>";
$current_vessel = get_current_vessel($conn);
if ($current_vessel) {
    echo "VesselID: " . $current_vessel['VesselID'] . "<br>";
    echo "VesselName: " . $current_vessel['VesselName'] . "<br>";
    echo "VesselType: " . $current_vessel['VesselType'] . "<br>";
    echo "IsActive: " . $current_vessel['IsActive'] . "<br>";
} else {
    echo "No current vessel found<br>";
}

// Show all vessels
echo "<h3>All Vessels in Database:</h3>";
$sql = "SELECT VesselID, VesselName, VesselType, IsActive FROM vessels ORDER BY VesselID";
$result = $conn->query($sql);
while ($vessel = $result->fetch_assoc()) {
    echo "ID: {$vessel['VesselID']}, Name: {$vessel['VesselName']}, Type: {$vessel['VesselType']}, Active: {$vessel['IsActive']}<br>";
}

// Show data counts for each vessel
echo "<h3>Data Counts by Vessel:</h3>";
$tables = ['mainengines', 'generators', 'gears'];
foreach ($tables as $table) {
    echo "<h4>{$table}:</h4>";
    $sql = "SELECT v.VesselName, COUNT(e.VesselID) as count 
            FROM vessels v 
            LEFT JOIN {$table} e ON v.VesselID = e.VesselID 
            GROUP BY v.VesselID, v.VesselName 
            ORDER BY v.VesselID";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        echo "Vessel: {$row['VesselName']}, Records: {$row['count']}<br>";
    }
}

$conn->close();
?>
