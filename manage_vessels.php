<?php
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

// Require login (only logged-in users can manage vessels)
require_login();
$current_user = get_logged_in_user();

// Check if database schema is ready
$schema_ready = true;
$schema_errors = [];

// Check if vessels table exists
$vessels_check = $conn->query("SHOW TABLES LIKE 'vessels'");
if ($vessels_check->num_rows == 0) {
    $schema_ready = false;
    $schema_errors[] = "Vessels table does not exist";
}

// Check if VesselID columns exist in equipment tables
$equipment_tables = ['mainengines', 'generators', 'gears'];
foreach ($equipment_tables as $table) {
    $column_check = $conn->query("SHOW COLUMNS FROM $table LIKE 'VesselID'");
    if ($column_check->num_rows == 0) {
        $schema_ready = false;
        $schema_errors[] = "VesselID column missing from $table table";
    }
}

if (!$schema_ready) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Setup Required - Vessel Data Logger</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="container">
            <header>
                <h1>⚠️ Database Setup Required</h1>
                <p><a href="index.php" class="btn btn-primary">🏠 Home</a></p>
            </header>
            
            <div class="alert alert-warning">
                <h3>Multi-vessel database schema not found</h3>
                <p>The following issues were detected:</p>
                <ul>
                    <?php foreach ($schema_errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
                
                <h4>To fix this:</h4>
                <ol>
                    <li><a href="check_schema.php" class="btn btn-warning">Run Database Schema Check/Fix</a></li>
                    <li>Or manually run: <a href="setup_multivessel.php" class="btn btn-info">Multi-vessel Setup Script</a></li>
                    <li>Then return to this page</li>
                </ol>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_vessel'])) {
        $vessel_name = trim($_POST['vessel_name']);
        $vessel_type = trim($_POST['vessel_type']);
        $engine_config = $_POST['engine_config'] ?? 'standard';
        $owner = trim($_POST['owner']);
        $year_built = !empty($_POST['year_built']) ? $_POST['year_built'] : null;
        $length = !empty($_POST['length']) ? $_POST['length'] : null;
        $notes = trim($_POST['notes']);
        $rpm_min = !empty($_POST['rpm_min']) ? (int)$_POST['rpm_min'] : 650;
        $rpm_max = !empty($_POST['rpm_max']) ? (int)$_POST['rpm_max'] : 1750;
        $temp_min = !empty($_POST['temp_min']) ? (int)$_POST['temp_min'] : 20;
        $temp_max = !empty($_POST['temp_max']) ? (int)$_POST['temp_max'] : 400;
        $gen_min = !empty($_POST['gen_min']) ? (int)$_POST['gen_min'] : 20;
        $gen_max = !empty($_POST['gen_max']) ? (int)$_POST['gen_max'] : 400;
        
        if (!empty($vessel_name)) {
            // Check if vessel name already exists
            $check_sql = "SELECT VesselID, VesselName FROM vessels WHERE VesselName = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param('s', $vessel_name);
            $check_stmt->execute();
            $existing = $check_stmt->get_result();
            
            if ($existing->num_rows > 0) {
                $error = "A vessel named '$vessel_name' already exists. Please choose a different name.";
            } else {
                $sql = "INSERT INTO vessels (VesselName, VesselType, EngineConfig, Owner, YearBuilt, Length, Notes, RPMMin, RPMMax, TempMin, TempMax, GenMin, GenMax) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssssidsiiiiii', $vessel_name, $vessel_type, $engine_config, $owner, $year_built, $length, $notes, $rpm_min, $rpm_max, $temp_min, $temp_max, $gen_min, $gen_max);
                
                if ($stmt->execute()) {
                    $success = "Vessel '$vessel_name' added successfully!";
                } else {
                    $error = "Error adding vessel: " . $conn->error;
                }
            }
        } else {
            $error = "Vessel name is required.";
        }
    }
    
    if (isset($_POST['set_active_vessel'])) {
        $vessel_id = (int)$_POST['vessel_id'];
        
        // Get vessel name for confirmation
        $sql = "SELECT VesselName FROM vessels WHERE VesselID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $vessel_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $vessel = $result->fetch_assoc();
        
        if ($vessel) {
            set_active_vessel($vessel_id, $vessel['VesselName']);
            $success = "Active vessel set to: " . $vessel['VesselName'];
        } else {
            $error = "Vessel not found.";
        }
    }
    
    if (isset($_POST['toggle_vessel_status'])) {
        $vessel_id = (int)$_POST['vessel_id'];
        $sql = "UPDATE vessels SET IsActive = NOT IsActive WHERE VesselID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $vessel_id);
        
        if ($stmt->execute()) {
            $success = "Vessel status updated successfully!";
        } else {
            $error = "Error updating vessel status.";
        }
    }
}

// Get current active vessel
$active_vessel_id = $_SESSION['active_vessel_id'] ?? 1;
$sql = "SELECT * FROM vessels WHERE VesselID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $active_vessel_id);
$stmt->execute();
$active_vessel = $stmt->get_result()->fetch_assoc();

