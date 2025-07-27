<?php
require_once 'config.php';

echo "<h1>Database Schema Investigation</h1>";

// Check mainengines table structure
echo "<h2>mainengines Table Structure:</h2>";
$result = $conn->query("DESCRIBE mainengines");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td><strong>{$row['Type']}</strong></td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check for any constraints
echo "<h2>Checking for Constraints/Triggers:</h2>";
try {
    $result = $conn->query("SHOW CREATE TABLE mainengines");
    $create_table = $result->fetch_assoc();
    echo "<pre>" . htmlspecialchars($create_table['Create Table']) . "</pre>";
} catch (Exception $e) {
    echo "Error getting table creation info: " . $e->getMessage() . "<br>";
}

// Check if there are any triggers on the table
echo "<h2>Checking for Triggers:</h2>";
try {
    $result = $conn->query("SHOW TRIGGERS LIKE 'mainengines'");
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Trigger</th><th>Event</th><th>Table</th><th>Statement</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Trigger']}</td>";
            echo "<td>{$row['Event']}</td>";
            echo "<td>{$row['Table']}</td>";
            echo "<td><pre>" . htmlspecialchars($row['Statement']) . "</pre></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No triggers found on mainengines table.<br>";
    }
} catch (Exception $e) {
    echo "Error checking triggers: " . $e->getMessage() . "<br>";
}

// Test direct insert to see what happens
echo "<h2>Testing Direct Insert:</h2>";
$test_sql = "INSERT INTO mainengines (VesselID, EntryDate, Side, RPM, MainHrs, OilPressure, OilTemp, FuelPress, WaterTemp, RecordedBy, Notes) VALUES (4, '2025-07-27', 'Center Main', 1800, 99999, 45, 210, 25, 180, 2, 'TEST DIRECT INSERT')";

echo "SQL: " . htmlspecialchars($test_sql) . "<br><br>";

if ($conn->query($test_sql)) {
    $insert_id = $conn->insert_id;
    echo "✅ Direct insert successful! EntryID: $insert_id<br>";
    
    // Check what was actually saved
    $check_sql = "SELECT EntryID, Side, MainHrs, Notes FROM mainengines WHERE EntryID = $insert_id";
    $check_result = $conn->query($check_sql);
    $saved_data = $check_result->fetch_assoc();
    
    echo "Saved data:<br>";
    echo "EntryID: {$saved_data['EntryID']}<br>";
    echo "Side: '" . $saved_data['Side'] . "' (length: " . strlen($saved_data['Side']) . ")<br>";
    echo "MainHrs: {$saved_data['MainHrs']}<br>";
    echo "Notes: '{$saved_data['Notes']}'<br>";
    
    if ($saved_data['Side'] === 'Center Main') {
        echo "✅ Direct insert preserves 'Center Main' correctly!<br>";
        echo "This means the issue is in the prepared statement binding.<br>";
    } else {
        echo "❌ Even direct insert fails to save 'Center Main'<br>";
        echo "This suggests a database-level constraint or trigger issue.<br>";
    }
} else {
    echo "❌ Direct insert failed: " . $conn->error . "<br>";
}

// Test with prepared statement manually
echo "<h2>Testing Prepared Statement Manually:</h2>";
$manual_sql = "INSERT INTO mainengines (VesselID, EntryDate, Side, RPM, MainHrs, OilPressure, OilTemp, FuelPress, WaterTemp, RecordedBy, Notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$manual_stmt = $conn->prepare($manual_sql);

$vessel_id = 4;
$entry_date = '2025-07-27';
$side = 'Center Main';
$rpm = 1800;
$main_hrs = 88888;
$oil_pressure = 45;
$oil_temp = 210;
$fuel_press = 25;
$water_temp = 180;
$recorded_by = 2;
$notes = 'MANUAL PREPARED TEST';

echo "Binding parameters:<br>";
echo "VesselID: $vessel_id<br>";
echo "EntryDate: '$entry_date'<br>";
echo "Side: '$side' (length: " . strlen($side) . ")<br>";
echo "RPM: $rpm<br>";
echo "MainHrs: $main_hrs<br>";

$manual_stmt->bind_param('issiiiiiiss', $vessel_id, $entry_date, $side, $rpm, $main_hrs, $oil_pressure, $oil_temp, $fuel_press, $water_temp, $recorded_by, $notes);

if ($manual_stmt->execute()) {
    $insert_id = $conn->insert_id;
    echo "✅ Manual prepared statement successful! EntryID: $insert_id<br>";
    
    // Check what was actually saved
    $check_sql = "SELECT EntryID, Side, MainHrs, Notes FROM mainengines WHERE EntryID = $insert_id";
    $check_result = $conn->query($check_sql);
    $saved_data = $check_result->fetch_assoc();
    
    echo "Saved data:<br>";
    echo "EntryID: {$saved_data['EntryID']}<br>";
    echo "Side: '" . $saved_data['Side'] . "' (length: " . strlen($saved_data['Side']) . ")<br>";
    echo "MainHrs: {$saved_data['MainHrs']}<br>";
    echo "Notes: '{$saved_data['Notes']}'<br>";
    
} else {
    echo "❌ Manual prepared statement failed: " . $manual_stmt->error . "<br>";
}
?>
