<?php
session_start();
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

echo "<h2>Debug View Logs</h2>";

// Debug session
echo "<h3>Session Variables:</h3>";
echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'NOT SET') . "<br>";
echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'NOT SET') . "<br>";

// Get current vessel
$current_vessel = get_current_vessel($conn);
echo "<h3>Current Vessel from get_current_vessel():</h3>";
if ($current_vessel) {
    echo "VesselID: " . $current_vessel['VesselID'] . "<br>";
    echo "VesselName: " . $current_vessel['VesselName'] . "<br>";
    echo "EngineConfig: " . ($current_vessel['EngineConfig'] ?? 'NOT SET') . "<br>";
    
    // Test the get_vessel_sides function
    $sides = get_vessel_sides($conn, $current_vessel['VesselID']);
    echo "Available sides: " . implode(', ', $sides) . "<br>";
} else {
    echo "NULL - No vessel found<br>";
}

// Test query for mainengines
if ($current_vessel) {
    echo "<h3>Testing mainengines query with VesselID {$current_vessel['VesselID']}:</h3>";
    
    $sql = "SELECT e.*, COALESCE(CONCAT(u.FirstName, ' ', u.LastName), 'Unknown User') as RecordedByName 
            FROM mainengines e 
            LEFT JOIN users u ON e.RecordedBy = u.UserID 
            WHERE e.VesselID = ?
            ORDER BY e.EntryDate DESC LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $current_vessel['VesselID']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "Query: " . $sql . "<br>";
    echo "VesselID parameter: " . $current_vessel['VesselID'] . "<br>";
    echo "Results found: " . $result->num_rows . "<br><br>";
    
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>EntryDate</th><th>Side</th><th>VesselID</th><th>RPM</th><th>RecordedBy</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['EntryDate']}</td>";
            echo "<td>{$row['Side']}</td>";
            echo "<td>{$row['VesselID']}</td>";
            echo "<td>{$row['RPM']}</td>";
            echo "<td>{$row['RecordedByName']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No records found for this vessel.";
    }
}

// Show ALL mainengines data for comparison
echo "<h3>ALL mainengines data (for comparison):</h3>";
$all_sql = "SELECT e.VesselID, v.VesselName, e.EntryDate, e.Side, e.RPM 
            FROM mainengines e 
            LEFT JOIN vessels v ON e.VesselID = v.VesselID 
            ORDER BY e.EntryDate DESC LIMIT 20";
$all_result = $conn->query($all_sql);

echo "<table border='1'>";
echo "<tr><th>VesselID</th><th>VesselName</th><th>EntryDate</th><th>Side</th><th>RPM</th></tr>";
while ($row = $all_result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['VesselID']}</td>";
    echo "<td>{$row['VesselName']}</td>";
    echo "<td>{$row['EntryDate']}</td>";
    echo "<td>{$row['Side']}</td>";
    echo "<td>{$row['RPM']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><br>";
echo "<a href='test_vessel_switch.php'>Test Vessel Switching</a> | ";
echo "<a href='manage_vessels.php'>Manage Vessels</a> | ";
echo "<a href='view_logs.php?equipment=mainengines'>Normal View Logs</a>";
?>
