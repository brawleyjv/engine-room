<?php
require_once 'config.php';
require_once 'vessel_functions.php';
require_once 'auth_functions.php';

// Require login and vessel selection for data entry
require_vessel_selection();

// Get current user and active vessel
$current_user = get_logged_in_user();
$current_vessel = get_current_vessel($conn);

$active_vessel_id = $current_vessel['VesselID'];
$available_sides = get_vessel_sides($conn, $active_vessel_id);

echo "<h1>Debug: Real Add Log Process</h1>";
echo "<h2>Session Info:</h2>";
echo "Current Vessel ID: $active_vessel_id<br>";
echo "Available Sides: " . implode(', ', $available_sides) . "<br>";

if ($_POST) {
    echo "<h2>POST Data Received:</h2>";
    foreach ($_POST as $key => $value) {
        echo "$key = '$value'<br>";
    }
    
    $equipment_type = $_POST['equipment_type'] ?? '';
    $side = $_POST['side'] ?? '';
    $entry_date = $_POST['entry_date'] ?? '';
    $recorded_by = $current_user['user_id'];
    $notes = $_POST['notes'] ?? '';
    
    echo "<h2>Variables After Extraction:</h2>";
    echo "equipment_type = '$equipment_type'<br>";
    echo "side = '$side'<br>";
    echo "entry_date = '$entry_date'<br>";
    echo "recorded_by = '$recorded_by'<br>";
    echo "notes = '$notes'<br>";
    
    // Validate required fields
    if (empty($equipment_type) || empty($side) || empty($entry_date)) {
        echo "<h3>❌ Validation Failed:</h3>";
        echo "equipment_type empty: " . (empty($equipment_type) ? 'YES' : 'NO') . "<br>";
        echo "side empty: " . (empty($side) ? 'YES' : 'NO') . "<br>";
        echo "entry_date empty: " . (empty($entry_date) ? 'YES' : 'NO') . "<br>";
    } else {
        echo "<h3>✅ Validation Passed</h3>";
        
        if ($equipment_type === 'mainengines') {
            $rpm = $_POST['me_rpm'] ?? '';
            $main_hrs = $_POST['me_main_hrs'] ?? '';
            $oil_pressure = $_POST['me_oil_pressure'] ?? '';
            $oil_temp = $_POST['me_oil_temp'] ?? '';
            $fuel_press = $_POST['me_fuel_press'] ?? '';
            $water_temp = $_POST['me_water_temp'] ?? '';
            
            echo "<h3>Main Engine Data:</h3>";
            echo "RPM: $rpm<br>";
            echo "Main Hrs: $main_hrs<br>";
            echo "Oil Pressure: $oil_pressure<br>";
            echo "Oil Temp: $oil_temp<br>";
            echo "Fuel Press: $fuel_press<br>";
            echo "Water Temp: $water_temp<br>";
            
            // Prepare the SQL - exactly like the real form
            $sql = "INSERT INTO mainengines (VesselID, EntryDate, Side, RPM, MainHrs, OilPressure, OilTemp, FuelPress, WaterTemp, RecordedBy, Notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            echo "<h3>SQL Statement:</h3>";
            echo htmlspecialchars($sql) . "<br>";
            
            echo "<h3>Parameters to bind:</h3>";
            echo "1. VesselID (i): $active_vessel_id<br>";
            echo "2. EntryDate (s): '$entry_date'<br>";
            echo "3. Side (s): '$side'<br>";
            echo "4. RPM (i): $rpm<br>";
            echo "5. MainHrs (i): $main_hrs<br>";
            echo "6. OilPressure (i): $oil_pressure<br>";
            echo "7. OilTemp (i): $oil_temp<br>";
            echo "8. FuelPress (i): $fuel_press<br>";
            echo "9. WaterTemp (i): $water_temp<br>";
            echo "10. RecordedBy (i): $recorded_by<br>";
            echo "11. Notes (s): '$notes'<br>";
            
            // Actually execute the insert
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('issiiiiiiss', $active_vessel_id, $entry_date, $side, $rpm, $main_hrs, $oil_pressure, $oil_temp, $fuel_press, $water_temp, $recorded_by, $notes);
            
            echo "<h3>Executing Insert...</h3>";
            if ($stmt->execute()) {
                $insert_id = $conn->insert_id;
                echo "✅ <strong>SUCCESS!</strong> Insert ID: $insert_id<br>";
                
                // Verify the data was actually saved
                echo "<h3>Verifying Insert:</h3>";
                $verify_sql = "SELECT EntryID, Side, MainHrs FROM mainengines WHERE EntryID = ?";
                $verify_stmt = $conn->prepare($verify_sql);
                $verify_stmt->bind_param('i', $insert_id);
                $verify_stmt->execute();
                $verify_result = $verify_stmt->get_result();
                $saved_data = $verify_result->fetch_assoc();
                
                if ($saved_data) {
                    echo "EntryID: {$saved_data['EntryID']}<br>";
                    echo "Side in DB: '" . $saved_data['Side'] . "'<br>";
                    echo "MainHrs in DB: {$saved_data['MainHrs']}<br>";
                    
                    if ($saved_data['Side'] === $side) {
                        echo "✅ Side value matches what we sent!<br>";
                    } else {
                        echo "❌ Side value DOES NOT match! Sent: '$side', Saved: '{$saved_data['Side']}'<br>";
                    }
                } else {
                    echo "❌ Could not verify - entry not found<br>";
                }
                
            } else {
                echo "❌ <strong>INSERT FAILED!</strong><br>";
                echo "Error: " . $stmt->error . "<br>";
                echo "MySQL Error: " . $conn->error . "<br>";
            }
        }
    }
    
} else {
    // Show the form
    echo "<form method='POST'>";
    echo "<h3>Test Real Insert:</h3>";
    
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
    echo "<input type='number' name='me_main_hrs' value='" . rand(10000, 99999) . "' required><br><br>";
    
    echo "<label>Oil Pressure:</label><br>";
    echo "<input type='number' name='me_oil_pressure' value='45'><br><br>";
    
    echo "<label>Oil Temp:</label><br>";
    echo "<input type='number' name='me_oil_temp' value='210'><br><br>";
    
    echo "<label>Fuel Press:</label><br>";
    echo "<input type='number' name='me_fuel_press' value='25'><br><br>";
    
    echo "<label>Water Temp:</label><br>";
    echo "<input type='number' name='me_water_temp' value='180'><br><br>";
    
    echo "<button type='submit'>DEBUG INSERT</button>";
    echo "</form>";
}

echo "<br><br><a href='view_logs.php'>Check View Logs</a><br>";
echo "<a href='debug_side_mismatch.php'>Run Side Debug</a><br>";
?>
