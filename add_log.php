<?php
require_once 'config.php';
require_once 'vessel_functions.php';
session_start();

// Get active vessel
$active_vessel_id = get_active_vessel_id();
$active_vessel = get_active_vessel_info($conn);

$message = '';
$message_type = '';

if ($_POST) {
    $equipment_type = $_POST['equipment_type'] ?? '';
    $side = $_POST['side'] ?? '';
    $entry_date = $_POST['entry_date'] ?? '';
    $recorded_by = $_POST['recorded_by'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Validate required fields
    if (empty($equipment_type) || empty($side) || empty($entry_date) || empty($recorded_by)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } else {
        // Check for duplicate hours first
        $duplicate_check_result = checkDuplicateHours($conn, $equipment_type, $side, $_POST, $active_vessel_id);
        
        if ($duplicate_check_result['is_duplicate']) {
            $message = $duplicate_check_result['message'];
            $message_type = 'error';
        } else {
            try {
                if ($equipment_type === 'mainengines') {
                $rpm = $_POST['me_rpm'];
                $main_hrs = $_POST['me_main_hrs'];
                $oil_pressure = $_POST['me_oil_pressure'];
                $oil_temp = $_POST['me_oil_temp'];
                $fuel_press = $_POST['me_fuel_press'];
                $water_temp = $_POST['me_water_temp'];
                
                $sql = "INSERT INTO mainengines (VesselID, EntryDate, Side, RPM, MainHrs, OilPressure, OilTemp, FuelPress, WaterTemp, RecordedBy, Notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('issiiiiiiss', $active_vessel_id, $entry_date, $side, $rpm, $main_hrs, $oil_pressure, $oil_temp, $fuel_press, $water_temp, $recorded_by, $notes);
                
            } elseif ($equipment_type === 'generators') {
                $gen_hrs = $_POST['gen_hrs'];
                $oil_press = $_POST['gen_oil_press'];
                $fuel_press = $_POST['gen_fuel_press'];
                $water_temp = $_POST['gen_water_temp'];
                
                $sql = "INSERT INTO generators (VesselID, EntryDate, Side, GenHrs, OilPress, FuelPress, WaterTemp, RecordedBy, Notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('issiiiiss', $active_vessel_id, $entry_date, $side, $gen_hrs, $oil_press, $fuel_press, $water_temp, $recorded_by, $notes);
                
            } elseif ($equipment_type === 'gears') {
                $gear_hrs = $_POST['gear_hrs'];
                $oil_press = $_POST['gear_oil_press'];
                $temp = $_POST['gear_temp'];
                
                $sql = "INSERT INTO gears (VesselID, EntryDate, Side, GearHrs, OilPress, Temp, RecordedBy, Notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('issiiiss', $active_vessel_id, $entry_date, $side, $gear_hrs, $oil_press, $temp, $recorded_by, $notes);
            }
            
            if ($stmt->execute()) {
                $message = 'Log entry added successfully!';
                $message_type = 'success';
                // Clear form data only on success
                $_POST = [];
            } else {
                $message = 'Error adding log entry: ' . $stmt->error . ' | SQL Error: ' . $conn->error;
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
        }
    }
}

// Function to check for duplicate hours
function checkDuplicateHours($conn, $equipment_type, $side, $post_data, $vessel_id) {
    $result = ['is_duplicate' => false, 'message' => ''];
    
    if ($equipment_type === 'mainengines') {
        $hours_value = $post_data['me_main_hrs'];
        $hours_column = 'MainHrs';
        $table = 'mainengines';
        $equipment_name = 'Main Engine';
    } elseif ($equipment_type === 'generators') {
        $hours_value = $post_data['gen_hrs'];
        $hours_column = 'GenHrs';
        $table = 'generators';
        $equipment_name = 'Generator';
    } elseif ($equipment_type === 'gears') {
        $hours_value = $post_data['gear_hrs'];
        $hours_column = 'GearHrs';
        $table = 'gears';
        $equipment_name = 'Gear';
    } else {
        return $result; // No check needed for unknown equipment type
    }
    
    // Skip check if hours value is empty
    if (empty($hours_value)) {
        return $result;
    }
    
    // Check if the hours already exist for this side and vessel
    $sql = "SELECT EntryDate, RecordedBy, $hours_column FROM $table WHERE Side = ? AND VesselID = ? AND $hours_column = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sii', $side, $vessel_id, $hours_value);
    $stmt->execute();
    $existing_result = $stmt->get_result();
    
    if ($existing_result->num_rows > 0) {
        $existing_row = $existing_result->fetch_assoc();
        $existing_date = $existing_row['EntryDate'];
        $existing_recorder = $existing_row['RecordedBy'];
        
        // Get the EntryID for the edit link
        $entry_id_sql = "SELECT EntryID FROM $table WHERE Side = ? AND $hours_column = ?";
        $entry_id_stmt = $conn->prepare($entry_id_sql);
        $entry_id_stmt->bind_param('si', $side, $hours_value);
        $entry_id_stmt->execute();
        $entry_id_result = $entry_id_stmt->get_result();
        $entry_id_row = $entry_id_result->fetch_assoc();
        $entry_id = $entry_id_row['EntryID'];
        
        $result['is_duplicate'] = true;
        $result['message'] = "⚠️ <strong>Equipment Hours Already Exist!</strong><br><br>" .
                           "$equipment_name ($side side) already has an entry with <strong>$hours_value hours</strong> recorded on $existing_date by $existing_recorder.<br><br>" .
                           "<strong>What would you like to do?</strong><br>" .
                           "• Change the hours in your current entry to a different value<br>" .
                           "• Or <a href='edit_log.php?equipment=$equipment_type&id=$entry_id' style='color: #007bff; text-decoration: underline; font-weight: bold;'>click here to edit the existing record</a> if you want to update the other values<br>" .
                           "• Or <a href='view_logs.php?equipment=$equipment_type&side=$side&hours=$hours_value' style='color: #007bff; text-decoration: underline;'>view all logs for this equipment</a>";
    }
    
    return $result;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Log Entry - Vessel Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .equipment-fields {
            display: none;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .equipment-fields.active {
            display: grid;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>➕ Add Equipment Log Entry</h1>
            <p><a href="index.php" class="btn btn-info">← Back to Home</a></p>
        </header>
        
        <?= render_vessel_selector($conn, 'add_log') ?>
        
        <div class="form-container">
            <?php if (!empty($message)): ?>
                <div class="message <?= $message_type ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="add_log.php">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    
                    <div class="form-group">
                        <label for="equipment_type">Equipment Type: *</label>
                        <select name="equipment_type" id="equipment_type" required onchange="showEquipmentFields()">
                            <option value="">Select Equipment</option>
                            <option value="mainengines" <?= ($_POST['equipment_type'] ?? '') === 'mainengines' ? 'selected' : '' ?>>Main Engines</option>
                            <option value="generators" <?= ($_POST['equipment_type'] ?? '') === 'generators' ? 'selected' : '' ?>>Generators</option>
                            <option value="gears" <?= ($_POST['equipment_type'] ?? '') === 'gears' ? 'selected' : '' ?>>Gears</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="side">Side: *</label>
                        <select name="side" id="side" required>
                            <option value="">Select Side</option>
                            <option value="Port" <?= ($_POST['side'] ?? '') === 'Port' ? 'selected' : '' ?>>Port</option>
                            <option value="Starboard" <?= ($_POST['side'] ?? '') === 'Starboard' ? 'selected' : '' ?>>Starboard</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="entry_date">Entry Date: *</label>
                        <input type="date" name="entry_date" id="entry_date" required 
                               value="<?= htmlspecialchars($_POST['entry_date'] ?? date('Y-m-d')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="recorded_by">Recorded By: *</label>
                        <input type="text" name="recorded_by" id="recorded_by" required
                               value="<?= htmlspecialchars($_POST['recorded_by'] ?? '') ?>" placeholder="Engineer name">
                    </div>
                </div>
                
                <!-- Main Engines Fields -->
                <div id="mainengines-fields" class="equipment-fields">
                    <div class="form-group">
                        <label for="me_main_hrs">Main Hours: *</label>
                        <input type="number" name="me_main_hrs" id="me_main_hrs" step="1"
                               value="<?= htmlspecialchars($_POST['me_main_hrs'] ?? '') ?>" placeholder="Engine Hours">
                    </div>
                    
                    <div class="form-group">
                        <label for="me_rpm">RPM: *</label>
                        <input type="number" name="me_rpm" id="me_rpm" step="1"
                               value="<?= htmlspecialchars($_POST['me_rpm'] ?? '') ?>" placeholder="Engine RPM">
                    </div>
                    
                    <div class="form-group">
                        <label for="me_oil_pressure">Oil Pressure: *</label>
                        <input type="number" name="me_oil_pressure" id="me_oil_pressure" step="1"
                               value="<?= htmlspecialchars($_POST['me_oil_pressure'] ?? '') ?>" placeholder="PSI">
                    </div>
                    
                    <div class="form-group">
                        <label for="me_fuel_press">Fuel Pressure: *</label>
                        <input type="number" name="me_fuel_press" id="me_fuel_press" step="1"
                               value="<?= htmlspecialchars($_POST['me_fuel_press'] ?? '') ?>" placeholder="PSI">
                    </div>
                    
                    <div class="form-group">
                        <label for="me_water_temp">Water Temperature: *</label>
                        <input type="number" name="me_water_temp" id="me_water_temp" step="1"
                               value="<?= htmlspecialchars($_POST['me_water_temp'] ?? '') ?>" placeholder="°F">
                    </div>
                    
                    <div class="form-group">
                        <label for="me_oil_temp">Oil Temperature: *</label>
                        <input type="number" name="me_oil_temp" id="me_oil_temp" step="1"
                               value="<?= htmlspecialchars($_POST['me_oil_temp'] ?? '') ?>" placeholder="°F">
                    </div>
                </div>
                
                <!-- Generators Fields -->
                <div id="generators-fields" class="equipment-fields">
                    <div class="form-group">
                        <label for="gen_hrs">Generator Hours: *</label>
                        <input type="number" name="gen_hrs" id="gen_hrs" step="1"
                               value="<?= htmlspecialchars($_POST['gen_hrs'] ?? '') ?>" placeholder="Generator Hours">
                    </div>
                    
                    <div class="form-group">
                        <label for="gen_oil_press">Oil Pressure: *</label>
                        <input type="number" name="gen_oil_press" id="gen_oil_press" step="1"
                               value="<?= htmlspecialchars($_POST['gen_oil_press'] ?? '') ?>" placeholder="PSI">
                    </div>
                    
                    <div class="form-group">
                        <label for="gen_fuel_press">Fuel Pressure: *</label>
                        <input type="number" name="gen_fuel_press" id="gen_fuel_press" step="1"
                               value="<?= htmlspecialchars($_POST['gen_fuel_press'] ?? '') ?>" placeholder="PSI">
                    </div>
                    
                    <div class="form-group">
                        <label for="gen_water_temp">Water Temperature: *</label>
                        <input type="number" name="gen_water_temp" id="gen_water_temp" step="1"
                               value="<?= htmlspecialchars($_POST['gen_water_temp'] ?? '') ?>" placeholder="°F">
                    </div>
                </div>
                
                <!-- Gears Fields -->
                <div id="gears-fields" class="equipment-fields">
                    <div class="form-group">
                        <label for="gear_hrs">Gear Hours: *</label>
                        <input type="number" name="gear_hrs" id="gear_hrs" step="1"
                               value="<?= htmlspecialchars($_POST['gear_hrs'] ?? '') ?>" placeholder="Gear Hours">
                    </div>
                    
                    <div class="form-group">
                        <label for="gear_oil_press">Oil Pressure: *</label>
                        <input type="number" name="gear_oil_press" id="gear_oil_press" step="1"
                               value="<?= htmlspecialchars($_POST['gear_oil_press'] ?? '') ?>" placeholder="PSI">
                    </div>
                    
                    <div class="form-group">
                        <label for="gear_temp">Temperature: *</label>
                        <input type="number" name="gear_temp" id="gear_temp" step="1"
                               value="<?= htmlspecialchars($_POST['gear_temp'] ?? '') ?>" placeholder="°F">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes:</label>
                    <textarea name="notes" id="notes" rows="4" placeholder="Additional notes or observations..."><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-success">💾 Save Log Entry</button>
                    <a href="index.php" class="btn btn-info">Cancel</a>
                    <a href="view_logs.php" class="btn btn-primary">View Logs</a>
                </div>
            </form>
        </div>
        
        <footer>
            <p>&copy; 2025 Vessel Data Logger | <a href="index.php">Home</a></p>
        </footer>
    </div>
    
    <script>
        function showEquipmentFields() {
            // Hide all equipment field sections
            document.querySelectorAll('.equipment-fields').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove required from all equipment-specific fields
            document.querySelectorAll('#mainengines-fields input, #generators-fields input, #gears-fields input').forEach(input => {
                input.removeAttribute('required');
            });
            
            // Show the selected equipment fields
            const selectedEquipment = document.getElementById('equipment_type').value;
            if (selectedEquipment) {
                const fieldsSection = document.getElementById(selectedEquipment + '-fields');
                if (fieldsSection) {
                    fieldsSection.classList.add('active');
                    
                    // Add required attribute to ALL visible fields for the selected equipment
                    if (selectedEquipment === 'mainengines') {
                        document.getElementById('me_rpm').setAttribute('required', 'required');
                        document.getElementById('me_main_hrs').setAttribute('required', 'required');
                        document.getElementById('me_oil_pressure').setAttribute('required', 'required');
                        document.getElementById('me_oil_temp').setAttribute('required', 'required');
                        document.getElementById('me_fuel_press').setAttribute('required', 'required');
                        document.getElementById('me_water_temp').setAttribute('required', 'required');
                    } else if (selectedEquipment === 'generators') {
                        document.getElementById('gen_hrs').setAttribute('required', 'required');
                        document.getElementById('gen_oil_press').setAttribute('required', 'required');
                        document.getElementById('gen_fuel_press').setAttribute('required', 'required');
                        document.getElementById('gen_water_temp').setAttribute('required', 'required');
                    } else if (selectedEquipment === 'gears') {
                        document.getElementById('gear_hrs').setAttribute('required', 'required');
                        document.getElementById('gear_oil_press').setAttribute('required', 'required');
                        document.getElementById('gear_temp').setAttribute('required', 'required');
                    }
                }
            }
        }
        
        // Show equipment fields on page load if equipment type is already selected
        document.addEventListener('DOMContentLoaded', function() {
            showEquipmentFields();
        });
    </script>
</body>
</html>
