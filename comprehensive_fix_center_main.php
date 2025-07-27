<?php
session_start();
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

require_login();

echo "<h1>Comprehensive Fix for Center Main Data</h1>";

// Get current vessel
$current_vessel = get_current_vessel($conn);
if (!$current_vessel) {
    echo "❌ No current vessel found. Setting to Test Vessel...<br>";
    // Set to Test Vessel
    $sql = "SELECT * FROM vessels WHERE VesselName = 'Test Vessel' OR VesselID = 4";
    $result = $conn->query($sql);
    $current_vessel = $result->fetch_assoc();
    
    if ($current_vessel) {
        $_SESSION['current_vessel_id'] = $current_vessel['VesselID'];
        $_SESSION['current_vessel_name'] = $current_vessel['VesselName'];
        echo "✅ Set current vessel to: " . $current_vessel['VesselName'] . "<br>";
    }
}

if (!$current_vessel) {
    echo "❌ Still no vessel found. Cannot proceed.<br>";
    exit;
}

echo "<h2>Working with Vessel:</h2>";
echo "VesselID: " . $current_vessel['VesselID'] . "<br>";
echo "VesselName: " . $current_vessel['VesselName'] . "<br>";
echo "EngineConfig: " . $current_vessel['EngineConfig'] . "<br>";

// Show current mainengines data
echo "<h2>Current mainengines data (ALL entries):</h2>";
$sql = "SELECT EntryID, EntryDate, Side, MainHrs, RPM FROM mainengines WHERE VesselID = ? ORDER BY EntryDate DESC, EntryID DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $current_vessel['VesselID']);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>EntryID</th><th>Date</th><th>Side (Raw)</th><th>MainHrs</th><th>RPM</th><th>Action Needed</th></tr>";

$entries_to_fix = [];
while ($row = $result->fetch_assoc()) {
    $side_display = $row['Side'] === '' ? '[EMPTY STRING]' : "'" . htmlspecialchars($row['Side']) . "'";
    $needs_fix = ($row['Side'] === '' || $row['Side'] === null);
    
    if ($needs_fix) {
        $entries_to_fix[] = $row['EntryID'];
    }
    
    echo "<tr>";
    echo "<td>" . $row['EntryID'] . "</td>";
    echo "<td>" . $row['EntryDate'] . "</td>";
    echo "<td style='background: " . ($needs_fix ? '#ffcccc' : '#ccffcc') . ";'>" . $side_display . "</td>";
    echo "<td>" . $row['MainHrs'] . "</td>";
    echo "<td>" . $row['RPM'] . "</td>";
    echo "<td>" . ($needs_fix ? "❌ NEEDS FIX" : "✅ OK") . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Entries that need fixing:</h2>";
if (empty($entries_to_fix)) {
    echo "✅ No entries need fixing.<br>";
} else {
    echo "Found " . count($entries_to_fix) . " entries with empty sides: " . implode(', ', $entries_to_fix) . "<br>";
    
    echo "<h3>Applying fix...</h3>";
    
    // Fix all empty sides for this vessel
    $sql = "UPDATE mainengines SET Side = 'Center Main' WHERE VesselID = ? AND (Side = '' OR Side IS NULL)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $current_vessel['VesselID']);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        echo "✅ Updated $affected_rows entries to have Side = 'Center Main'<br>";
    } else {
        echo "❌ Error updating entries: " . $conn->error . "<br>";
    }
}

// Show data after fix
echo "<h2>Data after fix:</h2>";
$sql = "SELECT EntryID, EntryDate, Side, MainHrs, RPM FROM mainengines WHERE VesselID = ? ORDER BY EntryDate DESC, EntryID DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $current_vessel['VesselID']);
$stmt->execute();
$result = $stmt->get_result();

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>EntryID</th><th>Date</th><th>Side</th><th>MainHrs</th><th>RPM</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['EntryID'] . "</td>";
    echo "<td>" . $row['EntryDate'] . "</td>";
    echo "<td style='background: #ccffcc;'><strong>" . htmlspecialchars($row['Side']) . "</strong></td>";
    echo "<td>" . $row['MainHrs'] . "</td>";
    echo "<td>" . $row['RPM'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test the filter
echo "<h2>Testing Center Main filter:</h2>";
$sql = "SELECT COUNT(*) as count FROM mainengines WHERE VesselID = ? AND Side = 'Center Main'";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $current_vessel['VesselID']);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'];

echo "Records with Side = 'Center Main': <strong>$count</strong><br>";

if ($count > 0) {
    echo "✅ <strong>Filter should now work!</strong><br>";
} else {
    echo "❌ Still no records found with 'Center Main'<br>";
}

echo "<br><h2>Test Links:</h2>";
echo "<a href='view_logs.php?equipment=mainengines&side=Center+Main' style='padding: 10px; background: #007bff; color: white; text-decoration: none; margin-right: 10px;'>🔍 Test Center Main Filter</a>";
echo "<a href='view_logs.php?equipment=mainengines' style='padding: 10px; background: #28a745; color: white; text-decoration: none; margin-right: 10px;'>📊 View All Main Engines</a>";
echo "<a href='debug_side_mismatch.php' style='padding: 10px; background: #ffc107; color: white; text-decoration: none;'>🔧 Run Debug Again</a>";

echo "<br><br><h2>Next Steps:</h2>";
echo "1. Click the 'Test Center Main Filter' link above<br>";
echo "2. You should now see your center engine entries<br>";
echo "3. You should also see a graph button for Center Main<br>";
echo "4. If you still get empty sides on NEW entries, we need to fix the add_log.php form<br>";
?>
