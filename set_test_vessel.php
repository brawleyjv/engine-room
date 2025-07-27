<?php
session_start();
require_once 'config.php';
require_once 'vessel_functions.php';

echo "Setting Test Vessel as active...\n";

// Set Test Vessel (ID: 4) as active
set_active_vessel(4, 'Test Vessel');

echo "Session variables set:\n";
echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'NOT SET') . "\n";
echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'NOT SET') . "\n";

// Test get_current_vessel
$current_vessel = get_current_vessel($conn);
if ($current_vessel) {
    echo "\nget_current_vessel() now returns:\n";
    echo "VesselID: " . $current_vessel['VesselID'] . "\n";
    echo "VesselName: " . $current_vessel['VesselName'] . "\n";
    echo "EngineConfig: " . ($current_vessel['EngineConfig'] ?? 'NOT SET') . "\n";
    
    $sides = get_vessel_sides($conn, $current_vessel['VesselID']);
    echo "Available sides: " . implode(', ', $sides) . "\n";
} else {
    echo "get_current_vessel() still returns NULL\n";
}

echo "\nNow go to view_logs.php?equipment=mainengines to test!\n";
?>