// Get all vessels
$sql = "SELECT * FROM vessels ORDER BY IsActive DESC, VesselName ASC";
$vessels = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Get statistics for each vessel
$vessel_stats = [];
foreach ($vessels as $vessel) {
    $vessel_id = $vessel['VesselID'];
    $stats = [];
    
    // Count entries for each equipment type
    $tables = ['mainengines', 'generators', 'gears'];
    foreach ($tables as $table) {
        try {
            $sql = "SELECT COUNT(*) as count FROM $table WHERE VesselID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $vessel_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats[$table] = $result->fetch_assoc()['count'];
        } catch (mysqli_sql_exception $e) {
            // If VesselID column doesn't exist, show 0
            $stats[$table] = 0;
        }
    }
    
    $vessel_stats[$vessel_id] = $stats;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Management - Vessel Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .vessel-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 5px solid #007bff;
        }
        .vessel-card.active {
            border-left-color: #28a745;
            background: #f8fff9;
        }
        .vessel-card.inactive {
            border-left-color: #6c757d;
            background: #f8f9fa;
            opacity: 0.8;
        }
        .vessel-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        .vessel-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .vessel-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .vessel-info {
            color: #666;
            margin: 10px 0;
        }
        .vessel-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        .stat-item {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        .vessel-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .vessel-scales {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 13px;
            border-left: 3px solid #28a745;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .current-vessel {
            background: #e7f3ff;
            border: 2px solid #007bff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .add-vessel-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>🚢 Vessel Management</h1>
                    <p>
                        <a href="index.php" class="btn btn-primary">🏠 Home</a>
                        <a href="add_log.php" class="btn btn-success">📝 Add Log Entry</a>
                        <a href="view_logs.php" class="btn btn-info">📊 View Logs</a>
                        <?php if ($current_user['is_admin']): ?>
                            <a href="manage_users.php" class="btn btn-secondary">👥 User Management</a>
                        <?php endif; ?>
                    </p>
                </div>
                <div style="text-align: right;">
                    <div style="color: #666; font-size: 14px; margin-bottom: 5px;">
                        Logged in as: <strong><?= htmlspecialchars($current_user['full_name']) ?></strong>
                    </div>
                    <a href="logout.php" class="btn btn-secondary" style="font-size: 12px;">Logout</a>
                </div>
            </div>
        </header>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Current Active Vessel -->
        <div class="current-vessel">
            <h2>🎯 Currently Active Vessel</h2>
            <?php if ($active_vessel): ?>
                <h3><?= htmlspecialchars($active_vessel['VesselName']) ?></h3>
                <p><?= htmlspecialchars($active_vessel['VesselType']) ?></p>
                <p><small>All new log entries will be recorded for this vessel</small></p>
            <?php else: ?>
                <p>No active vessel selected. Please select a vessel below.</p>
            <?php endif; ?>
        </div>

        <!-- Add New Vessel Form -->
        <div class="add-vessel-form">
            <h2>➕ Add New Vessel</h2>
            <form method="POST">
                <div class="form-grid">
                    <div>
                        <label for="vessel_name">Vessel Name *</label>
                        <input type="text" id="vessel_name" name="vessel_name" required>
                    </div>
                    <div>
                        <label for="vessel_type">Vessel Type</label>
                        <select id="vessel_type" name="vessel_type">
                            <option value="Towboat">Towboat</option>
                            <option value="Fishing Vessel">Fishing Vessel</option>
                            <option value="Pleasure Craft">Pleasure Craft</option>
                            <option value="Work Boat">Work Boat</option>
                            <option value="Tug Boat">Tug Boat</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="engine_config">Engine Configuration</label>
                        <select id="engine_config" name="engine_config">
                            <option value="standard">Standard (Port & Starboard)</option>
                            <option value="three_engine">Three Engine (Port, Center, Starboard)</option>
                        </select>
                        <small style="color: #666; display: block; margin-top: 2px;">
                            Choose three engine for vessels with a center main engine
                        </small>
                    </div>
                    <div>
                        <label for="owner">Owner</label>
                        <input type="text" id="owner" name="owner">
                    </div>
                    <div>
                        <label for="year_built">Year Built</label>
                        <input type="number" id="year_built" name="year_built" min="1900" max="<?= date('Y') ?>">
                    </div>
                    <div>
                        <label for="length">Length (feet)</label>
                        <input type="number" id="length" name="length" step="0.1" min="0">
                    </div>
                    <div>
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                
                <!-- Chart Scale Settings -->
                <h3>📊 Chart Scale Settings</h3>
                <div class="form-grid">
                    <div>
                        <label for="rpm_min">RPM Min</label>
                        <input type="number" id="rpm_min" name="rpm_min" value="650" min="0">
                    </div>
                    <div>
                        <label for="rpm_max">RPM Max</label>
                        <input type="number" id="rpm_max" name="rpm_max" value="1750" min="0">
                    </div>
                    <div>
                        <label for="temp_min">Temp/Pressure Min</label>
                        <input type="number" id="temp_min" name="temp_min" value="20" min="0">
                    </div>
                    <div>
                        <label for="temp_max">Temp/Pressure Max</label>
                        <input type="number" id="temp_max" name="temp_max" value="400" min="0">
                    </div>
                    <div>
                        <label for="gen_min">Generator Temp/Pressure Min</label>
                        <input type="number" id="gen_min" name="gen_min" value="20" min="0">
                    </div>
                    <div>
                        <label for="gen_max">Generator Temp/Pressure Max</label>
                        <input type="number" id="gen_max" name="gen_max" value="400" min="0">
                    </div>
                </div>
                <button type="submit" name="add_vessel" class="btn btn-primary">Add Vessel</button>
            </form>
        </div>

        <!-- Vessels List -->
        <h2>🚢 All Vessels</h2>
        
        <?php foreach ($vessels as $vessel): ?>
            <?php 
            $is_active = $vessel['IsActive'] == 1;
            $is_current = $vessel['VesselID'] == $active_vessel_id;
            $stats = $vessel_stats[$vessel['VesselID']];
            ?>
            <div class="vessel-card <?= $is_current ? 'active' : ($is_active ? '' : 'inactive') ?>">
                <div class="vessel-header">
                    <div>
                        <div class="vessel-name">
                            <?= htmlspecialchars($vessel['VesselName']) ?>
                            <?php if ($is_current): ?>
                                <span style="color: #28a745;">⭐ ACTIVE</span>
                            <?php endif; ?>
                        </div>
                        <span class="vessel-status <?= $is_active ? 'status-active' : 'status-inactive' ?>">
                            <?= $is_active ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                </div>
                
                <div class="vessel-info">
                    <strong>Type:</strong> <?= htmlspecialchars($vessel['VesselType']) ?><br>
                    <strong>Engine Config:</strong> 
                    <?php if (($vessel['EngineConfig'] ?? 'standard') === 'three_engine'): ?>
                        <span style="color: #007bff;">⚙️ Three Engine (Port, Center, Starboard)</span>
                    <?php else: ?>
                        <span style="color: #666;">⚙️ Standard (Port, Starboard)</span>
                    <?php endif; ?><br>
                    <?php if ($vessel['Owner']): ?>
                        <strong>Owner:</strong> <?= htmlspecialchars($vessel['Owner']) ?><br>
                    <?php endif; ?>
                    <?php if ($vessel['YearBuilt']): ?>
                        <strong>Year Built:</strong> <?= $vessel['YearBuilt'] ?><br>
                    <?php endif; ?>
                    <?php if ($vessel['Length']): ?>
                        <strong>Length:</strong> <?= $vessel['Length'] ?> feet<br>
                    <?php endif; ?>
                    <strong>Added:</strong> <?= date('M j, Y', strtotime($vessel['CreatedDate'])) ?>
                </div>

                <?php if ($vessel['Notes']): ?>
                    <div class="vessel-info">
                        <strong>Notes:</strong> <?= htmlspecialchars($vessel['Notes']) ?>
                    </div>
                <?php endif; ?>

                <div class="vessel-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?= $stats['mainengines'] ?></div>
                        <div class="stat-label">Main Engine Logs</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $stats['generators'] ?></div>
                        <div class="stat-label">Generator Logs</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= $stats['gears'] ?></div>
                        <div class="stat-label">Gear Logs</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?= array_sum($stats) ?></div>
                        <div class="stat-label">Total Entries</div>
                    </div>
                </div>
                
                <div class="vessel-scales">
                    <strong>📊 Chart Scales:</strong>
                    RPM: <?= $vessel['RPMMin'] ?>-<?= $vessel['RPMMax'] ?> | 
                    Temp/Pressure: <?= $vessel['TempMin'] ?>-<?= $vessel['TempMax'] ?> (°F/PSI) | 
                    Generator: <?= $vessel['GenMin'] ?>-<?= $vessel['GenMax'] ?> (°F/PSI)
                </div>

                <div class="vessel-actions">
                    <?php if (!$is_current): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="vessel_id" value="<?= $vessel['VesselID'] ?>">
                            <button type="submit" name="set_active_vessel" class="btn btn-success">
                                🎯 Set as Active
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="vessel_id" value="<?= $vessel['VesselID'] ?>">
                        <button type="submit" name="toggle_vessel_status" class="btn btn-secondary">
                            <?= $is_active ? '⏸️ Deactivate' : '▶️ Activate' ?>
                        </button>
                    </form>
                    
                    <a href="view_logs.php?vessel_id=<?= $vessel['VesselID'] ?>" class="btn btn-info">
                        📊 View Logs
                    </a>
                    
                    <a href="edit_vessel_scales.php?vessel_id=<?= $vessel['VesselID'] ?>" class="btn btn-warning">
                        📏 Edit Scales
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

        <footer>
            <p>&copy; 2025 Vessel Data Logger | <a href="index.php">Home</a></p>
        </footer>
    </div>
</body>
</html>
