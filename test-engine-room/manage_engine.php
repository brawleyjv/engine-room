<?php
/**
 * Engine Management - Professional Marine Engine Room
 * Dedicated engine readings and monitoring
 */

require_once 'test_db.php';
require_once 'settings_helper.php';

$message = '';
$error = '';
$engine_type = $_GET['type'] ?? 'port_main';

// Validate engine type
$valid_engines = ['port_main', 'center_main', 'starboard_main'];
if (!in_array($engine_type, $valid_engines)) {
    $engine_type = 'port_main';
}

// Check if engine is active
$pdo = getTestDatabase();
$vessel_settings = getVesselSettings($pdo);
if (!isEngineActive($engine_type, $vessel_settings)) {
    header('Location: index.php');
    exit('Access denied: Engine is disabled in vessel settings.');
}

$engine_name = ucwords(str_replace('_', ' ', $engine_type));

// Handle form submission
if ($_POST) {
    try {
        if (isset($_POST['add_engine_reading'])) {
            $stmt = $pdo->prepare("INSERT INTO engine_readings 
                (vessel_id, engine_type, rpm, fuel_pressure, oil_pressure, water_temp_in, water_temp_out, 
                oil_temp_in, oil_temp_out, turbo_oil_pressure, governor_air_pressure, aftercooler_water_pressure,
                lube_oil_filter_pressure_in, lube_oil_filter_pressure_out, aftercooler_water_temp_out,
                air_box_pressure, crankcase_vacuum, ship_air_pressure, engine_hours_total, hours_ran_today, notes, created_by) 
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Marine Engineer')");
            
            $stmt->execute([
                $engine_type,
                $_POST['rpm'] ?: null,
                $_POST['fuel_pressure'] ?: null,
                $_POST['oil_pressure'] ?: null,
                $_POST['water_temp_in'] ?: null,
                $_POST['water_temp_out'] ?: null,
                $_POST['oil_temp_in'] ?: null,
                $_POST['oil_temp_out'] ?: null,
                $_POST['turbo_oil_pressure'] ?: null,
                $_POST['governor_air_pressure'] ?: null,
                $_POST['aftercooler_water_pressure'] ?: null,
                $_POST['lube_oil_filter_pressure_in'] ?: null,
                $_POST['lube_oil_filter_pressure_out'] ?: null,
                $_POST['aftercooler_water_temp_out'] ?: null,
                $_POST['air_box_pressure'] ?: null,
                $_POST['crankcase_vacuum'] ?: null,
                $_POST['ship_air_pressure'] ?: null,
                $_POST['engine_hours_total'] ?: null,
                $_POST['hours_ran_today'] ?: null,
                $_POST['notes']
            ]);
            
            // Update engine hours if provided
            if ($_POST['engine_hours_total']) {
                $new_total = floatval($_POST['engine_hours_total']);
                $pdo->prepare("UPDATE engine_hours SET total_hours = ?, last_updated = datetime('now') 
                             WHERE engine_type = ?")->execute([$new_total, $engine_type]);
            }
            
            $message = "$engine_name engine reading recorded successfully!";
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get engine hours info
try {
    $stmt = $pdo->prepare("SELECT * FROM engine_hours WHERE engine_type = ?");
    $stmt->execute([$engine_type]);
    $engine_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent readings
    $stmt = $pdo->prepare("SELECT * FROM engine_readings WHERE engine_type = ? ORDER BY reading_date DESC LIMIT 10");
    $stmt->execute([$engine_type]);
    $recent_readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $engine_info = [];
    $recent_readings = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $engine_name; ?> Engine Management - Marine Engine Room</title>
    <link rel="stylesheet" href="test_styles.css">
</head>
<body>
    <div class="header">
        <h1>🔧 <?php echo $engine_name; ?> Engine Management</h1>
        <p>Professional engine monitoring with detailed parameter readings</p>
        <a href="index.php" style="color: white; text-decoration: none;">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <!-- Engine Status Card -->
        <?php if ($engine_info): ?>
            <?php 
            $overhaul_interval = getEngineOverhaulInterval($vessel_settings);
            $overhaul_pct = calculateOverhaulProgress($engine_info['hours_since_overhaul'], $overhaul_interval);
            $status = getOverhaulStatus($overhaul_pct);
            ?>
            <div class="fuel-status-card" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <h3><?php echo $engine_name; ?> Status</h3>
                <div class="fuel-amount-display">
                    <span class="fuel-amount-large"><?php echo number_format($engine_info['hours_since_overhaul'], 1); ?></span>
                    <span class="fuel-unit">hours since overhaul</span>
                </div>
                <div class="fuel-status">
                    <span class="<?php echo $status['class']; ?>"><?php echo $status['color'] . ' ' . $status['status']; ?></span>
                    <?php if ($overhaul_pct >= 75): ?>
                        <p style="margin: 10px 0 0 0; font-size: 14px;">
                            <?php echo $overhaul_pct >= 90 ? 'Schedule overhaul immediately' : 'Plan overhaul within next maintenance cycle'; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div style="margin-top: 15px; font-size: 14px;">
                    <p>Total Hours: <?php echo number_format($engine_info['total_hours'], 1); ?></p>
                    <p>Progress to Overhaul: <?php echo $overhaul_pct; ?>%</p>
                    <p>Overhaul Interval: <?php echo number_format($overhaul_interval); ?> hours</p>
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

        <!-- Engine Reading Form -->
        <div class="form-section">
            <h3>📊 Record Engine Reading</h3>
            <form method="POST">
                <h4 style="margin-top: 20px; color: #2c3e50;">Basic Engine Parameters</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>RPM:</label>
                        <input type="number" name="rpm" min="0" max="5000" placeholder="1800">
                    </div>
                    <div class="form-group">
                        <label>Fuel Pressure (PSI):</label>
                        <input type="number" name="fuel_pressure" step="0.1" min="0" placeholder="45.0">
                    </div>
                    <div class="form-group">
                        <label>Oil Pressure (PSI):</label>
                        <input type="number" name="oil_pressure" step="0.1" min="0" placeholder="55.0">
                    </div>
                </div>
                
                <h4 style="color: #2c3e50;">Temperature Readings (°F)</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Water Temp In (°F):</label>
                        <input type="number" name="water_temp_in" step="0.1" placeholder="180.0">
                    </div>
                    <div class="form-group">
                        <label>Water Temp Out (°F):</label>
                        <input type="number" name="water_temp_out" step="0.1" placeholder="195.0">
                    </div>
                    <div class="form-group">
                        <label>Oil Temp In (°F):</label>
                        <input type="number" name="oil_temp_in" step="0.1" placeholder="160.0">
                    </div>
                    <div class="form-group">
                        <label>Oil Temp Out (°F):</label>
                        <input type="number" name="oil_temp_out" step="0.1" placeholder="175.0">
                    </div>
                    <div class="form-group">
                        <label>Aftercooler Water Temp Out (°F):</label>
                        <input type="number" name="aftercooler_water_temp_out" step="0.1" placeholder="110.0">
                    </div>
                </div>
                
                <h4 style="color: #2c3e50;">Advanced Pressure Readings (PSI)</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Turbo Oil Pressure:</label>
                        <input type="number" name="turbo_oil_pressure" step="0.1" placeholder="25.0">
                    </div>
                    <div class="form-group">
                        <label>Governor Air Pressure:</label>
                        <input type="number" name="governor_air_pressure" step="0.1" placeholder="120.0">
                    </div>
                    <div class="form-group">
                        <label>Aftercooler Water Pressure:</label>
                        <input type="number" name="aftercooler_water_pressure" step="0.1" placeholder="15.0">
                    </div>
                    <div class="form-group">
                        <label>Lube Oil Filter Press In (PSI):</label>
                        <input type="number" name="lube_oil_filter_pressure_in" step="0.1" placeholder="60.0">
                    </div>
                    <div class="form-group">
                        <label>Lube Oil Filter Press Out (PSI):</label>
                        <input type="number" name="lube_oil_filter_pressure_out" step="0.1" placeholder="55.0">
                    </div>
                    <div class="form-group">
                        <label>Air Box Pressure (PSI):</label>
                        <input type="number" name="air_box_pressure" step="0.1" placeholder="8.5">
                    </div>
                    <div class="form-group">
                        <label>Crankcase Vacuum (inHg):</label>
                        <input type="number" name="crankcase_vacuum" step="0.1" placeholder="2.5">
                    </div>
                    <div class="form-group">
                        <label>Ship Air Pressure (PSI):</label>
                        <input type="number" name="ship_air_pressure" step="0.1" placeholder="100.0">
                    </div>
                </div>
                
                <h4 style="color: #2c3e50;">Engine Hours</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Total Engine Hours:</label>
                        <input type="number" name="engine_hours_total" step="0.1" placeholder="<?php echo $engine_info['total_hours'] ?? '15847.5'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Hours Ran Today:</label>
                        <input type="number" name="hours_ran_today" step="0.1" placeholder="8.5">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label>Notes:</label>
                    <textarea name="notes" rows="3" placeholder="Engine running normally, no issues observed..."></textarea>
                </div>
                
                <button type="submit" name="add_engine_reading" class="btn btn-success">Record Engine Reading</button>
            </form>
        </div>

        <!-- Recent Readings -->
        <div class="form-section">
            <h3>📋 Recent Engine Readings</h3>
            <?php if (!empty($recent_readings)): ?>
                <div class="readings-table-container">
                    <table class="readings-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>RPM</th>
                                <th>Oil Press</th>
                                <th>Water Temp</th>
                                <th>Engine Hours</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_readings as $reading): ?>
                                <tr>
                                    <td><?php echo date('M j, Y H:i', strtotime($reading['reading_date'])); ?></td>
                                    <td><?php echo $reading['rpm'] ? number_format($reading['rpm'], 0) : '--'; ?></td>
                                    <td><?php echo $reading['oil_pressure'] ? number_format($reading['oil_pressure'], 1) . ' PSI' : '--'; ?></td>
                                    <td><?php echo $reading['water_temp_out'] ? number_format($reading['water_temp_out'], 1) . '°F' : '--'; ?></td>
                                    <td><?php echo $reading['engine_hours_total'] ? number_format($reading['engine_hours_total'], 1) : '--'; ?></td>
                                    <td><?php echo htmlspecialchars($reading['notes'] ?: 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; font-style: italic;">No engine readings recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
