<?php
session_start();
require_once 'config.php';
require_once 'vessel_functions.php';

echo "<h2>Debug Vessel Engine Configuration</h2>";

// Get current vessel
$current_vessel = get_current_vessel($conn);
echo "<h3>Current Vessel:</h3>";
if ($current_vessel) {
    echo "VesselID: " . $current_vessel['VesselID'] . "<br>";
    echo "VesselName: " . $current_vessel['VesselName'] . "<br>";
} else {
    echo "No vessel selected<br>";
    exit;
}

// Check the EngineConfig directly from database
echo "<h3>Database EngineConfig Check:</h3>";
$sql = "SELECT VesselID, VesselName, EngineConfig FROM vessels WHERE VesselID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $current_vessel['VesselID']);
$stmt->execute();
$result = $stmt->get_result();
$vessel_data = $result->fetch_assoc();

if ($vessel_data) {
    echo "VesselID: " . $vessel_data['VesselID'] . "<br>";
    echo "VesselName: " . $vessel_data['VesselName'] . "<br>";
    echo "EngineConfig: " . ($vessel_data['EngineConfig'] ?? 'NULL/NOT SET') . "<br>";
} else {
    echo "Vessel not found in database!<br>";
}

// Test get_vessel_sides function
echo "<h3>get_vessel_sides() result:</h3>";
$sides = get_vessel_sides($conn, $current_vessel['VesselID']);
echo "Available sides: " . implode(', ', $sides) . "<br>";

// Show all vessels and their EngineConfig
echo "<h3>All Vessels EngineConfig:</h3>";
$all_sql = "SELECT VesselID, VesselName, EngineConfig FROM vessels ORDER BY VesselID";
$all_result = $conn->query($all_sql);

echo "<table border='1'>";
echo "<tr><th>VesselID</th><th>VesselName</th><th>EngineConfig</th><th>Expected Sides</th></tr>";
while ($row = $all_result->fetch_assoc()) {
    $expected_sides = ($row['EngineConfig'] === 'three_engine') ? 'Port, Center Main, Starboard' : 'Port, Starboard';
    echo "<tr>";
    echo "<td>{$row['VesselID']}</td>";
    echo "<td>{$row['VesselName']}</td>";
    echo "<td>" . ($row['EngineConfig'] ?? 'NULL') . "</td>";
    echo "<td>{$expected_sides}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br>";
echo "<a href='view_logs.php?equipment=mainengines'>Test View Logs</a> | ";
echo "<a href='manage_vessels.php'>Manage Vessels</a>";
?>
