<?php
/**
 * Marine Engine Room Dashboard - Professional Version
 * Complete marine engine monitoring with proper engine specifications
 */

require_once 'test_db.php';
require_once 'settings_helper.php';

// Function to get service alerts for an engine
function getServiceAlerts($pdo, $engine_type, $current_hours) {
    $stmt = $pdo->prepare("
        SELECT si.item_name, si.category, st.last_service_hours, st.next_service_hours, 
               ss.interval_hours, ss.is_enabled,
               (? - st.last_service_hours) as hours_since_service,
               CASE 
                   WHEN (? - st.last_service_hours) >= ss.interval_hours THEN 'overdue'
                   WHEN (? - st.last_service_hours) >= (ss.interval_hours - 72) THEN 'due_soon'
                   ELSE 'good'
               END as alert_level
        FROM service_tracking st
        JOIN service_items si ON st.service_item_code = si.item_code  
        JOIN service_settings ss ON st.equipment_type = ss.equipment_type 
                                 AND st.equipment_id = ss.equipment_id 
                                 AND st.service_item_code = ss.service_item_code
        WHERE st.equipment_type = 'engine' 
          AND st.equipment_id = ? 
          AND ss.is_enabled = 1
        ORDER BY 
            CASE alert_level 
                WHEN 'overdue' THEN 1 
                WHEN 'due_soon' THEN 2 
                ELSE 4 
            END,
            hours_since_service DESC
    ");
    $stmt->execute([$current_hours, $current_hours, $current_hours, $engine_type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get gearbox service alerts
function getGearboxServiceAlerts($pdo, $gearbox_type, $current_hours) {
    $stmt = $pdo->prepare("
        SELECT si.item_name, si.category, st.last_service_hours, st.next_service_hours, 
               ss.interval_hours, ss.is_enabled,
               (? - st.last_service_hours) as hours_since_service,
               CASE 
                   WHEN (? - st.last_service_hours) >= ss.interval_hours THEN 'overdue'
                   WHEN (? - st.last_service_hours) >= (ss.interval_hours - 72) THEN 'due_soon'
                   ELSE 'good'
               END as alert_level
        FROM service_tracking st
        JOIN service_items si ON st.service_item_code = si.item_code  
        JOIN service_settings ss ON st.equipment_type = ss.equipment_type 
                                 AND st.equipment_id = ss.equipment_id 
                                 AND st.service_item_code = ss.service_item_code
        WHERE st.equipment_type = 'gearbox' 
          AND st.equipment_id = ? 
          AND ss.is_enabled = 1
        ORDER BY 
            CASE alert_level 
                WHEN 'overdue' THEN 1 
                WHEN 'due_soon' THEN 2 
                ELSE 4 
            END,
            hours_since_service DESC
    ");
    $stmt->execute([$current_hours, $current_hours, $current_hours, $gearbox_type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get generator service alerts
function getGeneratorServiceAlerts($pdo, $generator_type, $current_hours) {
    $stmt = $pdo->prepare("
        SELECT si.item_name, si.category, st.last_service_hours, st.next_service_hours, 
               ss.interval_hours, ss.is_enabled,
               (? - st.last_service_hours) as hours_since_service,
               CASE 
                   WHEN (? - st.last_service_hours) >= ss.interval_hours THEN 'overdue'
                   WHEN (? - st.last_service_hours) >= (ss.interval_hours - 72) THEN 'due_soon'
                   ELSE 'good'
               END as alert_level
        FROM service_tracking st
        JOIN service_items si ON st.service_item_code = si.item_code  
        JOIN service_settings ss ON st.equipment_type = ss.equipment_type 
                                 AND st.equipment_id = ss.equipment_id 
                                 AND st.service_item_code = ss.service_item_code
        WHERE st.equipment_type = 'generator' 
          AND st.equipment_id = ? 
          AND ss.is_enabled = 1
        ORDER BY 
            CASE alert_level 
                WHEN 'overdue' THEN 1 
                WHEN 'due_soon' THEN 2 
                ELSE 4 
            END,
            hours_since_service DESC
    ");
    $stmt->execute([$current_hours, $current_hours, $current_hours, $generator_type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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

// Get fluid inventory stats instead of reading counts
try {
    $fluid_inventory = [];
    $stmt = $pdo->query("SELECT fluid_type, amount FROM fluid_inventory ORDER BY fluid_type");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fluid_inventory[$row['fluid_type']] = round($row['amount'], 1);
    }
    
    // Ensure all fluid types exist with default 0
    $required_fluids = ['fuel', 'lube_oil', 'gear_oil', 'hydraulic_oil'];
    foreach ($required_fluids as $fluid) {
        if (!isset($fluid_inventory[$fluid])) {
            $fluid_inventory[$fluid] = 0;
        }
    }
    
    // Get latest fluid transaction date
    $latest_transaction = $pdo->query("SELECT MAX(created_at) FROM fluid_transactions")->fetchColumn();
    
} catch (Exception $e) {
    // Default values if there's an error
    $fluid_inventory = ['fuel' => 0, 'lube_oil' => 0, 'gear_oil' => 0, 'hydraulic_oil' => 0];
    $latest_transaction = null;
}

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
            <a href="view_logs.php" class="nav-link">View Logs</a>
            <a href="equipment_graphs.php" class="nav-link">📊 Performance Graphs</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <!-- Fluid Inventory Dashboard -->
        <div class="marine-stats-grid">
            <div class="stats-card">
                <h3>🛢️ Fuel</h3>
                <div class="stat-value"><?php echo number_format($fluid_inventory['fuel'], 1); ?></div>
                <div>gallons on board</div>
            </div>
            <div class="stats-card">
                <h3>🛢️ Lube Oil</h3>
                <div class="stat-value"><?php echo number_format($fluid_inventory['lube_oil'], 1); ?></div>
                <div>gallons available</div>
            </div>
            <div class="stats-card">
                <h3>⚙️ Gear Oil</h3>
                <div class="stat-value"><?php echo number_format($fluid_inventory['gear_oil'], 1); ?></div>
                <div>gallons in stock</div>
            </div>
            <div class="stats-card">
                <h3>🔧 Hydraulic Oil</h3>
                <div class="stat-value"><?php echo number_format($fluid_inventory['hydraulic_oil'], 1); ?></div>
                <div>gallons available</div>
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
                    
                    // Get service alerts for this engine
                    $service_alerts = $is_active ? getServiceAlerts($pdo, $engine['engine_type'], $engine['total_hours']) : [];
                    $overdue_count = array_reduce($service_alerts, function($count, $alert) { return $count + ($alert['alert_level'] == 'overdue' ? 1 : 0); }, 0);
                    $due_soon_count = array_reduce($service_alerts, function($count, $alert) { return $count + ($alert['alert_level'] == 'due_soon' ? 1 : 0); }, 0);
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
                        
                        <!-- Service Alerts Section -->
                        <?php if ($is_active && !empty($service_alerts)): ?>
                            <div style="margin: 8px 0; font-size: 11px; border-top: 1px solid #e9ecef; padding-top: 8px;">
                                <div style="font-weight: bold; color: #495057; margin-bottom: 4px;">Service Alerts:</div>
                                <?php if ($overdue_count > 0): ?>
                                    <div style="color: #dc3545; font-weight: bold;">🔴 <?php echo $overdue_count; ?> overdue</div>
                                <?php endif; ?>
                                <?php if ($due_soon_count > 0): ?>
                                    <div style="color: #ffc107;">🟡 <?php echo $due_soon_count; ?> due soon</div>
                                <?php endif; ?>
                                <?php 
                                $top_alerts = array_slice(array_filter($service_alerts, function($alert) { 
                                    return in_array($alert['alert_level'], ['overdue', 'due_soon']); 
                                }), 0, 2);
                                foreach ($top_alerts as $alert): ?>
                                    <div style="font-size: 10px; color: #6c757d; margin-top: 2px;">
                                        • <?php echo htmlspecialchars($alert['item_name']); ?> 
                                        (<?php echo number_format($alert['hours_since_service']); ?>/<?php echo number_format($alert['next_service_hours']); ?>hrs)
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin: 10px 0; font-size: 12px; color: #7f8c8d;">
                            <div>Total: <?php echo number_format($engine['total_hours'], 1); ?> hrs</div>
                            <div>Progress: <?php echo $overhaul_pct; ?>% to overhaul</div>
                        </div>
                        <?php if ($is_active): ?>
                            <div style="display: flex; gap: 5px;">
                                <a href="manage_engine.php?type=<?php echo $engine['engine_type']; ?>" 
                                   style="flex: 1; background: <?php echo $overhaul_pct > 80 ? '#dc3545' : ($overhaul_pct > 60 ? '#ffc107' : '#28a745'); ?>; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; text-align: center; font-size: 12px;">
                                    📊 Readings
                                </a>
                                <a href="service_engine.php?type=<?php echo $engine['engine_type']; ?>" 
                                   style="flex: 1; background: <?php echo ($overdue_count > 0) ? '#dc3545' : (($due_soon_count > 0) ? '#ffc107' : '#6c757d'); ?>; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; text-align: center; font-size: 12px;">
                                    🔧 Service<?php echo ($overdue_count > 0 || $due_soon_count > 0) ? ' ⚠️' : ''; ?>
                                </a>
                            </div>
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
                    
                    // Get gearbox service alerts
                    $gearbox_service_alerts = $is_active ? getGearboxServiceAlerts($pdo, $gearbox['gearbox_type'], $gearbox['total_hours']) : [];
                    $gb_overdue_count = array_reduce($gearbox_service_alerts, function($count, $alert) { return $count + ($alert['alert_level'] == 'overdue' ? 1 : 0); }, 0);
                    $gb_due_soon_count = array_reduce($gearbox_service_alerts, function($count, $alert) { return $count + ($alert['alert_level'] == 'due_soon' ? 1 : 0); }, 0);
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
                        
                        <!-- Gearbox Service Alerts Section -->
                        <?php if ($is_active && !empty($gearbox_service_alerts)): ?>
                            <div style="margin: 8px 0; font-size: 11px; border-top: 1px solid #e9ecef; padding-top: 8px;">
                                <div style="font-weight: bold; color: #495057; margin-bottom: 4px;">Service Alerts:</div>
                                <?php if ($gb_overdue_count > 0): ?>
                                    <div style="color: #dc3545; font-weight: bold;">🔴 <?php echo $gb_overdue_count; ?> overdue</div>
                                <?php endif; ?>
                                <?php if ($gb_due_soon_count > 0): ?>
                                    <div style="color: #ffc107;">🟡 <?php echo $gb_due_soon_count; ?> due soon</div>
                                <?php endif; ?>
                                <?php 
                                $gb_top_alerts = array_slice(array_filter($gearbox_service_alerts, function($alert) { 
                                    return in_array($alert['alert_level'], ['overdue', 'due_soon']); 
                                }), 0, 2);
                                foreach ($gb_top_alerts as $alert): ?>
                                    <div style="font-size: 10px; color: #6c757d; margin-top: 2px;">
                                        • <?php echo htmlspecialchars($alert['item_name']); ?> 
                                        (<?php echo number_format($alert['hours_since_service']); ?>/<?php echo number_format($alert['next_service_hours']); ?>hrs)
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin: 10px 0; font-size: 12px; color: #7f8c8d;">
                            <div>Total: <?php echo number_format($gearbox['total_hours'], 1); ?> hrs</div>
                            <div>Progress: <?php echo $overhaul_pct; ?>% to overhaul</div>
                        </div>
                        <?php if ($is_active): ?>
                            <div style="display: flex; gap: 5px;">
                                <a href="manage_gearbox.php?type=<?php echo $gearbox['gearbox_type']; ?>" 
                                   style="flex: 1; background: <?php echo $overhaul_pct > 80 ? '#dc3545' : ($overhaul_pct > 60 ? '#ffc107' : '#28a745'); ?>; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; text-align: center; font-size: 12px;">
                                    📊 Readings
                                </a>
                                <a href="service_gearbox.php?type=<?php echo $gearbox['gearbox_type']; ?>" 
                                   style="flex: 1; background: <?php echo ($gb_overdue_count > 0) ? '#dc3545' : (($gb_due_soon_count > 0) ? '#ffc107' : '#6c757d'); ?>; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; text-align: center; font-size: 12px;">
                                    🔧 Service<?php echo ($gb_overdue_count > 0 || $gb_due_soon_count > 0) ? ' ⚠️' : ''; ?>
                                </a>
                            </div>
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
                    
                    // Get service alerts
                    $gen_service_alerts = $is_active ? getGeneratorServiceAlerts($pdo, $generator['generator_type'], $generator['total_hours']) : [];
                    $gen_overdue_count = count(array_filter($gen_service_alerts, function($a) { return $a['alert_level'] == 'overdue'; }));
                    $gen_due_soon_count = count(array_filter($gen_service_alerts, function($a) { return $a['alert_level'] == 'due_soon'; }));
                    ?>
                    <div class="fluid-dashboard-card <?php echo $overhaul_pct > 80 ? 'primary-fluid' : ($overhaul_pct > 60 ? 'secondary-fluid' : 'tertiary-fluid'); ?> <?php echo $disabled_class; ?>">
                        <div class="fluid-header">
                            <span class="fluid-icon">🔌</span>
                            <h4><?php echo $generator_name; ?></h4>
                            <?php if ($gen_overdue_count > 0 || $gen_due_soon_count > 0): ?>
                                <div style="position: absolute; top: 8px; right: 8px;">
                                    <?php if ($gen_overdue_count > 0): ?>
                                        <span style="background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; margin-left: 2px;">
                                            <?php echo $gen_overdue_count; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($gen_due_soon_count > 0): ?>
                                        <span style="background: #ffc107; color: white; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; margin-left: 2px;">
                                            <?php echo $gen_due_soon_count; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
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
                        
                        <?php if (!empty($gen_service_alerts) && ($gen_overdue_count > 0 || $gen_due_soon_count > 0)): ?>
                            <div style="margin: 8px 0; padding: 6px; background: rgba(220,53,69,0.1); border-radius: 4px; font-size: 10px;">
                                <div style="color: #dc3545; font-weight: bold; margin-bottom: 3px;">Service Alerts:</div>
                                <?php 
                                $gen_top_alerts = array_slice(array_filter($gen_service_alerts, function($alert) {
                                    return in_array($alert['alert_level'], ['overdue', 'due_soon']);
                                }), 0, 2);
                                foreach ($gen_top_alerts as $alert): ?>
                                    <div style="font-size: 10px; color: #6c757d; margin-top: 2px;">
                                        • <?php echo htmlspecialchars($alert['item_name']); ?> 
                                        (<?php echo number_format($alert['hours_since_service']); ?>/<?php echo number_format($alert['next_service_hours']); ?>hrs)
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin: 10px 0; font-size: 12px; color: #7f8c8d;">
                            <div>Total: <?php echo number_format($generator['total_hours'], 1); ?> hrs</div>
                            <div>Progress: <?php echo $overhaul_pct; ?>% to overhaul</div>
                        </div>
                        <?php if ($is_active): ?>
                            <div style="display: flex; gap: 5px;">
                                <a href="manage_generator.php?type=<?php echo $generator['generator_type']; ?>" 
                                   style="flex: 1; background: <?php echo $overhaul_pct > 80 ? '#dc3545' : ($overhaul_pct > 60 ? '#ffc107' : '#28a745'); ?>; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; text-align: center; font-size: 12px;">
                                    📊 Readings
                                </a>
                                <a href="service_generator.php?type=<?php echo $generator['generator_type']; ?>" 
                                   style="flex: 1; background: <?php echo ($gen_overdue_count > 0) ? '#dc3545' : (($gen_due_soon_count > 0) ? '#ffc107' : '#6c757d'); ?>; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; text-align: center; font-size: 12px;">
                                    🔧 Service<?php echo ($gen_overdue_count > 0 || $gen_due_soon_count > 0) ? ' ⚠️' : ''; ?>
                                </a>
                            </div>
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
