<?php
session_start();
require_once 'config.php';
require_once 'vessel_functions.php';

set_active_vessel(4, 'Test Vessel');

echo "<h2>Debug Center Main Data</h2>";

// Check what mainengines data exists for Test Vessel
echo "<h3>All mainengines data for Test Vessel (ID: 4):</h3>";
$sql = "SELECT EntryID, EntryDate, Side, RPM, MainHrs, VesselID 
        FROM mainengines 
        WHERE VesselID = 4 
        ORDER BY EntryDate DESC";

$result = $conn->query($sql);
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>EntryID</th><th>Date</th><th>Side</th><th>RPM</th><th>MainHrs</th><th>VesselID</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['Side'] === 'Center Main') ? "style='background-color: yellow;'" : "";
        echo "<tr {$highlight}>";
        echo "<td>{$row['EntryID']}</td>";
        echo "<td>{$row['EntryDate']}</td>";
        echo "<td><strong>{$row['Side']}</strong></td>";
        echo "<td>{$row['RPM']}</td>";
        echo "<td>{$row['MainHrs']}</td>";
        echo "<td>{$row['VesselID']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count by side
    echo "<h3>Count by Side:</h3>";
    $count_sql = "SELECT Side, COUNT(*) as count 
                  FROM mainengines 
                  WHERE VesselID = 4 
                  GROUP BY Side 
                  ORDER BY Side";
    $count_result = $conn->query($count_sql);
    while ($count_row = $count_result->fetch_assoc()) {
        echo "Side: <strong>{$count_row['Side']}</strong> - Count: {$count_row['count']}<br>";
    }
    
} else {
    echo "No mainengines data found for Test Vessel!<br>";
}

// Check all distinct sides in mainengines table
echo "<h3>All distinct sides in mainengines table:</h3>";
$sides_sql = "SELECT DISTINCT Side, COUNT(*) as count FROM mainengines GROUP BY Side ORDER BY Side";
$sides_result = $conn->query($sides_sql);
while ($side_row = $sides_result->fetch_assoc()) {
    echo "'{$side_row['Side']}' - Count: {$side_row['count']}<br>";
}

// Test the exact query that the search uses
echo "<h3>Testing search query with Center Main filter:</h3>";
$test_sql = "SELECT e.*, COALESCE(CONCAT(u.FirstName, ' ', u.LastName), 'Unknown User') as RecordedByName 
             FROM mainengines e 
             LEFT JOIN users u ON e.RecordedBy = u.UserID 
             WHERE e.VesselID = ? AND e.Side = ?
             ORDER BY e.EntryDate DESC";

$test_stmt = $conn->prepare($test_sql);
$vessel_id = 4;
$side = 'Center Main';
$test_stmt->bind_param('is', $vessel_id, $side);
$test_stmt->execute();
$test_result = $test_stmt->get_result();

echo "Query: {$test_sql}<br>";
echo "Parameters: VesselID={$vessel_id}, Side='{$side}'<br>";
echo "Results: {$test_result->num_rows} rows<br>";

if ($test_result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>EntryID</th><th>Date</th><th>Side</th><th>RPM</th></tr>";
    while ($test_row = $test_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$test_row['EntryID']}</td>";
        echo "<td>{$test_row['EntryDate']}</td>";
        echo "<td>{$test_row['Side']}</td>";
        echo "<td>{$test_row['RPM']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<br><br>";
echo "<a href='debug_view_logs_test.php?equipment=mainengines'>Back to Debug View Logs</a>";
?>
