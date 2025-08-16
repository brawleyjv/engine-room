<?php
/**
 * Generator Management - Professional Marine Engine Room
 * Dedicated generator readings and monitoring
 */

require_once 'test_db.php';
require_once 'settings_helper.php';

$message = '';
$error = '';
$generator_type = $_GET['type'] ?? 'port_gen';

// Validate generator type
$valid_generators = ['port_gen', 'center_gen', 'starboard_gen'];
if (!in_array($generator_type, $valid_generators)) {
    $generator_type = 'port_gen';
}

// Check if generator is active
$pdo = getTestDatabase();
$vessel_settings = getVesselSettings($pdo);
if (!isGeneratorActive($generator_type, $vessel_settings)) {
    header('Location: index.php');
    exit('Access denied: Generator is disabled in vessel settings.');
}

$generator_name = ucwords(str_replace(['_gen', '_'], [' Generator', ' '], $generator_type));

// Handle form submission
if ($_POST) {
    try {
        if (isset($_POST['add_generator_reading'])) {
            $stmt = $pdo->prepare("INSERT INTO generator_readings 
                (vessel_id, generator_type, rpm, battery_voltage, water_temp_in, water_temp_out, lube_oil_pressure, 
                fuel_pressure, voltage_out, frequency_hz, amperage, engine_hours_total, hours_ran_today, notes, created_by) 
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Marine Engineer')");
            
            $stmt->execute([
                $generator_type,
                $_POST['rpm'] ?: null,
                $_POST['battery_voltage'] ?: null,
                $_POST['water_temp_in'] ?: null,
                $_POST['water_temp_out'] ?: null,
                $_POST['lube_oil_pressure'] ?: null,
                $_POST['fuel_pressure'] ?: null,
                $_POST['voltage_out'] ?: null,
                $_POST['frequency_hz'] ?: null,
                $_POST['amperage'] ?: null,
                $_POST['engine_hours_total'] ?: null,
                $_POST['hours_ran_today'] ?: null,
                $_POST['notes']
            ]);
            
            // Update generator hours if provided
            if ($_POST['engine_hours_total']) {
                $new_total = floatval($_POST['engine_hours_total']);
                $pdo->prepare("UPDATE generator_hours SET total_hours = ?, last_updated = datetime('now') 
                             WHERE generator_type = ?")->execute([$new_total, $generator_type]);
            }
            
            $message = "$generator_name reading recorded successfully!";
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get generator hours info
try {
    $stmt = $pdo->prepare("SELECT * FROM generator_hours WHERE generator_type = ?");
    $stmt->execute([$generator_type]);
    $generator_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent readings
    $stmt = $pdo->prepare("SELECT * FROM generator_readings WHERE generator_type = ? ORDER BY reading_date DESC LIMIT 10");
    $stmt->execute([$generator_type]);
    $recent_readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $generator_info = [];
    $recent_readings = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $generator_name; ?> Management - Marine Engine Room</title>
    <link rel="stylesheet" href="test_styles.css">
</head>
<body>
    <div class="header">
        <h1>🔌 <?php echo $generator_name; ?> Management</h1>
        <p>Professional generator monitoring with detailed parameter readings</p>
        <a href="index.php" style="color: white; text-decoration: none;">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <!-- Generator Status Card -->
        <?php if ($generator_info): ?>
            <?php $overhaul_pct = round(($generator_info['hours_since_overhaul'] / $generator_info['next_overhaul_hours']) * 100, 1); ?>
            <div class="fuel-status-card" style="background: linear-gradient(135deg, #e67e22, #d35400);">
                <h3><?php echo $generator_name; ?> Status</h3>
                <div class="fuel-amount-display">
                    <span class="fuel-amount-large"><?php echo number_format($generator_info['hours_since_overhaul'], 1); ?></span>
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
                    <p>Total Hours: <?php echo number_format($generator_info['total_hours'], 1); ?></p>
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

        <!-- Generator Reading Form -->
        <div class="form-section">
            <h3>📊 Record Generator Reading</h3>
            <form method="POST">
                <h4 style="margin-top: 20px; color: #2c3e50;">Engine Parameters</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>RPM:</label>
                        <input type="number" name="rpm" min="0" max="5000" placeholder="1800">
                    </div>
                    <div class="form-group">
                        <label>Battery Voltage (VDC):</label>
                        <input type="number" name="battery_voltage" step="0.1" min="0" placeholder="12.8">
                    </div>
                    <div class="form-group">
                        <label>Lube Oil Pressure (PSI):</label>
                        <input type="number" name="lube_oil_pressure" step="0.1" min="0" placeholder="45.0">
                    </div>
                    <div class="form-group">
                        <label>Fuel Pressure (PSI):</label>
                        <input type="number" name="fuel_pressure" step="0.1" min="0" placeholder="25.0">
                    </div>
                </div>
                
                <h4 style="color: #2c3e50;">Temperature Readings (°F)</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Water Temp In (°F):</label>
                        <input type="number" name="water_temp_in" step="0.1" placeholder="165.0">
                    </div>
                    <div class="form-group">
                        <label>Water Temp Out (°F):</label>
                        <input type="number" name="water_temp_out" step="0.1" placeholder="185.0">
                    </div>
                </div>
                
                <h4 style="color: #2c3e50;">Electrical Output</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Voltage Output (VAC):</label>
                        <input type="number" name="voltage_out" step="0.1" placeholder="480.0">
                    </div>
                    <div class="form-group">
                        <label>Frequency (Hz):</label>
                        <input type="number" name="frequency_hz" step="0.1" placeholder="60.0">
                    </div>
                    <div class="form-group">
                        <label>Amperage (A):</label>
                        <input type="number" name="amperage" step="0.1" placeholder="125.0">
                    </div>
                </div>
                
                <h4 style="color: #2c3e50;">Operating Hours</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Total Generator Hours:</label>
                        <input type="number" name="engine_hours_total" step="0.1" placeholder="<?php echo $generator_info['total_hours'] ?? '8750.2'; ?>">
                    </div>
                    <div class="form-group">
                        <label>Hours Ran Today:</label>
                        <input type="number" name="hours_ran_today" step="0.1" placeholder="12.5">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 15px;">
                    <label>Notes:</label>
                    <textarea name="notes" rows="3" placeholder="Generator load sharing properly, voltage stable..."></textarea>
                </div>
                
                <button type="submit" name="add_generator_reading" class="btn btn-success">Record Generator Reading</button>
            </form>
        </div>

        <!-- Recent Readings -->
        <div class="form-section">
            <h3>📋 Recent Generator Readings</h3>
            <?php if (!empty($recent_readings)): ?>
                <div class="readings-table-container">
                    <table class="readings-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>RPM</th>
                                <th>Voltage Out</th>
                                <th>Frequency</th>
                                <th>Amperage</th>
                                <th>Total Hours</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_readings as $reading): ?>
                                <tr>
                                    <td><?php echo date('M j, Y H:i', strtotime($reading['reading_date'])); ?></td>
                                    <td><?php echo $reading['rpm'] ? number_format($reading['rpm'], 0) : '--'; ?></td>
                                    <td><?php echo $reading['voltage_out'] ? number_format($reading['voltage_out'], 1) . ' VAC' : '--'; ?></td>
                                    <td><?php echo $reading['frequency_hz'] ? number_format($reading['frequency_hz'], 1) . ' Hz' : '--'; ?></td>
                                    <td><?php echo $reading['amperage'] ? number_format($reading['amperage'], 1) . ' A' : '--'; ?></td>
                                    <td><?php echo $reading['engine_hours_total'] ? number_format($reading['engine_hours_total'], 1) : '--'; ?></td>
                                    <td><?php echo htmlspecialchars($reading['notes'] ?: 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; font-style: italic;">No generator readings recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
