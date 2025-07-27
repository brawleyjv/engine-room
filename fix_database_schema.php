<?php
require_once 'config.php';

echo "<h1>Fix Database Schema for Three-Engine Support</h1>";

echo "<h2>Current mainengines Side ENUM:</h2>";
$result = $conn->query("SHOW COLUMNS FROM mainengines LIKE 'Side'");
$side_column = $result->fetch_assoc();
echo "Type: " . $side_column['Type'] . "<br>";

echo "<h2>Updating mainengines table...</h2>";

// Update mainengines table to include Center Main
$sql = "ALTER TABLE mainengines MODIFY COLUMN Side enum('Port','Starboard','Center Main') NOT NULL";
if ($conn->query($sql)) {
    echo "✅ Updated mainengines Side column successfully<br>";
} else {
    echo "❌ Error updating mainengines: " . $conn->error . "<br>";
}

echo "<h2>Updating generators table...</h2>";

// Check if generators table exists and update it too
$result = $conn->query("SHOW COLUMNS FROM generators LIKE 'Side'");
if ($result->num_rows > 0) {
    $side_column = $result->fetch_assoc();
    echo "Current generators Side type: " . $side_column['Type'] . "<br>";
    
    $sql = "ALTER TABLE generators MODIFY COLUMN Side enum('Port','Starboard','Center Main') NOT NULL";
    if ($conn->query($sql)) {
        echo "✅ Updated generators Side column successfully<br>";
    } else {
        echo "❌ Error updating generators: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ No Side column found in generators table<br>";
}

echo "<h2>Updating gears table...</h2>";

// Check if gears table exists and update it too
$result = $conn->query("SHOW COLUMNS FROM gears LIKE 'Side'");
if ($result->num_rows > 0) {
    $side_column = $result->fetch_assoc();
    echo "Current gears Side type: " . $side_column['Type'] . "<br>";
    
    $sql = "ALTER TABLE gears MODIFY COLUMN Side enum('Port','Starboard','Center Main') NOT NULL";
    if ($conn->query($sql)) {
        echo "✅ Updated gears Side column successfully<br>";
    } else {
        echo "❌ Error updating gears: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ No Side column found in gears table<br>";
}

echo "<h2>Verification - Updated Schema:</h2>";

// Verify the changes
$tables = ['mainengines', 'generators', 'gears'];
foreach ($tables as $table) {
    echo "<h3>$table table:</h3>";
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE 'Side'");
    if ($result->num_rows > 0) {
        $side_column = $result->fetch_assoc();
        echo "Side Type: <strong>" . $side_column['Type'] . "</strong><br>";
        
        if (strpos($side_column['Type'], 'Center Main') !== false) {
            echo "✅ 'Center Main' is now included<br>";
        } else {
            echo "❌ 'Center Main' is still missing<br>";
        }
    } else {
        echo "No Side column found<br>";
    }
    echo "<br>";
}

echo "<h2>Testing Center Main Insert:</h2>";

// Test inserting Center Main now that schema is updated
$test_sql = "INSERT INTO mainengines (VesselID, EntryDate, Side, RPM, MainHrs, OilPressure, OilTemp, FuelPress, WaterTemp, RecordedBy, Notes) VALUES (4, '2025-07-27', 'Center Main', 1800, 77777, 45, 210, 25, 180, 2, 'SCHEMA FIX TEST')";

if ($conn->query($test_sql)) {
    $insert_id = $conn->insert_id;
    echo "✅ Test insert successful! EntryID: $insert_id<br>";
    
    // Check what was saved
    $check_sql = "SELECT EntryID, Side, MainHrs, Notes FROM mainengines WHERE EntryID = $insert_id";
    $check_result = $conn->query($check_sql);
    $saved_data = $check_result->fetch_assoc();
    
    echo "Saved data:<br>";
    echo "EntryID: {$saved_data['EntryID']}<br>";
    echo "Side: '<strong>{$saved_data['Side']}</strong>' (length: " . strlen($saved_data['Side']) . ")<br>";
    echo "MainHrs: {$saved_data['MainHrs']}<br>";
    
    if ($saved_data['Side'] === 'Center Main') {
        echo "<br>🎉 <strong>SUCCESS! 'Center Main' is now saving correctly!</strong><br>";
    } else {
        echo "<br>❌ Still not working - Side: '{$saved_data['Side']}'<br>";
    }
} else {
    echo "❌ Test insert failed: " . $conn->error . "<br>";
}

echo "<br><h2>Next Steps:</h2>";
echo "1. ✅ Database schema has been updated<br>";
echo "2. 🔧 Run the comprehensive fix to update existing empty entries<br>";
echo "3. 📝 Test the real add_log.php form<br>";
echo "4. 📊 Test view_logs.php filtering<br>";

echo "<br><h2>Test Links:</h2>";
echo "<a href='comprehensive_fix_center_main.php' style='padding: 10px; background: #28a745; color: white; text-decoration: none; margin-right: 10px;'>🔧 Fix Existing Data</a>";
echo "<a href='add_log.php' style='padding: 10px; background: #007bff; color: white; text-decoration: none; margin-right: 10px;'>📝 Test Add Log</a>";
echo "<a href='view_logs.php?equipment=mainengines' style='padding: 10px; background: #ffc107; color: white; text-decoration: none;'>📊 Test View Logs</a>";
?>
