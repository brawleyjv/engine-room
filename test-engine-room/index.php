<?php
/**
 * Marine Engine Room Dashboard - Professional Version
 * Complete marine engine monitoring with proper engine specifications
 */

require_once 'test_db.php';
require_once 'settings_helper.php';

// Initialize database connection
$pdo = getTestDatabase();

// Get vessel settings
$vessel_settings = getVesselSettings($pdo);

// Handle form submission
$message = '';
if ($_POST) {
    try {
        
        if (isset($_POST['add_main_engine'])) {
            $stmt = $pdo->prepare("INSERT INTO engine_readings 
                (vessel_id, engine_type, rpm, fuel_pressure, oil_pressure, water_temp_in, water_temp_out, 
                oil_temp_in, oil_temp_out, turbo_oil_pressure, governor_air_pressure, aftercooler_water_pressure,
                lube_oil_filter_pressure_in, lube_oil_filter_pressure_out, aftercooler_water_temp_out,
                air_box_pressure, crankcase_vacuum, ship_air_pressure, engine_hours_total, hours_ran_today, notes, created_by) 
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Marine Engineer')");
            
            $stmt->execute([
                $_POST['engine_type'],
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
            
            $message = "Main engine reading added successfully!";
        }
        
        if (isset($_POST['add_gearbox'])) {
            $stmt = $pdo->prepare("INSERT INTO gearbox_readings 
                (vessel_id, gearbox_type, clutch_pressure, oil_pressure, oil_temperature, cooling_water_temp, notes, created_by) 
                VALUES (1, ?, ?, ?, ?, ?, ?, 'Marine Engineer')");
            
            $stmt->execute([
                $_POST['gearbox_type'],
                $_POST['clutch_pressure'] ?: null,
                $_POST['oil_pressure'] ?: null,
                $_POST['oil_temperature'] ?: null,
                $_POST['cooling_water_temp'] ?: null,
                $_POST['notes']
            ]);
            
            $message = "Gearbox reading added successfully!";
        }
        
        if (isset($_POST['add_generator'])) {
            $stmt = $pdo->prepare("INSERT INTO generator_readings 
                (vessel_id, generator_type, rpm, battery_voltage, water_temp_in, water_temp_out, lube_oil_pressure, 
                fuel_pressure, voltage_out, frequency_hz, amperage, engine_hours_total, hours_ran_today, notes, created_by) 
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Marine Engineer')");
            
            $stmt->execute([
                $_POST['generator_type'],
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
            
            $message = "Generator reading added successfully!";
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Get fluid levels for dashboard
$fluid_levels = $pdo->query("SELECT fluid_type, amount FROM fluid_inventory WHERE vessel_id = 1")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get engine hours for dashboard
$engine_hours = $pdo->query("SELECT * FROM engine_hours ORDER BY 
    CASE engine_type 
        WHEN 'port_main' THEN 1 
        WHEN 'center_main' THEN 2 
        WHEN 'starboard_main' THEN 3 
        ELSE 4 
    END")->fetchAll(PDO::FETCH_ASSOC);

// Get gearbox hours for dashboard
$gearbox_hours = $pdo->query("SELECT * FROM gearbox_hours ORDER BY 
    CASE gearbox_type 
        WHEN 'port_main' THEN 1 
        WHEN 'center_main' THEN 2 
        WHEN 'starboard_main' THEN 3 
        ELSE 4 
    END")->fetchAll(PDO::FETCH_ASSOC);

// Get generator hours for dashboard
$generator_hours = $pdo->query("SELECT * FROM generator_hours ORDER BY 
    CASE generator_type 
        WHEN 'port_gen' THEN 1 
        WHEN 'center_gen' THEN 2 
        WHEN 'starboard_gen' THEN 3 
        ELSE 4 
    END")->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$total_engine_readings = $pdo->query("SELECT COUNT(*) FROM engine_readings")->fetchColumn();
$total_generator_readings = $pdo->query("SELECT COUNT(*) FROM generator_readings")->fetchColumn();
$total_gearbox_readings = $pdo->query("SELECT COUNT(*) FROM gearbox_readings")->fetchColumn();
$latest_reading = $pdo->query("SELECT MAX(reading_date) FROM engine_readings")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marine Engine Room - Professional Demo</title>
    <link rel="stylesheet" href="test_styles.css">
</head>
<body>
    <div class="header">
        <h1>🚢 Marine Engine Room Management - TEST DEMO</h1>
        <p>Professional marine engine monitoring with detailed readings for main engines, gearboxes, and generators</p>
        <div style="margin-top: 15px;">
            <a href="settings.php" style="background: #f39c12; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; font-weight: bold;">
                ⚙️ Vessel Settings
            </a>
        </div>
    </div>
    
    <div class="container">
        <div class="nav-links" style="margin-bottom: 20px;">
            <a href="#main-engines" class="nav-link">Main Engines</a>
            <a href="#gearboxes" class="nav-link">Gearboxes</a>
            <a href="#generators" class="nav-link">Generators</a>
            <a href="#fluids" class="nav-link">Fluid Management</a>
            <a href="test_reports.php" class="nav-link">View Reports</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <!-- Dashboard Stats -->
        <div class="marine-stats-grid">
            <div class="stats-card">
                <h3>Main Engine Readings</h3>
                <div class="stat-value"><?php echo $total_engine_readings; ?></div>
                <div>Port • Center • Starboard</div>
            </div>
            <div class="stats-card">
                <h3>Generator Readings</h3>
                <div class="stat-value"><?php echo $total_generator_readings; ?></div>
                <div>Port • Starboard</div>
            </div>
            <div class="stats-card">
                <h3>Gearbox Readings</h3>
                <div class="stat-value"><?php echo $total_gearbox_readings; ?></div>
                <div>Transmission monitoring</div>
            </div>
            <div class="stats-card">
                <h3>Last Reading</h3>
                <div class="stat-value"><?php echo $latest_reading ? date('M j', strtotime($latest_reading)) : '--'; ?></div>
                <div>Most recent entry</div>
            </div>
        </div>
        
        <!-- Overhaul Settings Info -->
        <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff; margin: 20px 0;">
            <p style="margin: 0; font-size: 14px; color: #004085;">
                <strong>⚙️ Overhaul Progress:</strong> Based on configurable maintenance intervals. 
                Current settings: Engines <?php echo $vessel_settings['engine_overhaul_interval'] ?? 8000; ?>hrs, 
                Gearboxes <?php echo $vessel_settings['gearbox_overhaul_interval'] ?? 6000; ?>hrs, 
                Generators <?php echo $vessel_settings['generator_overhaul_interval'] ?? 4000; ?>hrs. 
                <a href="settings.php" style="color: #007bff;">Adjust intervals →</a>
            </p>
        </div>
        
        <!-- Main Engines Dashboard -->
        <div class="form-section" id="main-engines">
            <h3>⚙️ Main Engine Status Dashboard</h3>
            
            <div class="fluid-dashboard-row">
                <?php foreach ($engine_hours as $engine): ?>
                    <?php 
                    // Get overhaul interval from settings
                    $overhaul_interval = floatval($vessel_settings['engine_overhaul_interval'] ?? 8000);
                    $overhaul_pct = round(($engine['hours_since_overhaul'] / $overhaul_interval) * 100, 1);
                    $engine_name = ucwords(str_replace('_', ' ', $engine['engine_type']));
                    $is_active = isEngineActive($engine['engine_type'], $vessel_settings);
                    $disabled_class = $is_active ? '' : 'equipment-disabled';
                    ?>
                    <div class="fluid-dashboard-card <?php echo $overhaul_pct > 80 ? 'primary-fluid' : ($overhaul_pct > 60 ? 'secondary-fluid' : 'tertiary-fluid'); ?> <?php echo $disabled_class; ?>">
                        <div class="fluid-header">
                            <span class="fluid-icon">🔧</span>
                            <h4><?php echo $engine_name; ?></h4>
                        </div>
                        <div class="fluid-level-display">
                            <div class="fluid-amount-medium"><?php echo number_format($engine['hours_since_overhaul'], 1); ?></div>
                            <div class="fluid-unit">hours since overhaul</div>
                        </div>
                        <div class="fluid-status">
                            <?php if ($overhaul_pct >= 90): ?>
                                <span class="status-critical">🔴 OVERHAUL DUE</span>
                            <?php elseif ($overhaul_pct >= 75): ?>
                                <span class="status-warning">🟡 PLAN OVERHAUL</span>
                            <?php else: ?>
                                <span class="status-good">🟢 GOOD</span>
                            <?php endif; ?>
                        </div>
                        <div style="margin: 10px 0; font-size: 12px; color: #7f8c8d;">
                            <div>Total: <?php echo number_format($engine['total_hours'], 1); ?> hrs</div>
                            <div>Progress: <?php echo $overhaul_pct; ?>% to overhaul</div>
                        </div>
                        <?php if ($is_active): ?>
                            <a href="manage_engine.php?type=<?php echo $engine['engine_type']; ?>" class="fluid-manage-btn <?php echo $overhaul_pct > 80 ? 'primary' : ($overhaul_pct > 60 ? 'secondary' : 'tertiary'); ?>">Manage Engine</a>
                        <?php else: ?>
                            <span class="fluid-manage-btn" style="background: #bdc3c7; cursor: not-allowed;">Inactive</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Gearbox Dashboard -->
        <div class="form-section" id="gearboxes">
            <h3>⚙️ Gearbox Status Dashboard</h3>
            
            <div class="fluid-dashboard-row">
                <?php foreach ($gearbox_hours as $gearbox): ?>
                    <?php 
                    // Get overhaul interval from settings
                    $overhaul_interval = floatval($vessel_settings['gearbox_overhaul_interval'] ?? 6000);
                    $overhaul_pct = round(($gearbox['hours_since_overhaul'] / $overhaul_interval) * 100, 1);
                    $gearbox_name = ucwords(str_replace('_', ' ', $gearbox['gearbox_type'])) . ' Gearbox';
                    $is_active = isGearboxActive($gearbox['gearbox_type'], $vessel_settings);
                    $disabled_class = $is_active ? '' : 'equipment-disabled';
                    ?>
                    <div class="fluid-dashboard-card <?php echo $overhaul_pct > 80 ? 'primary-fluid' : ($overhaul_pct > 60 ? 'secondary-fluid' : 'tertiary-fluid'); ?> <?php echo $disabled_class; ?>">
                        <div class="fluid-header">
                            <span class="fluid-icon">⚙️</span>
                            <h4><?php echo $gearbox_name; ?></h4>
                        </div>
                        <div class="fluid-level-display">
                            <div class="fluid-amount-medium"><?php echo number_format($gearbox['hours_since_overhaul'], 1); ?></div>
                            <div class="fluid-unit">hours since overhaul</div>
                        </div>
                        <div class="fluid-status">
                            <?php if ($overhaul_pct >= 90): ?>
                                <span class="status-critical">🔴 OVERHAUL DUE</span>
                            <?php elseif ($overhaul_pct >= 75): ?>
                                <span class="status-warning">🟡 PLAN OVERHAUL</span>
                            <?php else: ?>
                                <span class="status-good">🟢 GOOD</span>
                            <?php endif; ?>
                        </div>
                        <div style="margin: 10px 0; font-size: 12px; color: #7f8c8d;">
                            <div>Total: <?php echo number_format($gearbox['total_hours'], 1); ?> hrs</div>
                            <div>Progress: <?php echo $overhaul_pct; ?>% to overhaul</div>
                        </div>
                        <?php if ($is_active): ?>
                            <a href="manage_gearbox.php?type=<?php echo $gearbox['gearbox_type']; ?>" class="fluid-manage-btn <?php echo $overhaul_pct > 80 ? 'primary' : ($overhaul_pct > 60 ? 'secondary' : 'tertiary'); ?>">Manage Gearbox</a>
                        <?php else: ?>
                            <span class="fluid-manage-btn" style="background: #bdc3c7; cursor: not-allowed;">Inactive</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Generator Status Dashboard -->
        <div class="form-section" id="generators">
            <h3>🔌 Generator Status Dashboard</h3>
            
            <div class="fluid-dashboard-row">
                <?php foreach ($generator_hours as $generator): ?>
                    <?php 
                    // Get overhaul interval from settings
                    $overhaul_interval = floatval($vessel_settings['generator_overhaul_interval'] ?? 4000);
                    $overhaul_pct = round(($generator['hours_since_overhaul'] / $overhaul_interval) * 100, 1);
                    $generator_name = ucwords(str_replace(['_gen', '_'], [' Generator', ' '], $generator['generator_type']));
                    $is_active = isGeneratorActive($generator['generator_type'], $vessel_settings);
                    $disabled_class = $is_active ? '' : 'equipment-disabled';
                    ?>
                    <div class="fluid-dashboard-card <?php echo $overhaul_pct > 80 ? 'primary-fluid' : ($overhaul_pct > 60 ? 'secondary-fluid' : 'tertiary-fluid'); ?> <?php echo $disabled_class; ?>">
                        <div class="fluid-header">
                            <span class="fluid-icon">🔌</span>
                            <h4><?php echo $generator_name; ?></h4>
                        </div>
                        <div class="fluid-level-display">
                            <div class="fluid-amount-medium"><?php echo number_format($generator['hours_since_overhaul'], 1); ?></div>
                            <div class="fluid-unit">hours since overhaul</div>
                        </div>
                        <div class="fluid-status">
                            <?php if ($overhaul_pct >= 90): ?>
                                <span class="status-critical">🔴 OVERHAUL DUE</span>
                            <?php elseif ($overhaul_pct >= 75): ?>
                                <span class="status-warning">🟡 PLAN OVERHAUL</span>
                            <?php else: ?>
                                <span class="status-good">🟢 GOOD</span>
                            <?php endif; ?>
                        </div>
                        <div style="margin: 10px 0; font-size: 12px; color: #7f8c8d;">
                            <div>Total: <?php echo number_format($generator['total_hours'], 1); ?> hrs</div>
                            <div>Progress: <?php echo $overhaul_pct; ?>% to overhaul</div>
                        </div>
                        <?php if ($is_active): ?>
                            <a href="manage_generator.php?type=<?php echo $generator['generator_type']; ?>" class="fluid-manage-btn <?php echo $overhaul_pct > 80 ? 'primary' : ($overhaul_pct > 60 ? 'secondary' : 'tertiary'); ?>">Manage Generator</a>
                        <?php else: ?>
                            <span class="fluid-manage-btn" style="background: #bdc3c7; cursor: not-allowed;">Inactive</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Fluid Inventory Dashboard -->
        <div class="form-section" id="fluids">
            <h3>🛢️ Fluid Inventory Dashboard</h3>
            
            <!-- Primary Fluids Row -->
            <div class="fluid-dashboard-row">
                <div class="fluid-dashboard-card primary-fluid">
                    <div class="fluid-header">
                        <span class="fluid-icon">⛽</span>
                        <h4>Fuel</h4>
                    </div>
                    <div class="fluid-level-display">
                        <div class="fluid-amount-large"><?php echo number_format($fluid_levels['fuel'] ?? 0, 1); ?></div>
                        <div class="fluid-unit">gallons</div>
                    </div>
                    <div class="fluid-status">
                        <?php 
                        $fuel_level = $fluid_levels['fuel'] ?? 0;
                        if ($fuel_level < 1000): ?>
                            <span class="status-critical">🔴 CRITICAL</span>
                        <?php elseif ($fuel_level < 5000): ?>
                            <span class="status-warning">🟡 LOW</span>
                        <?php else: ?>
                            <span class="status-good">🟢 GOOD</span>
                        <?php endif; ?>
                    </div>
                    <a href="manage_fuel.php" class="fluid-manage-btn primary">Manage Fuel</a>
                </div>
                
                <div class="fluid-dashboard-card secondary-fluid">
                    <div class="fluid-header">
                        <span class="fluid-icon">🔧</span>
                        <h4>Lube Oil</h4>
                    </div>
                    <div class="fluid-level-display">
                        <div class="fluid-amount-large"><?php echo number_format($fluid_levels['lube_oil'] ?? 0, 1); ?></div>
                        <div class="fluid-unit">gallons</div>
                    </div>
                    <div class="fluid-status">
                        <?php 
                        $lube_level = $fluid_levels['lube_oil'] ?? 0;
                        if ($lube_level < 100): ?>
                            <span class="status-warning">🟡 LOW</span>
                        <?php else: ?>
                            <span class="status-good">🟢 GOOD</span>
                        <?php endif; ?>
                    </div>
                    <a href="manage_lube_oil.php" class="fluid-manage-btn secondary">Manage Lube Oil</a>
                </div>
            </div>
            
            <!-- Secondary Fluids Row -->
            <div class="fluid-dashboard-row">
                <div class="fluid-dashboard-card tertiary-fluid">
                    <div class="fluid-header">
                        <span class="fluid-icon">⚙️</span>
                        <h4>Gear Oil</h4>
                    </div>
                    <div class="fluid-level-display">
                        <div class="fluid-amount-medium"><?php echo number_format($fluid_levels['gear_oil'] ?? 0, 1); ?></div>
                        <div class="fluid-unit">gallons</div>
                    </div>
                    <div class="fluid-status">
                        <?php 
                        $gear_level = $fluid_levels['gear_oil'] ?? 0;
                        if ($gear_level < 20): ?>
                            <span class="status-warning">🟡 LOW</span>
                        <?php else: ?>
                            <span class="status-good">🟢 GOOD</span>
                        <?php endif; ?>
                    </div>
                    <a href="manage_gear_oil.php" class="fluid-manage-btn tertiary">Manage Gear Oil</a>
                </div>
                
                <div class="fluid-dashboard-card tertiary-fluid">
                    <div class="fluid-header">
                        <span class="fluid-icon">🛠️</span>
                        <h4>Hydraulic Oil</h4>
                    </div>
                    <div class="fluid-level-display">
                        <div class="fluid-amount-medium"><?php echo number_format($fluid_levels['hydraulic_oil'] ?? 0, 1); ?></div>
                        <div class="fluid-unit">gallons</div>
                    </div>
                    <div class="fluid-status">
                        <?php 
                        $hydraulic_level = $fluid_levels['hydraulic_oil'] ?? 0;
                        if ($hydraulic_level < 50): ?>
                            <span class="status-warning">🟡 LOW</span>
                        <?php else: ?>
                            <span class="status-good">🟢 GOOD</span>
                        <?php endif; ?>
                    </div>
                    <a href="manage_hydraulic_oil.php" class="fluid-manage-btn tertiary">Manage Hydraulic Oil</a>
                </div>
                
                <div class="fluid-summary-card">
                    <div class="summary-header">
                        <span class="fluid-icon">📊</span>
                        <h4>Fluid Status</h4>
                    </div>
                    <div class="summary-content">
                        <div class="summary-item">
                            <span class="summary-label">Last Updated:</span>
                            <span class="summary-value">Today</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Total Value:</span>
                            <span class="summary-value">$<?php echo number_format(($fuel_level * 3.2) + (($fluid_levels['lube_oil'] ?? 0) * 8.5) + (($fluid_levels['gear_oil'] ?? 0) * 12.0) + (($fluid_levels['hydraulic_oil'] ?? 0) * 15.0), 0); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Alerts:</span>
                            <?php 
                            $alerts = 0;
                            if ($fuel_level < 5000) $alerts++;
                            if (($fluid_levels['lube_oil'] ?? 0) < 100) $alerts++;
                            if (($fluid_levels['gear_oil'] ?? 0) < 20) $alerts++;
                            if (($fluid_levels['hydraulic_oil'] ?? 0) < 50) $alerts++;
                            ?>
                            <span class="summary-value alert-count"><?php echo $alerts; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #3498db;">
                <p><strong>💡 Fluid Management System:</strong></p>
                <ul style="margin: 10px 0;">
                    <li><strong>Fuel:</strong> Daily consumption tracking - most critical</li>
                    <li><strong>Lube Oil:</strong> Periodic changes and top-offs</li>
                    <li><strong>Gear Oil:</strong> Rarely used - typically stable levels</li>
                    <li><strong>Hydraulic Oil:</strong> Occasional system maintenance</li>
                </ul>
                <p>Click any "Manage" button to record usage, receipts, or view transaction history for that specific fluid.</p>
            </div>
        </div>
            </form>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #e8f4f8; border-radius: 8px;">
            <h4>🚢 Professional Marine Engine Room Monitoring - TEST DEMO</h4>
            <p>This demonstrates comprehensive marine engine room monitoring with:</p>
            <ul>
                <li><strong>Main Engines:</strong> Port, Center, Starboard with full parameter monitoring</li>
                <li><strong>Gearboxes:</strong> Clutch pressure, oil pressure/temp, cooling water</li>
                <li><strong>Generators:</strong> Engine and electrical side monitoring with load data</li>
                <li><strong>Fluid Management:</strong> Fuel, hydraulic oil, gear oil, and lube oil tracking</li>
                <li><strong>Engine Hours:</strong> Total hours and daily run time tracking</li>
                <li><strong>Professional Parameters:</strong> All the readings you specified for real marine operations</li>
            </ul>
            <p><strong>Try adding readings for different engines and check the reports page!</strong></p>
        </div>
    </div>
</body>
</html>
