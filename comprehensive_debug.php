<?php
session_start();
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

echo "<h2>Comprehensive Debug - View Logs Issue</h2>";

// Step 1: Force set the Test Vessel session
echo "<h3>Step 1: Setting Test Vessel Session</h3>";
set_active_vessel(4, 'Test Vessel');
echo "✅ Session set for Test Vessel (ID: 4)<br>";
echo "current_vessel_id: " . $_SESSION['current_vessel_id'] . "<br>";
echo "active_vessel_id: " . $_SESSION['active_vessel_id'] . "<br>";

// Step 2: Test get_current_vessel function
echo "<h3>Step 2: Testing get_current_vessel()</h3>";
$current_vessel = get_current_vessel($conn);
if ($current_vessel) {
    echo "✅ get_current_vessel() works:<br>";
    foreach ($current_vessel as $key => $value) {
        echo "&nbsp;&nbsp;{$key}: " . ($value ?? 'NULL') . "<br>";
    }
} else {
    echo "❌ get_current_vessel() returned NULL<br>";
    exit;
}

// Step 3: Test get_vessel_sides function directly
echo "<h3>Step 3: Testing get_vessel_sides()</h3>";
$sides = get_vessel_sides($conn, $current_vessel['VesselID']);
echo "Sides returned: " . implode(', ', $sides) . "<br>";
echo "Number of sides: " . count($sides) . "<br>";

if (count($sides) != 3) {
    echo "❌ ERROR: Expected 3 sides, got " . count($sides) . "<br>";
    
    // Debug the get_vessel_sides function step by step
    echo "<h4>Debugging get_vessel_sides function:</h4>";
    $sql = "SELECT EngineConfig FROM vessels WHERE VesselID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $current_vessel['VesselID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $vessel = $result->fetch_assoc();
    
    echo "Raw EngineConfig from database: '" . ($vessel['EngineConfig'] ?? 'NULL') . "'<br>";
    echo "Type of EngineConfig: " . gettype($vessel['EngineConfig']) . "<br>";
    echo "Length of EngineConfig: " . strlen($vessel['EngineConfig'] ?? '') . "<br>";
    
    if (($vessel['EngineConfig'] ?? 'standard') === 'three_engine') {
        echo "✅ Condition matches 'three_engine'<br>";
    } else {
        echo "❌ Condition does NOT match 'three_engine'<br>";
        echo "Actual value: '" . ($vessel['EngineConfig'] ?? 'NULL') . "'<br>";
    }
} else {
    echo "✅ SUCCESS: Got 3 sides as expected<br>";
}

// Step 4: Replicate the exact view_logs.php logic
echo "<h3>Step 4: Replicating view_logs.php Filter Logic</h3>";
$equipment_type = 'mainengines';
echo "Equipment type: {$equipment_type}<br>";
echo "Available sides for dropdown: " . implode(', ', $sides) . "<br>";

// Step 5: Show the HTML dropdown as it would appear
echo "<h3>Step 5: HTML Dropdown Preview</h3>";
echo "<select name='side'>";
echo "<option value=''>All Sides</option>";
foreach ($sides as $side_name) {
    echo "<option value='" . htmlspecialchars($side_name) . "'>" . htmlspecialchars($side_name) . "</option>";
}
echo "</select>";

echo "<h3>Step 6: Raw HTML Code</h3>";
echo "<pre>";
echo htmlspecialchars("<select name='side'>");
echo htmlspecialchars("<option value=''>All Sides</option>");
foreach ($sides as $side_name) {
    echo htmlspecialchars("<option value='" . htmlspecialchars($side_name) . "'>" . htmlspecialchars($side_name) . "</option>");
}
echo htmlspecialchars("</select>");
echo "</pre>";

echo "<br><br>";
echo "<a href='view_logs.php?equipment=mainengines' style='padding: 10px; background: #007cba; color: white; text-decoration: none;'>🔗 Go to Actual View Logs</a>";
?>
