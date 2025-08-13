<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../vessel_functions.php';
require_once __DIR__ . '/../../auth_functions.php';
require_once __DIR__ . '/../offline/sync_manager.php';

// Require login and vessel selection for data entry
require_vessel_selection();

// Get current user and active vessel
$current_user = get_logged_in_user();
$current_vessel = get_current_vessel($conn);

// Ensure we have a valid vessel
if (!$current_vessel) {
    header('Location: select_vessel.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$active_vessel_id = $current_vessel['VesselID'];

// Initialize offline sync manager
$sqlite_path = "../../vessel/offline/data/vessel_{$active_vessel_id}.db";
$sync_manager = new OfflineSyncManager($conn, $sqlite_path, $active_vessel_id, $current_user['user_id']);
$offline_db = $sync_manager->getSQLiteConnection();

// Check if we're online or offline
$is_online = checkInternetConnection();
$offline_status = $sync_manager->getOfflineStatus();

// Get available sides for this vessel
$available_sides = get_vessel_sides_offline($offline_db, $active_vessel_id, 'mainengines');

$message = '';
$message_type = '';

if ($_POST) {
    $equipment_type = $_POST['equipment_type'] ?? '';
    $side = $_POST['side'] ?? '';
    $entry_date = $_POST['entry_date'] ?? '';
    $recorded_by = $current_user['user_id']; // Use logged-in user ID
    $notes = $_POST['notes'] ?? '';
    
    // Validate required fields
    if (empty($equipment_type) || empty($side) || empty($entry_date)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } else {
        // Check for duplicate hours using offline database
        $duplicate_check_result = checkDuplicateHoursOffline($offline_db, $equipment_type, $side, $_POST, $active_vessel_id);
        
        if ($duplicate_check_result['is_duplicate']) {
            $message = $duplicate_check_result['message'];
            $message_type = 'error';
        } else {
            try {
                $record_data = [];
                $local_id = generateUUID();
                
                if ($equipment_type === 'mainengines') {
                    $rpm = $_POST['me_rpm'];
                    $main_hrs = $_POST['me_main_hrs'];
                    $oil_pressure = $_POST['me_oil_pressure'];
                    $oil_temp = $_POST['me_oil_temp'];
                    $fuel_press = $_POST['me_fuel_press'];
                    $water_temp = $_POST['me_water_temp'];
                    
                    // Insert into local SQLite database
                    $sql = "INSERT INTO mainengines (VesselID, EntryDate, Side, RPM, MainHrs, OilPressure, OilTemp, FuelPress, WaterTemp, RecordedBy, Notes, LocalID, SyncStatus) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                    $stmt = $offline_db->prepare($sql);
                    $stmt->bindValue(1, $active_vessel_id);
                    $stmt->bindValue(2, $entry_date);
                    $stmt->bindValue(3, $side);
                    $stmt->bindValue(4, $rpm);
                    $stmt->bindValue(5, $main_hrs);
                    $stmt->bindValue(6, $oil_pressure);
                    $stmt->bindValue(7, $oil_temp);
                    $stmt->bindValue(8, $fuel_press);
                    $stmt->bindValue(9, $water_temp);
                    $stmt->bindValue(10, $recorded_by);
                    $stmt->bindValue(11, $notes);
                    $stmt->bindValue(12, $local_id);
                    
                    $record_data = [
                        'VesselID' => $active_vessel_id,
                        'EntryDate' => $entry_date,
                        'Side' => $side,
                        'RPM' => $rpm,
                        'MainHrs' => $main_hrs,
                        'OilPressure' => $oil_pressure,
                        'OilTemp' => $oil_temp,
                        'FuelPress' => $fuel_press,
                        'WaterTemp' => $water_temp,
                        'RecordedBy' => $recorded_by,
                        'Notes' => $notes,
                        'LocalID' => $local_id
                    ];
                    
                } elseif ($equipment_type === 'generators') {
                    $gen_hrs = $_POST['gen_hrs'];
                    $oil_press = $_POST['gen_oil_press'];
                    $fuel_press = $_POST['gen_fuel_press'];
                    $water_temp = $_POST['gen_water_temp'];
                    
                    $sql = "INSERT INTO generators (VesselID, EntryDate, Side, GenHrs, OilPress, FuelPress, WaterTemp, RecordedBy, Notes, LocalID, SyncStatus) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                    $stmt = $offline_db->prepare($sql);
                    $stmt->bindValue(1, $active_vessel_id);
                    $stmt->bindValue(2, $entry_date);
                    $stmt->bindValue(3, $side);
                    $stmt->bindValue(4, $gen_hrs);
                    $stmt->bindValue(5, $oil_press);
                    $stmt->bindValue(6, $fuel_press);
                    $stmt->bindValue(7, $water_temp);
                    $stmt->bindValue(8, $recorded_by);
                    $stmt->bindValue(9, $notes);
                    $stmt->bindValue(10, $local_id);
                    
                    $record_data = [
                        'VesselID' => $active_vessel_id,
                        'EntryDate' => $entry_date,
                        'Side' => $side,
                        'GenHrs' => $gen_hrs,
                        'OilPress' => $oil_press,
                        'FuelPress' => $fuel_press,
                        'WaterTemp' => $water_temp,
                        'RecordedBy' => $recorded_by,
                        'Notes' => $notes,
                        'LocalID' => $local_id
                    ];
                    
                } elseif ($equipment_type === 'gears') {
                    $gear_hrs = $_POST['gear_hrs'];
                    $oil_press = $_POST['gear_oil_press'];
                    $temp = $_POST['gear_temp'];
                    
                    $sql = "INSERT INTO gears (VesselID, EntryDate, Side, GearHrs, OilPress, Temp, RecordedBy, Notes, LocalID, SyncStatus) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                    $stmt = $offline_db->prepare($sql);
                    $stmt->bindValue(1, $active_vessel_id);
                    $stmt->bindValue(2, $entry_date);
                    $stmt->bindValue(3, $side);
                    $stmt->bindValue(4, $gear_hrs);
                    $stmt->bindValue(5, $oil_press);
                    $stmt->bindValue(6, $temp);
                    $stmt->bindValue(7, $recorded_by);
                    $stmt->bindValue(8, $notes);
                    $stmt->bindValue(9, $local_id);
                    
                    $record_data = [
                        'VesselID' => $active_vessel_id,
                        'EntryDate' => $entry_date,
                        'Side' => $side,
                        'GearHrs' => $gear_hrs,
                        'OilPress' => $oil_press,
                        'Temp' => $temp,
                        'RecordedBy' => $recorded_by,
                        'Notes' => $notes,
                        'LocalID' => $local_id
                    ];
                }
                
                if ($stmt->execute()) {
                    // Add to sync queue for later synchronization
                    $sync_manager->queueOperation($equipment_type, $local_id, 'INSERT', $record_data, 3);
                    
                    // If online, attempt immediate sync
                    if ($is_online) {
                        $sync_result = $sync_manager->performFullSync();
                        if ($sync_result['success']) {
                            $message = 'Log entry added and synced successfully!';
                        } else {
                            $message = 'Log entry added locally. Will sync when connection is restored.';
                        }
                    } else {
                        $message = 'Log entry added locally. Will sync when connection is restored.';
                    }
                    
                    $message_type = 'success';
                    // Clear form data only on success
                    $_POST = [];
                } else {
                    $message = 'Error adding log entry to local database.';
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}

// Function to check for duplicate hours in offline database
function checkDuplicateHoursOffline($offline_db, $equipment_type, $side, $post_data, $vessel_id) {
    $result = ['is_duplicate' => false, 'message' => ''];
    
    if ($equipment_type === 'mainengines') {
        $current_hrs = (float)$post_data['me_main_hrs'];
        $entry_date = $post_data['entry_date'];
        
        // Check for exact match
        $sql = "SELECT MainHrs, EntryDate FROM mainengines WHERE VesselID = ? AND Side = ? AND MainHrs = ? AND EntryDate = ?";
        $stmt = $offline_db->prepare($sql);
        $stmt->bindValue(1, $vessel_id);
        $stmt->bindValue(2, $side);
        $stmt->bindValue(3, $current_hrs);
        $stmt->bindValue(4, $entry_date);
        $result_set = $stmt->execute();
        
        if ($row = $result_set->fetchArray()) {
            $result['is_duplicate'] = true;
            $result['message'] = "Duplicate hours detected: {$current_hrs} hours for {$side} engine on {$entry_date} already exists.";
            return $result;
        }
        
        // Check for decreasing hours
        $sql = "SELECT MainHrs, EntryDate FROM mainengines WHERE VesselID = ? AND Side = ? AND EntryDate <= ? ORDER BY EntryDate DESC, MainHrs DESC LIMIT 1";
        $stmt = $offline_db->prepare($sql);
        $stmt->bindValue(1, $vessel_id);
        $stmt->bindValue(2, $side);
        $stmt->bindValue(3, $entry_date);
        $result_set = $stmt->execute();
        
        if ($row = $result_set->fetchArray()) {
            $last_hrs = (float)$row['MainHrs'];
            $last_date = $row['EntryDate'];
            
            if ($current_hrs < $last_hrs) {
                $result['is_duplicate'] = true;
                $result['message'] = "Invalid hours: Current hours ({$current_hrs}) cannot be less than previous hours ({$last_hrs}) from {$last_date} for {$side} engine.";
            }
        }
    }
    // Similar logic for generators and gears...
    
    return $result;
}

// Function to get vessel sides from offline database
function get_vessel_sides_offline($offline_db, $vessel_id, $equipment_type) {
    $sql = "SELECT DISTINCT Side FROM $equipment_type WHERE VesselID = ? ORDER BY Side";
    $stmt = $offline_db->prepare($sql);
    $stmt->bindValue(1, $vessel_id);
    $result = $stmt->execute();
    
    $sides = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $sides[] = $row['Side'];
    }
    
    // Default sides if none found
    if (empty($sides)) {
        $sides = ['Port', 'Starboard'];
    }
    
    return $sides;
}

// Function to check internet connection
function checkInternetConnection() {
    $connected = @fopen("http://www.google.com:80/", "r");
    if ($connected) {
        fclose($connected);
        return true;
    }
    return false;
}

// Function to generate UUID
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Manual sync trigger (for testing or manual sync)
if (isset($_GET['sync']) && $_GET['sync'] === 'now') {
    if ($is_online) {
        $sync_result = $sync_manager->performFullSync();
        if ($sync_result['success']) {
            $message = "Sync completed successfully. Pushed: {$sync_result['pushed']}, Pulled: {$sync_result['pulled']}";
            $message_type = 'success';
        } else {
            $message = "Sync failed: " . $sync_result['error'];
            $message_type = 'error';
        }
    } else {
        $message = "Cannot sync: No internet connection";
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Engine Log - <?php echo htmlspecialchars($current_vessel['VesselName']); ?></title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .offline-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            z-index: 1000;
        }
        
        .online {
            background-color: #4CAF50;
            color: white;
        }
        
        .offline {
            background-color: #f44336;
            color: white;
        }
        
        .pending-changes {
            background-color: #ff9800;
            color: white;
            margin-top: 5px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .sync-button {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .sync-button:hover {
            background-color: #1976D2;
        }
        
        .sync-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <!-- Connection Status Indicator -->
    <div class="offline-indicator <?php echo $is_online ? 'online' : 'offline'; ?>">
        <?php echo $is_online ? '🟢 Online' : '🔴 Offline'; ?>
        
        <?php if ($offline_status['pending_changes'] > 0): ?>
            <div class="pending-changes">
                <?php echo $offline_status['pending_changes']; ?> pending changes
                <?php if ($is_online): ?>
                    <a href="?sync=now" class="sync-button">Sync Now</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <div class="header">
            <h1>Add Engine Log Entry</h1>
            <p>Vessel: <strong><?php echo htmlspecialchars($current_vessel['VesselName']); ?></strong></p>
            <p>User: <strong><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></strong></p>
            <p>Mode: <strong><?php echo $is_online ? 'Online' : 'Offline'; ?></strong></p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="logForm">
            <div class="form-group">
                <label for="equipment_type">Equipment Type:</label>
                <select name="equipment_type" id="equipment_type" required onchange="toggleFields()">
                    <option value="">Select Equipment</option>
                    <option value="mainengines" <?php echo (isset($_POST['equipment_type']) && $_POST['equipment_type'] === 'mainengines') ? 'selected' : ''; ?>>Main Engines</option>
                    <option value="generators" <?php echo (isset($_POST['equipment_type']) && $_POST['equipment_type'] === 'generators') ? 'selected' : ''; ?>>Generators</option>
                    <option value="gears" <?php echo (isset($_POST['equipment_type']) && $_POST['equipment_type'] === 'gears') ? 'selected' : ''; ?>>Gears</option>
                </select>
            </div>

            <div class="form-group">
                <label for="side">Side:</label>
                <select name="side" id="side" required>
                    <option value="">Select Side</option>
                    <?php foreach ($available_sides as $side_option): ?>
                        <option value="<?php echo htmlspecialchars($side_option); ?>" 
                                <?php echo (isset($_POST['side']) && $_POST['side'] === $side_option) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($side_option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="entry_date">Entry Date:</label>
                <input type="date" name="entry_date" id="entry_date" 
                       value="<?php echo isset($_POST['entry_date']) ? htmlspecialchars($_POST['entry_date']) : date('Y-m-d'); ?>" required>
            </div>

            <!-- Main Engine Fields -->
            <div id="mainengine_fields" style="display: none;">
                <h3>Main Engine Data</h3>
                <div class="form-group">
                    <label for="me_rpm">RPM:</label>
                    <input type="number" name="me_rpm" id="me_rpm" min="0" max="9999" 
                           value="<?php echo isset($_POST['me_rpm']) ? htmlspecialchars($_POST['me_rpm']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="me_main_hrs">Engine Hours:</label>
                    <input type="number" name="me_main_hrs" id="me_main_hrs" step="0.1" min="0" 
                           value="<?php echo isset($_POST['me_main_hrs']) ? htmlspecialchars($_POST['me_main_hrs']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="me_oil_pressure">Oil Pressure (PSI):</label>
                    <input type="number" name="me_oil_pressure" id="me_oil_pressure" min="0" max="999" 
                           value="<?php echo isset($_POST['me_oil_pressure']) ? htmlspecialchars($_POST['me_oil_pressure']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="me_oil_temp">Oil Temperature (°F):</label>
                    <input type="number" name="me_oil_temp" id="me_oil_temp" min="0" max="999" 
                           value="<?php echo isset($_POST['me_oil_temp']) ? htmlspecialchars($_POST['me_oil_temp']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="me_fuel_press">Fuel Pressure (PSI):</label>
                    <input type="number" name="me_fuel_press" id="me_fuel_press" min="0" max="999" 
                           value="<?php echo isset($_POST['me_fuel_press']) ? htmlspecialchars($_POST['me_fuel_press']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="me_water_temp">Water Temperature (°F):</label>
                    <input type="number" name="me_water_temp" id="me_water_temp" min="0" max="999" 
                           value="<?php echo isset($_POST['me_water_temp']) ? htmlspecialchars($_POST['me_water_temp']) : ''; ?>">
                </div>
            </div>

            <!-- Generator Fields -->
            <div id="generator_fields" style="display: none;">
                <h3>Generator Data</h3>
                <div class="form-group">
                    <label for="gen_hrs">Generator Hours:</label>
                    <input type="number" name="gen_hrs" id="gen_hrs" step="0.1" min="0" 
                           value="<?php echo isset($_POST['gen_hrs']) ? htmlspecialchars($_POST['gen_hrs']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="gen_oil_press">Oil Pressure (PSI):</label>
                    <input type="number" name="gen_oil_press" id="gen_oil_press" min="0" max="999" 
                           value="<?php echo isset($_POST['gen_oil_press']) ? htmlspecialchars($_POST['gen_oil_press']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="gen_fuel_press">Fuel Pressure (PSI):</label>
                    <input type="number" name="gen_fuel_press" id="gen_fuel_press" min="0" max="999" 
                           value="<?php echo isset($_POST['gen_fuel_press']) ? htmlspecialchars($_POST['gen_fuel_press']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="gen_water_temp">Water Temperature (°F):</label>
                    <input type="number" name="gen_water_temp" id="gen_water_temp" min="0" max="999" 
                           value="<?php echo isset($_POST['gen_water_temp']) ? htmlspecialchars($_POST['gen_water_temp']) : ''; ?>">
                </div>
            </div>

            <!-- Gear Fields -->
            <div id="gear_fields" style="display: none;">
                <h3>Gear Data</h3>
                <div class="form-group">
                    <label for="gear_hrs">Gear Hours:</label>
                    <input type="number" name="gear_hrs" id="gear_hrs" step="0.1" min="0" 
                           value="<?php echo isset($_POST['gear_hrs']) ? htmlspecialchars($_POST['gear_hrs']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="gear_oil_press">Oil Pressure (PSI):</label>
                    <input type="number" name="gear_oil_press" id="gear_oil_press" min="0" max="999" 
                           value="<?php echo isset($_POST['gear_oil_press']) ? htmlspecialchars($_POST['gear_oil_press']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="gear_temp">Temperature (°F):</label>
                    <input type="number" name="gear_temp" id="gear_temp" min="0" max="999" 
                           value="<?php echo isset($_POST['gear_temp']) ? htmlspecialchars($_POST['gear_temp']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Notes (Optional):</label>
                <textarea name="notes" id="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-primary">Add Log Entry</button>
                <a href="dashboard.php" class="btn-secondary">Cancel</a>
            </div>
        </form>

        <div class="actions">
            <a href="dashboard.php" class="btn-secondary">← Back to Dashboard</a>
            <a href="view_logs.php" class="btn-secondary">View Logs</a>
        </div>
    </div>

    <script>
        function toggleFields() {
            const equipmentType = document.getElementById('equipment_type').value;
            
            // Hide all field groups
            document.getElementById('mainengine_fields').style.display = 'none';
            document.getElementById('generator_fields').style.display = 'none';
            document.getElementById('gear_fields').style.display = 'none';
            
            // Show relevant field group
            if (equipmentType === 'mainengines') {
                document.getElementById('mainengine_fields').style.display = 'block';
            } else if (equipmentType === 'generators') {
                document.getElementById('generator_fields').style.display = 'block';
            } else if (equipmentType === 'gears') {
                document.getElementById('gear_fields').style.display = 'block';
            }
        }

        // Initialize form on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleFields();
            
            // Auto-save form data to localStorage for offline recovery
            const form = document.getElementById('logForm');
            const inputs = form.querySelectorAll('input, select, textarea');
            
            inputs.forEach(input => {
                // Load saved data
                const savedValue = localStorage.getItem('vessel_log_' + input.name);
                if (savedValue && !input.value) {
                    input.value = savedValue;
                }
                
                // Save data on change
                input.addEventListener('change', function() {
                    localStorage.setItem('vessel_log_' + input.name, input.value);
                });
            });
            
            // Clear localStorage on successful submission
            <?php if ($message_type === 'success'): ?>
            inputs.forEach(input => {
                localStorage.removeItem('vessel_log_' + input.name);
            });
            <?php endif; ?>
        });

        // Service Worker for offline functionality
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../../vessel/offline/sw.js')
                .then(function(registration) {
                    console.log('Service Worker registered successfully');
                })
                .catch(function(error) {
                    console.log('Service Worker registration failed:', error);
                });
        }
    </script>
</body>
</html>
