<?php
/**
 * Gearbox Management - Professional Marine Engine Room
 * Dedicated gearbox readings and monitoring
 */

require_once 'test_db.php';
require_once 'settings_helper.php';

$message = '';
$error = '';
$gearbox_type = $_GET['type'] ?? 'port_main';

// Validate gearbox type
$valid_gearboxes = ['port_main', 'center_main', 'starboard_main'];
if (!in_array($gearbox_type, $valid_gearboxes)) {
    $gearbox_type = 'port_main';
}

// Check if gearbox is active
$pdo = getTestDatabase();
$vessel_settings = getVesselSettings($pdo);
if (!isGearboxActive($gearbox_type, $vessel_settings)) {
    header('Location: index.php');
    exit('Access denied: Gearbox is disabled in vessel settings.');
}

$gearbox_name = ucwords(str_replace('_', ' ', $gearbox_type));

// Handle form submission
if ($_POST) {
    try {
        if (isset($_POST['add_gearbox_reading'])) {
            $stmt = $pdo->prepare("INSERT INTO gearbox_readings 
                (vessel_id, gearbox_type, gear_oil_temp, gear_oil_pressure, gear_oil_pressure_filter_in, 
                gear_oil_pressure_filter_out, gear_oil_temp_in, gear_oil_temp_out, cooler_sea_pressure, 
                cooler_temp_in, cooler_temp_out, clutch_air_pressure, rpm_input, rpm_output, 
                total_hours, hours_ran_today, notes, created_by) 
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Marine Engineer')");
            
            $stmt->execute([
                $gearbox_type,
                $_POST['gear_oil_temp'] ?: null,
                $_POST['gear_oil_pressure'] ?: null,
                $_POST['gear_oil_pressure_filter_in'] ?: null,
                $_POST['gear_oil_pressure_filter_out'] ?: null,
                $_POST['gear_oil_temp_in'] ?: null,
                $_POST['gear_oil_temp_out'] ?: null,
                $_POST['cooler_sea_pressure'] ?: null,
                $_POST['cooler_temp_in'] ?: null,
                $_POST['cooler_temp_out'] ?: null,
                $_POST['clutch_air_pressure'] ?: null,
                $_POST['rpm_input'] ?: null,
                $_POST['rpm_output'] ?: null,
                $_POST['total_hours'] ?: null,
                $_POST['hours_ran_today'] ?: null,
                $_POST['notes']
            ]);
            
            // Update gearbox hours if provided
            if ($_POST['total_hours']) {
                $new_total = floatval($_POST['total_hours']);
                $pdo->prepare("UPDATE gearbox_hours SET total_hours = ?, last_updated = datetime('now') 
                             WHERE gearbox_type = ?")->execute([$new_total, $gearbox_type]);
            }
            
            $message = "$gearbox_name gearbox reading recorded successfully!";
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get gearbox hours info
try {
    $stmt = $pdo->prepare("SELECT * FROM gearbox_hours WHERE gearbox_type = ?");
    $stmt->execute([$gearbox_type]);
    $gearbox_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent readings
    $stmt = $pdo->prepare("SELECT * FROM gearbox_readings WHERE gearbox_type = ? ORDER BY reading_date DESC LIMIT 10");
    $stmt->execute([$gearbox_type]);
    $recent_readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $gearbox_info = [];
    $recent_readings = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $gearbox_name; ?> Gearbox Management - Marine Engine Room</title>
    <link rel="stylesheet" href="test_styles.css">
</head>
<body>
    <div class="header">
        <h1>⚙️ <?php echo $gearbox_name; ?> Gearbox Management</h1>
        <p>Professional gearbox monitoring with detailed parameter readings</p>
        <a href="index.php" style="color: white; text-decoration: none;">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <!-- Gearbox Status Card -->
        <?php if ($gearbox_info): ?>
            <?php $overhaul_pct = round(($gearbox_info['hours_since_overhaul'] / $gearbox_info['next_overhaul_hours']) * 100, 1); ?>
            <div class="fuel-status-card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <h3><?php echo $gearbox_name; ?> Status</h3>
                <div class="fuel-amount-display">
                    <span class="fuel-amount-large"><?php echo number_format($gearbox_info['hours_since_overhaul'], 1); ?></span>
                    <span class="fuel-unit">hours since overhaul</span>
                </div>
                <div class="fuel-status">
                    <?php if ($overhaul_pct >= 90): ?>
                        <span class="status-critical">🔴 OVERHAUL DUE</span>
                        <p style="margin: 10px 0 0 0; font-size: 14px;">Schedule overhaul immediately</p>
                    <?php elseif ($overhaul_pct >= 75): ?>
                        <span class="status-warning">🟡 PLAN OVERHAUL</span>
                        <p style="margin: 10px 0 0 0; font-size: 14px;">Plan overhaul within next maintenance cycle</p>
                    <?php else: ?>
                        <span class="status-good">🟢 OPERATIONAL</span>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 15px; font-size: 14px;">
                    <p>Total Hours: <?php echo number_format($gearbox_info['total_hours'], 1); ?></p>
                    <p>Progress to Overhaul: <?php echo $overhaul_pct; ?>%</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Gearbox Reading Form -->
        <div class="form-section">
            <h3>📊 Record Gearbox Reading</h3>
            <form method="POST">
                <h4 style="margin-top: 20px; color: #2c3e50;">Gear Oil Parameters</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Gear Oil Temperature (°F):</label>
                        <input type="number" name="gear_oil_temp" step="0.1" placeholder="160.0">
                    </div>
                    <div class="form-group">
                        <label>Gear Oil Pressure (PSI):</label>
                        <input type="number" name="gear_oil_pressure" step="0.1" min="0" placeholder="25.0">
                    </div>
                    <div class="form-group">
                        <label>Gear Oil Temp In (°F):</label>
                        <input type="number" name="gear_oil_temp_in" step="0.1" placeholder="140.0">
                    </div>
                    <div class="form-group">
                        <label>Gear Oil Temp Out (°F):</label>
                        <input type="number" name="gear_oil_temp_out" step="0.1" placeholder="165.0">
                    </div>
                </div>
                
                <h4 style="color: #2c3e50;">Filter Pressures (PSI)</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Filter Pressure In (PSI):</label>
                        <input type="number" name="gear_oil_pressure_filter_in" step="0.1" placeholder="28.0">
                    </div>
                    <div class="form-group">
                        <label>Filter Pressure Out (PSI):</label>
                        <input type="number" name="gear_oil_pressure_filter_out" step="0.1" placeholder="25.0">
                    </div>
                </div>
                
                <h4 style="color: #2c3e50;">Cooler System</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Cooler Sea Pressure (PSI):</label>
                        <input type="number" name="cooler_sea_pressure" step="0.1" placeholder="12.0">
                    </div>
                    <div class="form-group">
                        <label>Cooler Temp In (°F):</label>
                        <input type="number" name="cooler_temp_in" step="0.1" placeholder="80.0">
                    </div>
                    <div class="form-group">
                        <label>Cooler Temp Out (°F):</label>
                        <input type="number" name="cooler_temp_out" step="0.1" placeholder="160.0">
                    </div>
                </div>
                
                <h4 style="color: #2c3e50;">Operating Parameters</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Clutch Air Pressure (PSI):</label>
                        <input type="number" name="clutch_air_pressure" step="0.1" placeholder="100.0">
                    </div>
                    <div class="form-group">
                        <label>Input RPM:</label>
                        <input type="number" name="rpm_input" min="0" placeholder="1800">
                    </div>
                    <div class="form-group">
                        <label>Output RPM:</label>
                        <input type="number" name="rpm_output" min="0" placeholder="120">
                    </div>
                </div>
                
                <h4 style="color: #2c3e50;">Gearbox Hours</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Total Gearbox Hours:</label>
                        <input type="number" name="total_hours" step="0.1" placeholder="<?php echo $gearbox_info['total_hours'] ?? '14523.2'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Hours Ran Today:</label>
                        <input type="number" name="hours_ran_today" step="0.1" placeholder="8.5">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label>Notes:</label>
                    <textarea name="notes" rows="3" placeholder="Gearbox operating normally, no unusual vibrations..."></textarea>
                </div>
                
                <button type="submit" name="add_gearbox_reading" class="btn btn-success">Record Gearbox Reading</button>
            </form>
        </div>

        <!-- Recent Readings -->
        <div class="form-section">
            <h3>📋 Recent Gearbox Readings</h3>
            <?php if (!empty($recent_readings)): ?>
                <div class="readings-table-container">
                    <table class="readings-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Oil Temp</th>
                                <th>Oil Press</th>
                                <th>Input RPM</th>
                                <th>Output RPM</th>
                                <th>Total Hours</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_readings as $reading): ?>
                                <tr>
                                    <td><?php echo date('M j, Y H:i', strtotime($reading['reading_date'])); ?></td>
                                    <td><?php echo $reading['gear_oil_temp'] ? number_format($reading['gear_oil_temp'], 1) . '°F' : '--'; ?></td>
                                    <td><?php echo $reading['gear_oil_pressure'] ? number_format($reading['gear_oil_pressure'], 1) . ' PSI' : '--'; ?></td>
                                    <td><?php echo $reading['rpm_input'] ? number_format($reading['rpm_input'], 0) : '--'; ?></td>
                                    <td><?php echo $reading['rpm_output'] ? number_format($reading['rpm_output'], 0) : '--'; ?></td>
                                    <td><?php echo $reading['total_hours'] ? number_format($reading['total_hours'], 1) : '--'; ?></td>
                                    <td><?php echo htmlspecialchars($reading['notes'] ?: 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; font-style: italic;">No gearbox readings recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
