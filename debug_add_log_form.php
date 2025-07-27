<?php
session_start();
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

require_login();

echo "<h1>Debug: Add Log Form Submission</h1>";

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

// Get available sides
$available_sides = get_vessel_sides($conn, $current_vessel['VesselID']);
echo "<h2>Available Sides from get_vessel_sides():</h2>";
foreach ($available_sides as $side) {
    echo "- '" . htmlspecialchars($side) . "'<br>";
}

// Test form
echo "<h2>Test Form Submission:</h2>";

if ($_POST) {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    foreach ($_POST as $key => $value) {
        echo htmlspecialchars($key) . " = '" . htmlspecialchars($value) . "'<br>";
    }
    echo "</pre>";
    
    $equipment_type = $_POST['equipment_type'] ?? '';
    $side = $_POST['side'] ?? '';
    $entry_date = $_POST['entry_date'] ?? '';
    
    echo "<h3>Key Values:</h3>";
    echo "Equipment Type: '" . htmlspecialchars($equipment_type) . "'<br>";
    echo "Side: '" . htmlspecialchars($side) . "'<br>";
    echo "Entry Date: '" . htmlspecialchars($entry_date) . "'<br>";
    
    if (empty($side)) {
        echo "<br>❌ <strong>ISSUE FOUND: Side is empty!</strong><br>";
        echo "This is why your entries are getting empty side values.<br>";
    } else {
        echo "<br>✅ Side value looks good.<br>";
    }
    
    // Simulate the database insert (but don't actually insert)
    if ($equipment_type === 'mainengines' && !empty($side)) {
        echo "<h3>Would insert to mainengines table:</h3>";
        echo "VesselID: " . $current_vessel['VesselID'] . "<br>";
        echo "Side: '" . htmlspecialchars($side) . "'<br>";
        echo "EntryDate: '" . htmlspecialchars($entry_date) . "'<br>";
    }
    
} else {
    echo "<form method='POST'>";
    echo "<h3>Test Main Engine Entry:</h3>";
    
    echo "<label>Equipment Type:</label><br>";
    echo "<select name='equipment_type' required>";
    echo "<option value=''>Select Equipment</option>";
    echo "<option value='mainengines'>Main Engines</option>";
    echo "</select><br><br>";
    
    echo "<label>Side:</label><br>";
    echo "<select name='side' required>";
    echo "<option value=''>Select Side</option>";
    foreach ($available_sides as $side_option) {
        echo "<option value='" . htmlspecialchars($side_option) . "'>" . htmlspecialchars($side_option) . "</option>";
    }
    echo "</select><br><br>";
    
    echo "<label>Entry Date:</label><br>";
    echo "<input type='date' name='entry_date' value='" . date('Y-m-d') . "' required><br><br>";
    
    echo "<label>RPM:</label><br>";
    echo "<input type='number' name='me_rpm' value='1800'><br><br>";
    
    echo "<label>Main Hours:</label><br>";
    echo "<input type='number' name='me_main_hrs' value='12345' required><br><br>";
    
    echo "<button type='submit'>Test Form Submission</button>";
    echo "</form>";
}

echo "<br><br><a href='add_log.php'>Go to Real Add Log Form</a><br>";
echo "<a href='view_logs.php'>Go to View Logs</a><br>";
?>
