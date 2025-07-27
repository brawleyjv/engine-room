<?php
session_start();
require_once 'config.php';
require_once 'vessel_functions.php';

// Force set Test Vessel as active in web session
echo "<h2>Web Session Fix for Test Vessel</h2>";

echo "<h3>BEFORE:</h3>";
echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'NOT SET') . "<br>";
echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'NOT SET') . "<br>";

// Set Test Vessel (ID: 4) as active
set_active_vessel(4, 'Test Vessel');

echo "<h3>AFTER:</h3>";
echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'NOT SET') . "<br>";
echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'NOT SET') . "<br>";

// Test get_current_vessel
$current_vessel = get_current_vessel($conn);
if ($current_vessel) {
    echo "<h3>Vessel Info:</h3>";
    echo "VesselID: " . $current_vessel['VesselID'] . "<br>";
    echo "VesselName: " . $current_vessel['VesselName'] . "<br>";
    echo "EngineConfig: " . ($current_vessel['EngineConfig'] ?? 'NOT SET') . "<br>";
    
    $sides = get_vessel_sides($conn, $current_vessel['VesselID']);
    echo "Available sides: " . implode(', ', $sides) . "<br>";
    
    if (count($sides) == 3) {
        echo "<p style='color: green;'><strong>✓ SUCCESS: Three-engine vessel properly configured!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>✗ ERROR: Only " . count($sides) . " sides found</strong></p>";
    }
} else {
    echo "<p style='color: red;'><strong>✗ ERROR: get_current_vessel() still returns NULL</strong></p>";
}

echo "<br><br>";
echo "<strong>Now test these links:</strong><br>";
echo "<a href='view_logs.php?equipment=mainengines' style='display: inline-block; padding: 10px; background: #007cba; color: white; text-decoration: none; margin: 5px;'>View Logs - Mainengines</a><br>";
echo "<a href='add_log.php' style='display: inline-block; padding: 10px; background: #28a745; color: white; text-decoration: none; margin: 5px;'>Add Log Entry</a><br>";
echo "<a href='manage_vessels.php' style='display: inline-block; padding: 10px; background: #6c757d; color: white; text-decoration: none; margin: 5px;'>Manage Vessels</a><br>";
?>
