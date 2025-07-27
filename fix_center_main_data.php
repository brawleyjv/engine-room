<?php
session_start();
require_once 'config.php';
require_once 'vessel_functions.php';

set_active_vessel(4, 'Test Vessel');

echo "<h2>Fix Center Main Data</h2>";

echo "<h3>Current problematic data:</h3>";
$current_sql = "SELECT EntryID, EntryDate, Side, RPM, MainHrs, VesselID 
                FROM mainengines 
                WHERE VesselID = 4";
$current_result = $conn->query($current_sql);

echo "<table border='1'>";
echo "<tr><th>EntryID</th><th>Date</th><th>Side</th><th>RPM</th><th>MainHrs</th><th>VesselID</th></tr>";
while ($row = $current_result->fetch_assoc()) {
    $side_display = ($row['Side'] === '') ? '[EMPTY]' : $row['Side'];
    echo "<tr>";
    echo "<td>{$row['EntryID']}</td>";
    echo "<td>{$row['EntryDate']}</td>";
    echo "<td><strong>{$side_display}</strong></td>";
    echo "<td>{$row['RPM']}</td>";
    echo "<td>{$row['MainHrs']}</td>";
    echo "<td>{$row['VesselID']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Fixing the data...</h3>";

// Update the empty side to be "Center Main"
$update_sql = "UPDATE mainengines SET Side = 'Center Main' WHERE VesselID = 4 AND (Side = '' OR Side IS NULL)";
$update_result = $conn->query($update_sql);

if ($update_result) {
    echo "✅ Updated {$conn->affected_rows} record(s)<br>";
} else {
    echo "❌ Error updating: " . $conn->error . "<br>";
}

echo "<h3>Data after fix:</h3>";
$after_sql = "SELECT EntryID, EntryDate, Side, RPM, MainHrs, VesselID 
              FROM mainengines 
              WHERE VesselID = 4";
$after_result = $conn->query($after_sql);

echo "<table border='1'>";
echo "<tr><th>EntryID</th><th>Date</th><th>Side</th><th>RPM</th><th>MainHrs</th><th>VesselID</th></tr>";
while ($row = $after_result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['EntryID']}</td>";
    echo "<td>{$row['EntryDate']}</td>";
    echo "<td><strong>{$row['Side']}</strong></td>";
    echo "<td>{$row['RPM']}</td>";
    echo "<td>{$row['MainHrs']}</td>";
    echo "<td>{$row['VesselID']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Testing search now:</h3>";
$test_sql = "SELECT COUNT(*) as count FROM mainengines WHERE VesselID = 4 AND Side = 'Center Main'";
$test_result = $conn->query($test_sql);
$test_row = $test_result->fetch_assoc();
echo "Records with Side = 'Center Main': <strong>{$test_row['count']}</strong><br>";

echo "<br><br>";
echo "✅ <strong>Now try the view logs again!</strong><br><br>";
echo "<a href='debug_view_logs_test.php?equipment=mainengines&side=Center+Main' style='padding: 10px; background: #28a745; color: white; text-decoration: none;'>🔍 Test Search with Center Main</a><br><br>";
echo "<a href='view_logs.php?equipment=mainengines' style='padding: 10px; background: #007cba; color: white; text-decoration: none;'>📊 Go to Regular View Logs</a>";
?>
