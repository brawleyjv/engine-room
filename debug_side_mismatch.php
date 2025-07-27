<?php
session_start();
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

require_login();

echo "<h1>Debug: Side Data Mismatch Investigation</h1>";

// Get current vessel
$current_vessel = get_current_vessel($conn);
if (!$current_vessel) {
    echo "❌ No current vessel found<br>";
    exit;
}

echo "<h2>Current Vessel:</h2>";
echo "VesselID: " . $current_vessel['VesselID'] . "<br>";
echo "VesselName: " . $current_vessel['VesselName'] . "<br>";
echo "EngineConfig: " . $current_vessel['EngineConfig'] . "<br>";

// Get available sides from function
$sides_from_function = get_vessel_sides($conn, $current_vessel['VesselID']);
echo "<h2>Sides from get_vessel_sides() function:</h2>";
foreach ($sides_from_function as $side) {
    echo "- '" . htmlspecialchars($side) . "'<br>";
}

// Check actual data in mainengines table
echo "<h2>Actual Side values in mainengines table:</h2>";
$sql = "SELECT DISTINCT Side, COUNT(*) as count FROM mainengines WHERE VesselID = ? GROUP BY Side ORDER BY Side";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $current_vessel['VesselID']);
$stmt->execute();
$result = $stmt->get_result();

$actual_sides = [];
while ($row = $result->fetch_assoc()) {
    $actual_sides[] = $row;
    $side_display = $row['Side'] === '' ? '[EMPTY STRING]' : "'" . htmlspecialchars($row['Side']) . "'";
    echo "- $side_display (Count: " . $row['count'] . ")<br>";
}

// Check generators table too
echo "<h2>Actual Side values in generators table:</h2>";
$sql = "SELECT DISTINCT Side, COUNT(*) as count FROM generators WHERE VesselID = ? GROUP BY Side ORDER BY Side";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $current_vessel['VesselID']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $side_display = $row['Side'] === '' ? '[EMPTY STRING]' : "'" . htmlspecialchars($row['Side']) . "'";
    echo "- $side_display (Count: " . $row['count'] . ")<br>";
}

// Check gears table too
echo "<h2>Actual Side values in gears table:</h2>";
$sql = "SELECT DISTINCT Side, COUNT(*) as count FROM gears WHERE VesselID = ? GROUP BY Side ORDER BY Side";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $current_vessel['VesselID']);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $side_display = $row['Side'] === '' ? '[EMPTY STRING]' : "'" . htmlspecialchars($row['Side']) . "'";
    echo "- $side_display (Count: " . $row['count'] . ")<br>";
}

// Test filtering with different side values
echo "<h2>Filter Test Results:</h2>";

$test_sides = ['Center Main', 'Center', 'Port', 'Starboard'];

foreach ($test_sides as $test_side) {
    echo "<h3>Testing filter with side: '" . htmlspecialchars($test_side) . "'</h3>";
    
    // Test mainengines
    $sql = "SELECT COUNT(*) as count FROM mainengines WHERE VesselID = ? AND Side = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $current_vessel['VesselID'], $test_side);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    echo "Mainengines: $count entries<br>";
    
    // Test generators
    $sql = "SELECT COUNT(*) as count FROM generators WHERE VesselID = ? AND Side = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $current_vessel['VesselID'], $test_side);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    echo "Generators: $count entries<br>";
    
    // Test gears
    $sql = "SELECT COUNT(*) as count FROM gears WHERE VesselID = ? AND Side = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $current_vessel['VesselID'], $test_side);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    echo "Gears: $count entries<br>";
    
    echo "<br>";
}

// Show some sample mainengines entries
echo "<h2>Sample mainengines entries:</h2>";
$sql = "SELECT EntryID, EntryDate, Side, MainHrs FROM mainengines WHERE VesselID = ? ORDER BY EntryDate DESC LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $current_vessel['VesselID']);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>EntryID</th><th>Date</th><th>Side (Raw)</th><th>Side (Display)</th><th>MainHrs</th></tr>";
while ($row = $result->fetch_assoc()) {
    $side_display = $row['Side'] === '' ? '[EMPTY]' : htmlspecialchars($row['Side']);
    $side_raw = "'" . $row['Side'] . "'";
    echo "<tr>";
    echo "<td>" . $row['EntryID'] . "</td>";
    echo "<td>" . $row['EntryDate'] . "</td>";
    echo "<td>" . $side_raw . "</td>";
    echo "<td>" . $side_display . "</td>";
    echo "<td>" . $row['MainHrs'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><a href='view_logs.php'>Go to view_logs.php</a><br>";
?>
