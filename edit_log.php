<?php
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

// Require login and vessel selection for editing logs
require_vessel_selection();

// Get current user and vessel
$current_user = get_logged_in_user();
$current_vessel = get_current_vessel($conn);

$message = '';
$message_type = '';
$record = null;
$equipment_type = $_GET['equipment'] ?? '';
$entry_id = $_GET['id'] ?? '';

// Validate parameters
if (empty($equipment_type) || empty($entry_id) || !in_array($equipment_type, ['mainengines', 'generators', 'gears'])) {
    $message = 'Invalid parameters. Please select a record to edit from the view logs page.';
    $message_type = 'error';
} else {
    // Fetch the existing record with user name
    $sql = "SELECT e.*, COALESCE(CONCAT(u.FirstName, ' ', u.LastName), 'Unknown User') as RecordedByName 
            FROM $equipment_type e 
            LEFT JOIN users u ON e.RecordedBy = u.UserID 
            WHERE e.EntryID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $entry_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $message = 'Record not found.';
        $message_type = 'error';
    } else {
        $record = $result->fetch_assoc();
    }
}

// Handle form submission for updates
if ($_POST && $record) {
    // Check if this is a delete operation
    if (isset($_POST['delete_record'])) {
        try {
            $delete_sql = "DELETE FROM $equipment_type WHERE EntryID = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param('i', $entry_id);
            
            if ($delete_stmt->execute()) {
                $message = 'Record deleted successfully! <a href="view_logs.php?equipment=' . $equipment_type . '">Return to view logs</a>';
                $message_type = 'success';
                $record = null; // Clear the record so form doesn't display
            } else {
                $message = 'Error deleting record: ' . $delete_stmt->error;
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        // Regular update operation
        $side = $_POST['side'] ?? '';
        $entry_date = $_POST['entry_date'] ?? '';
        $recorded_by = $current_user['user_id']; // Use current user ID for edits
        $notes = $_POST['notes'] ?? '';
        
        // Validate required fields
        if (empty($side) || empty($entry_date)) {
            $message = 'Please fill in all required fields.';
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
                
                $sql = "UPDATE mainengines SET Side=?, EntryDate=?, RPM=?, MainHrs=?, OilPressure=?, OilTemp=?, FuelPress=?, WaterTemp=?, RecordedBy=?, Notes=? WHERE EntryID=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssiiiiiissi', $side, $entry_date, $rpm, $main_hrs, $oil_pressure, $oil_temp, $fuel_press, $water_temp, $recorded_by, $notes, $entry_id);
                
            } elseif ($equipment_type === 'generators') {
                $gen_hrs = $_POST['gen_hrs'];
                $oil_press = $_POST['gen_oil_press'];
                $fuel_press = $_POST['gen_fuel_press'];
                $water_temp = $_POST['gen_water_temp'];
                
                $sql = "UPDATE generators SET Side=?, EntryDate=?, GenHrs=?, OilPress=?, FuelPress=?, WaterTemp=?, RecordedBy=?, Notes=? WHERE EntryID=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssiiiissi', $side, $entry_date, $gen_hrs, $oil_press, $fuel_press, $water_temp, $recorded_by, $notes, $entry_id);
                
            } elseif ($equipment_type === 'gears') {
                $gear_hrs = $_POST['gear_hrs'];
                $oil_press = $_POST['gear_oil_press'];
                $temp = $_POST['gear_temp'];
                
                $sql = "UPDATE gears SET Side=?, EntryDate=?, GearHrs=?, OilPress=?, Temp=?, RecordedBy=?, Notes=? WHERE EntryID=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssiiissi', $side, $entry_date, $gear_hrs, $oil_press, $temp, $recorded_by, $notes, $entry_id);
            }
            
            if ($stmt->execute()) {
                $message = 'Record updated successfully!';
                $message_type = 'success';
                
                // Refresh the record data with user name
                $refresh_sql = "SELECT e.*, COALESCE(CONCAT(u.FirstName, ' ', u.LastName), 'Unknown User') as RecordedByName 
                               FROM $equipment_type e 
                               LEFT JOIN users u ON e.RecordedBy = u.UserID 
                               WHERE e.EntryID = ?";
                $refresh_stmt = $conn->prepare($refresh_sql);
                $refresh_stmt->bind_param('i', $entry_id);
                $refresh_stmt->execute();
                $refresh_result = $refresh_stmt->get_result();
                $record = $refresh_result->fetch_assoc();
                
            } else {
                $message = 'Error updating record: ' . $stmt->error;
                $message_type = 'error';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Equipment Log - Vessel Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
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
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .record-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>✏️ Edit Equipment Log Entry</h1>
            <p><a href="view_logs.php" class="btn btn-info">← Back to View Logs</a></p>
        </header>
        
        <div class="form-container">
            <?php if (!empty($message)): ?>
                <div class="message <?= $message_type ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <?php if ($record): ?>
                <div class="record-info">
                    <h3>Editing: <?= ucfirst($equipment_type) ?> Entry #<?= $record['EntryID'] ?></h3>
                    <p><strong>Original Entry Date:</strong> <?= $record['EntryDate'] ?> | <strong>Created:</strong> <?= $record['Timestamp'] ?></p>
                </div>
                
                <form method="POST" action="">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        
                        <div class="form-group">
                            <label for="side">Side: *</label>
                            <select name="side" id="side" required>
                                <option value="">Select Side</option>
                                <option value="Port" <?= $record['Side'] === 'Port' ? 'selected' : '' ?>>Port</option>
                                <option value="Starboard" <?= $record['Side'] === 'Starboard' ? 'selected' : '' ?>>Starboard</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="entry_date">Entry Date: *</label>
                            <input type="date" name="entry_date" id="entry_date" required 
                                   value="<?= htmlspecialchars($record['EntryDate']) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="recorded_by">Originally Recorded By:</label>
                            <input type="text" name="recorded_by_display" id="recorded_by_display" readonly
                                   value="<?= htmlspecialchars($record['RecordedByName']) ?>" 
                                   style="background-color: #f5f5f5; cursor: not-allowed;">
                            <small style="color: #666; display: block; margin-top: 5px;">
                                ℹ️ Edit will be attributed to: <?= htmlspecialchars($current_user['full_name']) ?>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Equipment-specific fields -->
                    <div class="equipment-fields">
                        <?php if ($equipment_type === 'mainengines'): ?>
                            <div class="form-group">
                                <label for="me_rpm">RPM: *</label>
                                <input type="number" name="me_rpm" id="me_rpm" step="1" required
                                       value="<?= htmlspecialchars($record['RPM']) ?>" placeholder="Engine RPM">
                            </div>
                            
                            <div class="form-group">
                                <label for="me_main_hrs">Main Hours: *</label>
                                <input type="number" name="me_main_hrs" id="me_main_hrs" step="1" required
                                       value="<?= htmlspecialchars($record['MainHrs']) ?>" placeholder="Engine Hours">
                            </div>
                            
                            <div class="form-group">
                                <label for="me_oil_pressure">Oil Pressure: *</label>
                                <input type="number" name="me_oil_pressure" id="me_oil_pressure" step="1" required
                                       value="<?= htmlspecialchars($record['OilPressure']) ?>" placeholder="PSI">
                            </div>
                            
                            <div class="form-group">
                                <label for="me_oil_temp">Oil Temperature: *</label>
                                <input type="number" name="me_oil_temp" id="me_oil_temp" step="1" required
                                       value="<?= htmlspecialchars($record['OilTemp']) ?>" placeholder="°F">
                            </div>
                            
                            <div class="form-group">
                                <label for="me_fuel_press">Fuel Pressure: *</label>
                                <input type="number" name="me_fuel_press" id="me_fuel_press" step="1" required
                                       value="<?= htmlspecialchars($record['FuelPress']) ?>" placeholder="PSI">
                            </div>
                            
                            <div class="form-group">
                                <label for="me_water_temp">Water Temperature: *</label>
                                <input type="number" name="me_water_temp" id="me_water_temp" step="1" required
                                       value="<?= htmlspecialchars($record['WaterTemp']) ?>" placeholder="°F">
                            </div>
                            
                        <?php elseif ($equipment_type === 'generators'): ?>
                            <div class="form-group">
                                <label for="gen_hrs">Generator Hours: *</label>
                                <input type="number" name="gen_hrs" id="gen_hrs" step="1" required
                                       value="<?= htmlspecialchars($record['GenHrs']) ?>" placeholder="Generator Hours">
                            </div>
                            
                            <div class="form-group">
                                <label for="gen_oil_press">Oil Pressure: *</label>
                                <input type="number" name="gen_oil_press" id="gen_oil_press" step="1" required
                                       value="<?= htmlspecialchars($record['OilPress']) ?>" placeholder="PSI">
                            </div>
                            
                            <div class="form-group">
                                <label for="gen_fuel_press">Fuel Pressure: *</label>
                                <input type="number" name="gen_fuel_press" id="gen_fuel_press" step="1" required
                                       value="<?= htmlspecialchars($record['FuelPress']) ?>" placeholder="PSI">
                            </div>
                            
                            <div class="form-group">
                                <label for="gen_water_temp">Water Temperature: *</label>
                                <input type="number" name="gen_water_temp" id="gen_water_temp" step="1" required
                                       value="<?= htmlspecialchars($record['WaterTemp']) ?>" placeholder="°F">
                            </div>
                            
                        <?php elseif ($equipment_type === 'gears'): ?>
                            <div class="form-group">
                                <label for="gear_hrs">Gear Hours: *</label>
                                <input type="number" name="gear_hrs" id="gear_hrs" step="1" required
                                       value="<?= htmlspecialchars($record['GearHrs']) ?>" placeholder="Gear Hours">
                            </div>
                            
                            <div class="form-group">
                                <label for="gear_oil_press">Oil Pressure: *</label>
                                <input type="number" name="gear_oil_press" id="gear_oil_press" step="1" required
                                       value="<?= htmlspecialchars($record['OilPress']) ?>" placeholder="PSI">
                            </div>
                            
                            <div class="form-group">
                                <label for="gear_temp">Temperature: *</label>
                                <input type="number" name="gear_temp" id="gear_temp" step="1" required
                                       value="<?= htmlspecialchars($record['Temp']) ?>" placeholder="°F">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes:</label>
                        <textarea name="notes" id="notes" rows="4" placeholder="Additional notes or observations..."><?= htmlspecialchars($record['Notes'] ?? '') ?></textarea>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <button type="submit" class="btn btn-success">💾 Update Record</button>
                        <a href="view_logs.php?equipment=<?= $equipment_type ?>" class="btn btn-info">Cancel</a>
                        <a href="add_log.php" class="btn btn-primary">Add New Entry</a>
                        
                        <!-- Delete button with confirmation -->
                        <button type="submit" name="delete_record" class="btn btn-danger" 
                                onclick="return confirm('⚠️ Are you sure you want to DELETE this record?\n\nThis action cannot be undone!');" 
                                style="margin-left: 20px;">
                            🗑️ Delete Record
                        </button>
                    </div>
                </form>
                
            <?php else: ?>
                <p>No record to edit. <a href="view_logs.php">Go back to view logs</a>.</p>
            <?php endif; ?>
        </div>
        
        <footer>
            <p>&copy; 2025 Vessel Data Logger | <a href="index.php">Home</a></p>
        </footer>
    </div>
</body>
</html>
