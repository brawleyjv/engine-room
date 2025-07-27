<?php
session_start();
require_once 'config.php';
require_once 'vessel_functions.php';

echo "<h2>Quick Session Check</h2>";

echo "<h3>Current Session State:</h3>";
echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'NOT SET') . "<br>";
echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'NOT SET') . "<br>";

if (!isset($_SESSION['current_vessel_id']) || empty($_SESSION['current_vessel_id'])) {
    echo "<p style='color: red;'>⚠️ current_vessel_id is not set - this will cause redirect to select_vessel.php</p>";
    
    echo "<h3>Fixing session now...</h3>";
    set_active_vessel(4, 'Test Vessel');
    
    echo "✅ Session fixed:<br>";
    echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'NOT SET') . "<br>";
    echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'NOT SET') . "<br>";
} else {
    echo "<p style='color: green;'>✅ Session is properly set</p>";
}

// Test get_current_vessel
$current_vessel = get_current_vessel($conn);
if ($current_vessel) {
    echo "<h3>Vessel Details:</h3>";
    echo "VesselID: " . $current_vessel['VesselID'] . "<br>";
    echo "VesselName: " . $current_vessel['VesselName'] . "<br>";
    echo "EngineConfig: " . ($current_vessel['EngineConfig'] ?? 'NOT SET') . "<br>";
    
    $sides = get_vessel_sides($conn, $current_vessel['VesselID']);
    echo "Available sides: <strong>" . implode(', ', $sides) . "</strong><br>";
} else {
    echo "<p style='color: red;'>❌ get_current_vessel() returned NULL</p>";
}

echo "<br><hr><br>";
echo "<strong>NOW TRY:</strong><br>";
echo "<a href='view_logs.php?equipment=mainengines' style='padding: 10px; background: #007cba; color: white; text-decoration: none; display: inline-block; margin: 5px;'>📊 View Logs - Mainengines</a><br>";
?>
