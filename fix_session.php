<?php
session_start();
require_once 'config.php';
require_once 'vessel_functions.php';

echo "<h2>Session Sync Fix</h2>";

echo "<h3>BEFORE:</h3>";
echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'NOT SET') . "<br>";
echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'NOT SET') . "<br>";

// If active_vessel_id is set but current_vessel_id doesn't match, sync them
if (isset($_SESSION['active_vessel_id'])) {
    $vessel_id = $_SESSION['active_vessel_id'];
    
    // Get vessel name
    $sql = "SELECT VesselName FROM vessels WHERE VesselID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $vessel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $vessel = $result->fetch_assoc();
    
    if ($vessel) {
        set_active_vessel($vessel_id, $vessel['VesselName']);
        echo "<h3>FIXED - Both session variables now synced to:</h3>";
        echo "Vessel ID: " . $vessel_id . "<br>";
        echo "Vessel Name: " . $vessel['VesselName'] . "<br>";
    }
}

echo "<h3>AFTER:</h3>";
echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'NOT SET') . "<br>";
echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'NOT SET') . "<br>";

echo "<br><br>";
echo "<a href='view_logs.php?equipment=mainengines'>Test View Logs Now</a> | ";
echo "<a href='debug_view_logs.php'>Debug View Logs</a> | ";
echo "<a href='manage_vessels.php'>Manage Vessels</a>";
?>
