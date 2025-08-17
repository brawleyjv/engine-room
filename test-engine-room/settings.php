<?php
/**
 * Vessel Configuration Settings - Marine Engine Room
 * Configure active engines and gearboxes for different vessel types
 */

require_once 'test_db.php';
require_once 'log_helper.php';

$message = '';
$error = '';

// Handle settings update
if ($_POST && isset($_POST['update_settings'])) {
    try {
        $pdo = getTestDatabase();
        
        // Create settings table if it doesn't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS vessel_settings (
            id INTEGER PRIMARY KEY,
            setting_key TEXT UNIQUE,
            setting_value TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Engine settings
        $engines = ['port_main', 'center_main', 'starboard_main'];
        foreach ($engines as $engine) {
            $active = isset($_POST['engine_' . $engine]) ? '1' : '0';
            $pdo->prepare("INSERT OR REPLACE INTO vessel_settings (setting_key, setting_value, updated_at) 
                          VALUES (?, ?, datetime('now'))")
                ->execute(['engine_' . $engine . '_active', $active]);
        }
        
        // Gearbox settings
        $gearboxes = ['port_main', 'center_main', 'starboard_main'];
        foreach ($gearboxes as $gearbox) {
            $active = isset($_POST['gearbox_' . $gearbox]) ? '1' : '0';
            $pdo->prepare("INSERT OR REPLACE INTO vessel_settings (setting_key, setting_value, updated_at) 
                          VALUES (?, ?, datetime('now'))")
                ->execute(['gearbox_' . $gearbox . '_active', $active]);
        }
        
        // Generator settings
        $generators = ['port_gen', 'center_gen', 'starboard_gen'];
        foreach ($generators as $generator) {
            $active = isset($_POST['generator_' . $generator]) ? '1' : '0';
            $pdo->prepare("INSERT OR REPLACE INTO vessel_settings (setting_key, setting_value, updated_at) 
                          VALUES (?, ?, datetime('now'))")
                ->execute(['generator_' . $generator . '_active', $active]);
        }
        
        // Vessel type setting
        if (!empty($_POST['vessel_type'])) {
            $pdo->prepare("INSERT OR REPLACE INTO vessel_settings (setting_key, setting_value, updated_at) 
                          VALUES ('vessel_type', ?, datetime('now'))")
                ->execute([$_POST['vessel_type']]);
        }
        
        // Universal service items settings - apply to all active engines
        if (isset($_POST['universal_service_settings'])) {
            // Get list of active engines
            $active_engines = [];
            foreach (['port_main', 'center_main', 'starboard_main'] as $engine) {
                $engine_active = isset($_POST['engine_' . $engine]) ? '1' : '0';
                if ($engine_active == '1') {
                    $active_engines[] = $engine;
                }
            }
            
            // Apply universal settings to all active engines
            foreach ($_POST['universal_service_settings'] as $service_code => $service_data) {
                $is_enabled = isset($service_data['enabled']) ? 1 : 0;
                $interval = isset($service_data['interval']) && is_numeric($service_data['interval']) && $service_data['interval'] > 0 
                          ? $service_data['interval'] 
                          : null;
                
                if ($interval !== null) {
                    foreach ($active_engines as $engine_id) {
                        $pdo->prepare("INSERT OR REPLACE INTO service_settings 
                                      (equipment_type, equipment_id, service_item_code, is_enabled, interval_hours, updated_at) 
                                      VALUES ('engine', ?, ?, ?, ?, datetime('now'))")
                            ->execute([$engine_id, $service_code, $is_enabled, $interval]);
                        
                        // Update or create tracking record
                        $pdo->prepare("INSERT OR REPLACE INTO service_tracking 
                                      (equipment_type, equipment_id, service_item_code, last_service_hours, next_service_hours, updated_at)
                                      VALUES ('engine', ?, ?, COALESCE((SELECT last_service_hours FROM service_tracking 
                                                                       WHERE equipment_type='engine' AND equipment_id=? AND service_item_code=?), 0), 
                                              COALESCE((SELECT last_service_hours FROM service_tracking 
                                                       WHERE equipment_type='engine' AND equipment_id=? AND service_item_code=?), 0) + ?, datetime('now'))")
                            ->execute([$engine_id, $service_code, $engine_id, $service_code, $engine_id, $service_code, $interval]);
                    }
                }
            }
        }
        
        // Universal gearbox service settings - apply to all active gearboxes
        if (isset($_POST['universal_gearbox_settings'])) {
            // Get list of active gearboxes
            $active_gearboxes = [];
            foreach (['port_main', 'center_main', 'starboard_main'] as $gearbox) {
                $gearbox_active = isset($_POST['gearbox_' . $gearbox]) ? '1' : '0';
                if ($gearbox_active == '1') {
                    $active_gearboxes[] = $gearbox;
                }
            }
            
            // Apply universal settings to all active gearboxes
            foreach ($_POST['universal_gearbox_settings'] as $service_code => $service_data) {
                $is_enabled = isset($service_data['enabled']) ? 1 : 0;
                $interval = isset($service_data['interval']) && is_numeric($service_data['interval']) && $service_data['interval'] > 0 
                          ? $service_data['interval'] 
                          : null;
                
                if ($interval !== null) {
                    foreach ($active_gearboxes as $gearbox_id) {
                        $pdo->prepare("INSERT OR REPLACE INTO service_settings 
                                      (equipment_type, equipment_id, service_item_code, is_enabled, interval_hours, updated_at) 
                                      VALUES ('gearbox', ?, ?, ?, ?, datetime('now'))")
                            ->execute([$gearbox_id, $service_code, $is_enabled, $interval]);
                        
                        // Update or create tracking record
                        $pdo->prepare("INSERT OR REPLACE INTO service_tracking 
                                      (equipment_type, equipment_id, service_item_code, last_service_hours, next_service_hours, updated_at)
                                      VALUES ('gearbox', ?, ?, COALESCE((SELECT last_service_hours FROM service_tracking 
                                                                        WHERE equipment_type='gearbox' AND equipment_id=? AND service_item_code=?), 0), 
                                              COALESCE((SELECT last_service_hours FROM service_tracking 
                                                       WHERE equipment_type='gearbox' AND equipment_id=? AND service_item_code=?), 0) + ?, datetime('now'))")
                            ->execute([$gearbox_id, $service_code, $gearbox_id, $service_code, $gearbox_id, $service_code, $interval]);
                    }
                }
            }
        }
        
        // Universal generator service settings - apply to all active generators
        if (isset($_POST['universal_generator_settings'])) {
            // Get list of active generators
            $active_generators = [];
            foreach (['port_gen', 'center_gen', 'starboard_gen'] as $generator) {
                $generator_active = isset($_POST['generator_' . $generator]) ? '1' : '0';
                if ($generator_active == '1') {
                    $active_generators[] = $generator;
                }
            }
            
            // Apply universal settings to all active generators
            foreach ($_POST['universal_generator_settings'] as $service_code => $service_data) {
                $is_enabled = isset($service_data['enabled']) ? 1 : 0;
                $interval = isset($service_data['interval']) && is_numeric($service_data['interval']) && $service_data['interval'] > 0 
                          ? $service_data['interval'] 
                          : null;
                
                if ($interval !== null) {
                    foreach ($active_generators as $generator_id) {
                        $pdo->prepare("INSERT OR REPLACE INTO service_settings 
                                      (equipment_type, equipment_id, service_item_code, is_enabled, interval_hours, updated_at) 
                                      VALUES ('generator', ?, ?, ?, ?, datetime('now'))")
                            ->execute([$generator_id, $service_code, $is_enabled, $interval]);
                        
                        // Update or create tracking record
                        $pdo->prepare("INSERT OR REPLACE INTO service_tracking 
                                      (equipment_type, equipment_id, service_item_code, last_service_hours, next_service_hours, updated_at)
                                      VALUES ('generator', ?, ?, COALESCE((SELECT last_service_hours FROM service_tracking 
                                                                        WHERE equipment_type='generator' AND equipment_id=? AND service_item_code=?), 0), 
                                              COALESCE((SELECT last_service_hours FROM service_tracking 
                                                       WHERE equipment_type='generator' AND equipment_id=? AND service_item_code=?), 0) + ?, datetime('now'))")
                            ->execute([$generator_id, $service_code, $generator_id, $service_code, $generator_id, $service_code, $interval]);
                    }
                }
            }
        }
        
        // Overhaul interval settings
        $overhaul_settings = [
            'engine_overhaul_interval',
            'gearbox_overhaul_interval', 
            'generator_overhaul_interval'
        ];
        
        foreach ($overhaul_settings as $setting) {
            if (isset($_POST[$setting]) && is_numeric($_POST[$setting]) && $_POST[$setting] > 0) {
                $pdo->prepare("INSERT OR REPLACE INTO vessel_settings (setting_key, setting_value, updated_at) 
                              VALUES (?, ?, datetime('now'))")
                    ->execute([$setting, $_POST[$setting]]);
            }
        }
        
        $message = "Vessel configuration updated successfully!";
        
    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Handle graph settings update
if ($_POST && isset($_POST['update_graph_settings'])) {
    try {
        $pdo = getTestDatabase();
        
        $graph_settings = [
            'graph_rpm_min' => floatval($_POST['graph_rpm_min'] ?? 0),
            'graph_rpm_max' => floatval($_POST['graph_rpm_max'] ?? 2000),
            'graph_pressure_min' => floatval($_POST['graph_pressure_min'] ?? 0),
            'graph_pressure_max' => floatval($_POST['graph_pressure_max'] ?? 100),
            'graph_temp_min' => floatval($_POST['graph_temp_min'] ?? 100),
            'graph_temp_max' => floatval($_POST['graph_temp_max'] ?? 300)
        ];
        
        foreach ($graph_settings as $key => $value) {
            $stmt = $pdo->prepare("INSERT OR REPLACE INTO vessel_settings (setting_key, setting_value, updated_at) 
                                  VALUES (?, ?, datetime('now'))");
            $stmt->execute([$key, $value]);
        }
        
        $message = "Performance graph scale settings updated successfully!";
        
    } catch (Exception $e) {
        $error = "Error updating graph settings: " . $e->getMessage();
    }
}

// Handle equipment hours management (engines, gearboxes, generators)
if ($_POST && isset($_POST['manage_equipment_hours'])) {
    try {
        $pdo = getTestDatabase();
        $equipment_type = $_POST['equipment_type'];
        $equipment_id = $_POST['equipment_id'];
        $action = $_POST['hours_action'];
        
        // Validate equipment type and ID
        $valid_equipment = [
            'engine' => ['port_main', 'center_main', 'starboard_main'],
            'gearbox' => ['port_main', 'center_main', 'starboard_main'], 
            'generator' => ['port_gen', 'center_gen', 'starboard_gen']
        ];
        
        if (!array_key_exists($equipment_type, $valid_equipment) || !in_array($equipment_id, $valid_equipment[$equipment_type])) {
            throw new Exception('Invalid equipment type or ID');
        }
        
        // Get current hours for logging
        $hours_table = $equipment_type . '_hours';
        $id_column = $equipment_type . '_type';
        $stmt = $pdo->prepare("SELECT total_hours FROM $hours_table WHERE $id_column = ?");
        $stmt->execute([$equipment_id]);
        $old_hours = $stmt->fetchColumn() ?: 0;
        
        if ($action === 'adjust') {
            $new_hours = floatval($_POST['new_hours']);
            if ($new_hours < 0) {
                throw new Exception('Hours cannot be negative');
            }
            
            // Update equipment hours
            $stmt = $pdo->prepare("UPDATE $hours_table SET total_hours = ?, last_updated = datetime('now') WHERE $id_column = ?");
            $stmt->execute([$new_hours, $equipment_id]);
            
            // Update service tracking
            $stmt = $pdo->prepare("UPDATE service_tracking SET updated_at = datetime('now') WHERE equipment_type = ? AND equipment_id = ?");
            $stmt->execute([$equipment_type, $equipment_id]);
            
            // Create logbook entry
            $equipment_name = ucwords(str_replace('_', ' ', $equipment_id));
            $log_entry = "Hours adjusted: $equipment_name $equipment_type hours changed from $old_hours to $new_hours hours (Administrative correction)";
            createLogEntry($pdo, 'maintenance', $equipment_id, $log_entry, 'Marine Engineer');
            
            $message = "$equipment_name $equipment_type hours adjusted from $old_hours to $new_hours hours.";
            
        } elseif ($action === 'reset') {
            // Reset to 0 hours and reset all service tracking
            $stmt = $pdo->prepare("UPDATE $hours_table SET total_hours = 0, hours_since_overhaul = 0, last_updated = datetime('now') WHERE $id_column = ?");
            $stmt->execute([$equipment_id]);
            
            // Reset all service tracking for this equipment
            $stmt = $pdo->prepare("UPDATE service_tracking SET last_service_hours = 0, updated_at = datetime('now') WHERE equipment_type = ? AND equipment_id = ?");
            $stmt->execute([$equipment_type, $equipment_id]);
            
            // Create logbook entry
            $equipment_name = ucwords(str_replace('_', ' ', $equipment_id));
            $log_entry = "Hours reset: $equipment_name $equipment_type hours reset from $old_hours to 0 hours (Overhaul/Repowering)";
            createLogEntry($pdo, 'maintenance', $equipment_id, $log_entry, 'Marine Engineer');
            
            $message = "$equipment_name $equipment_type hours reset from $old_hours to 0 hours. All service intervals reset.";
        }
        
    } catch (Exception $e) {
        $error = "Error managing equipment hours: " . $e->getMessage();
    }
}

// Get current settings
try {
    $pdo = getTestDatabase();
    
    // Create settings table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS vessel_settings (
        id INTEGER PRIMARY KEY,
        setting_key TEXT UNIQUE,
        setting_value TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM vessel_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Set defaults if no settings exist
    $default_settings = [
        'engine_port_main_active' => '1',
        'engine_center_main_active' => '1',
        'engine_starboard_main_active' => '1',
        'gearbox_port_main_active' => '1',
        'gearbox_center_main_active' => '1',
        'gearbox_starboard_main_active' => '1',
        'generator_port_gen_active' => '1',
        'generator_center_gen_active' => '1',
        'generator_starboard_gen_active' => '1',
        'vessel_type' => 'triple_screw',
        'engine_overhaul_interval' => '8000',
        'gearbox_overhaul_interval' => '6000',
        'generator_overhaul_interval' => '4000'
    ];
    
    foreach ($default_settings as $key => $value) {
        if (!isset($settings[$key])) {
            $settings[$key] = $value;
        }
    }
    
    // Load service items and settings
    $service_items_stmt = $pdo->query("SELECT * FROM service_items ORDER BY category, item_name");
    $service_items = $service_items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Load current service settings for each active engine
    $service_settings_stmt = $pdo->query("
        SELECT equipment_id, service_item_code, is_enabled, interval_hours 
        FROM service_settings 
        WHERE equipment_type = 'engine'
    ");
    $service_settings = [];
    while ($row = $service_settings_stmt->fetch(PDO::FETCH_ASSOC)) {
        $service_settings[$row['equipment_id']][$row['service_item_code']] = $row;
    }
    
} catch (Exception $e) {
    $error = "Error loading settings: " . $e->getMessage();
    $settings = [];
    $service_items = [];
    $service_settings = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Settings - Marine Engine Room</title>
    <link rel="stylesheet" href="test_styles.css">
</head>
<body>
    <div class="header">
        <h1>⚙️ Vessel Configuration Settings</h1>
        <p>Configure active engines and gearboxes for your vessel type</p>
        <a href="index.php" style="color: white; text-decoration: none;">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <h3>🚢 Vessel Type Configuration</h3>
            <form method="POST">
                
                <!-- Vessel Type Selection -->
                <div class="form-group">
                    <label><strong>Vessel Type:</strong></label>
                    <select name="vessel_type" id="vessel_type" onchange="updateVesselConfig()">
                        <option value="single_screw" <?php echo ($settings['vessel_type'] ?? '') == 'single_screw' ? 'selected' : ''; ?>>
                            Single Screw (1 Engine + 1 Gearbox)
                        </option>
                        <option value="twin_screw" <?php echo ($settings['vessel_type'] ?? '') == 'twin_screw' ? 'selected' : ''; ?>>
                            Twin Screw (Port + Starboard)
                        </option>
                        <option value="triple_screw" <?php echo ($settings['vessel_type'] ?? '') == 'triple_screw' ? 'selected' : ''; ?>>
                            Triple Screw (Port + Center + Starboard)
                        </option>
                        <option value="custom" <?php echo ($settings['vessel_type'] ?? '') == 'custom' ? 'selected' : ''; ?>>
                            Custom Configuration
                        </option>
                    </select>
                </div>
                
                <hr style="margin: 30px 0; border: 1px solid #ecf0f1;">
                
                <!-- Engine Configuration -->
                <h4 style="color: #2c3e50; margin-bottom: 20px;">🔧 Active Main Engines</h4>
                <div class="settings-grid">
                    <div class="setting-item">
                        <label class="setting-label">
                            <input type="checkbox" name="engine_port_main" value="1" 
                                   <?php echo ($settings['engine_port_main_active'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Port Main Engine
                        </label>
                        <p class="setting-description">Primary port side main engine</p>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">
                            <input type="checkbox" name="engine_center_main" value="1" 
                                   <?php echo ($settings['engine_center_main_active'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Center Main Engine
                        </label>
                        <p class="setting-description">Center line main engine</p>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">
                            <input type="checkbox" name="engine_starboard_main" value="1" 
                                   <?php echo ($settings['engine_starboard_main_active'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Starboard Main Engine
                        </label>
                        <p class="setting-description">Primary starboard side main engine</p>
                    </div>
                </div>
                
                <!-- Gearbox Configuration -->
                <h4 style="color: #2c3e50; margin: 30px 0 20px 0;">⚙️ Active Main Gearboxes</h4>
                <div class="settings-grid">
                    <div class="setting-item">
                        <label class="setting-label">
                            <input type="checkbox" name="gearbox_port_main" value="1" 
                                   <?php echo ($settings['gearbox_port_main_active'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Port Main Gearbox
                        </label>
                        <p class="setting-description">Port side main transmission</p>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">
                            <input type="checkbox" name="gearbox_center_main" value="1" 
                                   <?php echo ($settings['gearbox_center_main_active'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Center Main Gearbox
                        </label>
                        <p class="setting-description">Center line main transmission</p>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">
                            <input type="checkbox" name="gearbox_starboard_main" value="1" 
                                   <?php echo ($settings['gearbox_starboard_main_active'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Starboard Main Gearbox
                        </label>
                        <p class="setting-description">Starboard side main transmission</p>
                    </div>
                </div>
                
                <!-- Generator Configuration -->
                <h4 style="color: #2c3e50; margin: 30px 0 20px 0;">🔌 Active Generators</h4>
                <div class="settings-grid">
                    <div class="setting-item">
                        <label class="setting-label">
                            <input type="checkbox" name="generator_port_gen" value="1" 
                                   <?php echo ($settings['generator_port_gen_active'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Port Generator
                        </label>
                        <p class="setting-description">Port side auxiliary generator</p>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">
                            <input type="checkbox" name="generator_center_gen" value="1" 
                                   <?php echo ($settings['generator_center_gen_active'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Center Generator
                        </label>
                        <p class="setting-description">Center line auxiliary generator</p>
                    </div>
                    
                    <div class="setting-item">
                        <label class="setting-label">
                            <input type="checkbox" name="generator_starboard_gen" value="1" 
                                   <?php echo ($settings['generator_starboard_gen_active'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Starboard Generator
                        </label>
                        <p class="setting-description">Starboard side auxiliary generator</p>
                    </div>
                </div>
                
                <!-- Service Items Configuration -->
                <h4 style="color: #2c3e50; margin: 30px 0 20px 0;">🔧 Main Engine Service Items Configuration</h4>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #495057;">
                        Configure service items and intervals for ALL main engines. These settings apply universally to all active main engines. 
                        Engine-specific items (Racor, Turbo, Soakback) can be enabled based on your engine models.
                    </p>
                </div>
                
                <?php if (!empty($service_items)): 
                    // Group service items by category
                    $grouped_items = [];
                    foreach ($service_items as $item) {
                        $grouped_items[$item['category']][] = $item;
                    }
                ?>
                
                <div class="service-config-container">
                    <div class="engine-service-section" style="background: white; border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 25px; overflow: hidden;">
                        <div style="background: #007bff; color: white; padding: 15px; font-weight: bold;">
                            ⚙️ Universal Main Engine Service Settings
                        </div>
                        
                        <div style="padding: 20px;">
                            <div style="background: #e7f3ff; padding: 15px; border-radius: 6px; border-left: 4px solid #007bff; margin-bottom: 20px;">
                                <p style="margin: 0; font-size: 14px; color: #004085;">
                                    <strong>💡 Note:</strong> These settings apply to ALL active main engines: 
                                    <?php 
                                    $active_engine_names = [];
                                    if (($settings['engine_port_main_active'] ?? '1') == '1') $active_engine_names[] = 'Port';
                                    if (($settings['engine_center_main_active'] ?? '1') == '1') $active_engine_names[] = 'Center';
                                    if (($settings['engine_starboard_main_active'] ?? '1') == '1') $active_engine_names[] = 'Starboard';
                                    echo implode(', ', $active_engine_names) . ' Main Engine' . (count($active_engine_names) > 1 ? 's' : '');
                                    ?>
                                </p>
                            </div>
                            
                            <?php foreach ($grouped_items as $category => $items): ?>
                                <h5 style="color: #495057; margin: 20px 0 15px 0; font-size: 16px; border-bottom: 1px solid #dee2e6; padding-bottom: 5px;">
                                    <?php 
                                    $category_icons = [
                                        'Filters' => '🔽',
                                        'Oil Changes' => '🛢️',
                                        'Maintenance' => '🔧'
                                    ];
                                    echo ($category_icons[$category] ?? '⚙️') . ' ' . $category;
                                    ?>
                                </h5>
                                
                                <div style="display: grid; gap: 15px;">
                                    <?php foreach ($items as $item): ?>
                                        <?php 
                                        // Use port_main as reference for universal settings
                                        $current_setting = $service_settings['port_main'][$item['item_code']] ?? null;
                                        $is_enabled = $current_setting ? $current_setting['is_enabled'] : $item['is_universal'];
                                        $interval = $current_setting ? $current_setting['interval_hours'] : $item['default_interval_hours'];
                                        $is_engine_specific = !$item['is_universal'];
                                        ?>
                                        
                                        <div class="service-item-config" style="display: flex; align-items: center; padding: 12px; border: 1px solid #e9ecef; border-radius: 6px; background: <?php echo $is_engine_specific ? '#fff3cd' : '#f8f9fa'; ?>;">
                                            <div style="flex: 0 0 auto; margin-right: 15px;">
                                                <label class="setting-label" style="margin: 0;">
                                                    <input type="checkbox" 
                                                           name="universal_service_settings[<?php echo $item['item_code']; ?>][enabled]" 
                                                           value="1" 
                                                           <?php echo $is_enabled ? 'checked' : ''; ?>>
                                                    <span class="checkmark"></span>
                                                </label>
                                            </div>
                                            
                                            <div style="flex: 1; margin-right: 15px;">
                                                <div style="font-weight: bold; color: #2c3e50; margin-bottom: 2px;">
                                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                                    <?php if ($is_engine_specific): ?>
                                                        <span style="background: #ffc107; color: #212529; font-size: 10px; padding: 2px 6px; border-radius: 3px; margin-left: 8px;">ENGINE SPECIFIC</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="font-size: 12px; color: #6c757d;">
                                                    <?php echo htmlspecialchars($item['description']); ?>
                                                    <?php if ($item['engine_models']): ?>
                                                        <br><em>Models: <?php echo htmlspecialchars($item['engine_models']); ?></em>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div style="flex: 0 0 120px;">
                                                <input type="number" 
                                                       name="universal_service_settings[<?php echo $item['item_code']; ?>][interval]"
                                                       value="<?php echo $interval; ?>"
                                                       min="1" 
                                                       step="1"
                                                       style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; text-align: center; font-size: 12px;">
                                                <div style="font-size: 10px; color: #6c757d; text-align: center; margin-top: 2px;">hours</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
                
                <!-- Gearbox Service Items Configuration -->
                <h4 style="color: #2c3e50; margin: 30px 0 20px 0;">⚙️ Gearbox Service Items Configuration</h4>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #495057;">
                        Configure service items and intervals for ALL gearboxes. Simple maintenance: Lube Filter, Lube Strainer, Oil Change.
                        These settings apply universally to all active gearboxes.
                    </p>
                </div>
                
                <?php 
                // Get gearbox service items
                $gearbox_items_stmt = $pdo->query("SELECT * FROM service_items WHERE item_code LIKE 'gearbox_%' ORDER BY category, item_name");
                $gearbox_items = $gearbox_items_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($gearbox_items)):
                    // Group by category
                    $gearbox_grouped = [];
                    foreach ($gearbox_items as $item) {
                        $gearbox_grouped[$item['category']][] = $item;
                    }
                ?>
                
                <div class="service-config-container">
                    <div class="engine-service-section" style="background: white; border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 25px; overflow: hidden;">
                        <div style="background: #28a745; color: white; padding: 15px; font-weight: bold;">
                            ⚙️ Universal Gearbox Service Settings
                        </div>
                        
                        <div style="padding: 20px;">
                            <div style="background: #d4edda; padding: 15px; border-radius: 6px; border-left: 4px solid #28a745; margin-bottom: 20px;">
                                <p style="margin: 0; font-size: 14px; color: #155724;">
                                    <strong>💡 Note:</strong> These settings apply to ALL active gearboxes: 
                                    <?php 
                                    $active_gearbox_names = [];
                                    if (($settings['gearbox_port_main_active'] ?? '1') == '1') $active_gearbox_names[] = 'Port';
                                    if (($settings['gearbox_center_main_active'] ?? '1') == '1') $active_gearbox_names[] = 'Center';
                                    if (($settings['gearbox_starboard_main_active'] ?? '1') == '1') $active_gearbox_names[] = 'Starboard';
                                    echo implode(', ', $active_gearbox_names) . ' Gearbox' . (count($active_gearbox_names) > 1 ? 'es' : '');
                                    ?>
                                </p>
                            </div>
                            
                            <?php foreach ($gearbox_grouped as $category => $items): ?>
                                <h5 style="color: #495057; margin: 20px 0 15px 0; font-size: 16px; border-bottom: 1px solid #dee2e6; padding-bottom: 5px;">
                                    <?php 
                                    $category_icons = [
                                        'Filters' => '🔽',
                                        'Oil Changes' => '🛢️',
                                        'Maintenance' => '🔧'
                                    ];
                                    echo ($category_icons[$category] ?? '⚙️') . ' ' . $category;
                                    ?>
                                </h5>
                                
                                <div style="display: grid; gap: 15px;">
                                    <?php foreach ($items as $item): ?>
                                        <?php 
                                        // Use port_main as reference for universal settings
                                        $gearbox_setting = null;
                                        foreach (['port_main', 'center_main', 'starboard_main'] as $gb) {
                                            if (isset($service_settings[$gb][$item['item_code']])) {
                                                $gearbox_setting = $service_settings[$gb][$item['item_code']];
                                                break;
                                            }
                                        }
                                        $is_enabled = $gearbox_setting ? $gearbox_setting['is_enabled'] : 1;
                                        $interval = $gearbox_setting ? $gearbox_setting['interval_hours'] : $item['default_interval_hours'];
                                        ?>
                                        
                                        <div class="service-item-config" style="display: flex; align-items: center; padding: 12px; border: 1px solid #e9ecef; border-radius: 6px; background: #f8f9fa;">
                                            <div style="flex: 0 0 auto; margin-right: 15px;">
                                                <label class="setting-label" style="margin: 0;">
                                                    <input type="checkbox" 
                                                           name="universal_gearbox_settings[<?php echo $item['item_code']; ?>][enabled]" 
                                                           value="1" 
                                                           <?php echo $is_enabled ? 'checked' : ''; ?>>
                                                    <span class="checkmark"></span>
                                                </label>
                                            </div>
                                            
                                            <div style="flex: 1; margin-right: 15px;">
                                                <div style="font-weight: bold; color: #2c3e50; margin-bottom: 2px;">
                                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                                </div>
                                                <div style="font-size: 12px; color: #6c757d;">
                                                    <?php echo htmlspecialchars($item['description']); ?>
                                                </div>
                                            </div>
                                            
                                            <div style="flex: 0 0 120px;">
                                                <input type="number" 
                                                       name="universal_gearbox_settings[<?php echo $item['item_code']; ?>][interval]"
                                                       value="<?php echo $interval; ?>"
                                                       min="1" 
                                                       step="1"
                                                       style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; text-align: center; font-size: 12px;">
                                                <div style="font-size: 10px; color: #6c757d; text-align: center; margin-top: 2px;">hours</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
                
                <!-- Generator Service Settings -->
                <?php 
                $generator_services = $pdo->query("
                    SELECT si.item_code, si.item_name, si.category, si.description
                    FROM service_items si
                    WHERE si.item_code IN ('racor_fuel_filter', 'primary_fuel_filter', 'secondary_fuel_filter', 'generator_oil_filter', 'air_intake_filter')
                    ORDER BY si.category, si.item_name
                ")->fetchAll(PDO::FETCH_ASSOC);
                
                $generator_grouped = [];
                foreach ($generator_services as $service) {
                    $generator_grouped[$service['category']][] = $service;
                }
                ?>
                
                <?php if (!empty($generator_services)): ?>
                <div style="margin: 20px 0;">
                    <div class="settings-section">
                        <div class="settings-section-header">
                            ⚡ Universal Generator Service Settings
                        </div>
                        
                        <div style="padding: 20px;">
                            <div style="background: #d4edda; padding: 15px; border-radius: 6px; border-left: 4px solid #28a745; margin-bottom: 20px;">
                                <p style="margin: 0; font-size: 14px; color: #155724;">
                                    <strong>💡 Note:</strong> These settings apply to ALL active generators: 
                                    <?php 
                                    $active_generator_names = [];
                                    if (($settings['generator_port_gen_active'] ?? '1') == '1') $active_generator_names[] = 'Port';
                                    if (($settings['generator_center_gen_active'] ?? '1') == '1') $active_generator_names[] = 'Center';
                                    if (($settings['generator_starboard_gen_active'] ?? '1') == '1') $active_generator_names[] = 'Starboard';
                                    echo implode(', ', $active_generator_names) . ' Generator' . (count($active_generator_names) > 1 ? 's' : '');
                                    ?>
                                </p>
                            </div>
                            
                            <?php foreach ($generator_grouped as $category => $items): ?>
                                <h5 style="color: #495057; margin: 20px 0 15px 0; font-size: 16px; border-bottom: 1px solid #dee2e6; padding-bottom: 5px;">
                                    <?php 
                                    $category_icons = [
                                        'Filters' => '🔽',
                                        'Oil Changes' => '🛢️',
                                        'Maintenance' => '🔧'
                                    ];
                                    echo ($category_icons[$category] ?? '⚙️') . ' ' . $category;
                                    ?>
                                </h5>
                                
                                <div style="display: grid; gap: 15px;">
                                    <?php foreach ($items as $item): ?>
                                        <?php 
                                        // Use port_gen as reference for universal settings
                                        $generator_setting = null;
                                        foreach (['port_gen', 'center_gen', 'starboard_gen'] as $gen) {
                                            if (isset($service_settings[$gen][$item['item_code']])) {
                                                $generator_setting = $service_settings[$gen][$item['item_code']];
                                                break;
                                            }
                                        }
                                        $is_enabled = $generator_setting ? $generator_setting['is_enabled'] : 1;
                                        $interval = $generator_setting ? $generator_setting['interval_hours'] : 250;
                                        ?>
                                        
                                        <div class="service-item-config" style="display: flex; align-items: center; padding: 12px; border: 1px solid #e9ecef; border-radius: 6px; background: #f8f9fa;">
                                            <div style="flex: 0 0 auto; margin-right: 15px;">
                                                <label class="setting-label" style="margin: 0;">
                                                    <input type="checkbox" 
                                                           name="universal_generator_settings[<?php echo $item['item_code']; ?>][enabled]" 
                                                           value="1" 
                                                           <?php echo $is_enabled ? 'checked' : ''; ?>>
                                                    <span class="checkmark"></span>
                                                </label>
                                            </div>
                                            
                                            <div style="flex: 1; margin-right: 15px;">
                                                <div style="font-weight: bold; color: #2c3e50; margin-bottom: 2px;">
                                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                                </div>
                                                <div style="font-size: 12px; color: #6c757d;">
                                                    <?php echo htmlspecialchars($item['description']); ?>
                                                </div>
                                            </div>
                                            
                                            <div style="flex: 0 0 120px;">
                                                <input type="number" 
                                                       name="universal_generator_settings[<?php echo $item['item_code']; ?>][interval]"
                                                       value="<?php echo $interval; ?>"
                                                       min="1" 
                                                       step="1"
                                                       style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; text-align: center; font-size: 12px;">
                                                <div style="font-size: 10px; color: #6c757d; text-align: center; margin-top: 2px;">hours</div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
                
                <!-- Maintenance Interval Configuration -->
                <h4 style="color: #2c3e50; margin: 30px 0 20px 0;">🔧 Overhaul Intervals (Hours)</h4>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <p style="color: #6c757d; margin-bottom: 15px; font-size: 14px;">
                        Set the maintenance intervals for calculating overhaul progress. These values determine when equipment needs scheduled maintenance.
                    </p>
                    
                    <div class="settings-grid" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div class="form-group">
                            <label for="engine_overhaul_interval" style="font-weight: bold; color: #2c3e50;">
                                🔧 Engine Overhaul Interval
                            </label>
                            <input type="number" 
                                   name="engine_overhaul_interval" 
                                   id="engine_overhaul_interval"
                                   value="<?php echo htmlspecialchars($settings['engine_overhaul_interval'] ?? '8000'); ?>"
                                   min="100" 
                                   step="100"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-top: 5px;">
                            <p style="font-size: 12px; color: #6c757d; margin-top: 5px;">Hours between major overhauls</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="gearbox_overhaul_interval" style="font-weight: bold; color: #2c3e50;">
                                ⚙️ Gearbox Overhaul Interval  
                            </label>
                            <input type="number" 
                                   name="gearbox_overhaul_interval" 
                                   id="gearbox_overhaul_interval"
                                   value="<?php echo htmlspecialchars($settings['gearbox_overhaul_interval'] ?? '6000'); ?>"
                                   min="100" 
                                   step="100"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-top: 5px;">
                            <p style="font-size: 12px; color: #6c757d; margin-top: 5px;">Hours between gearbox overhauls</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="generator_overhaul_interval" style="font-weight: bold; color: #2c3e50;">
                                🔌 Generator Overhaul Interval
                            </label>
                            <input type="number" 
                                   name="generator_overhaul_interval" 
                                   id="generator_overhaul_interval"
                                   value="<?php echo htmlspecialchars($settings['generator_overhaul_interval'] ?? '4000'); ?>"
                                   min="100" 
                                   step="100"
                                   style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; margin-top: 5px;">
                            <p style="font-size: 12px; color: #6c757d; margin-top: 5px;">Hours between generator overhauls</p>
                        </div>
                    </div>
                    
                    <div style="background: #e7f3ff; padding: 15px; border-radius: 6px; border-left: 4px solid #007bff; margin-top: 15px;">
                        <p style="margin: 0; font-size: 14px; color: #004085;">
                            <strong>💡 Typical Marine Intervals:</strong><br>
                            • Main Engines: 8,000-12,000 hours (can be higher for newer engines)<br>
                            • Gearboxes: 6,000-10,000 hours (some industrial units exceed 20,000+)<br> 
                            • Generators: 3,000-6,000 hours (varies widely by manufacturer)<br>
                            <em>Enter any interval that matches your manufacturer specifications or operational requirements.</em>
                        </p>
                    </div>
                </div>
                
                <div style="margin-top: 30px; text-align: center;">
                    <button type="submit" name="update_settings" class="btn btn-success" style="padding: 15px 30px; font-size: 16px;">
                        💾 Save Vessel Configuration
                    </button>
                </div>
            </form>
        </div>

        <!-- Performance Graph Settings -->
        <div class="form-section">
            <h3>📊 Performance Graph Settings</h3>
            <p>Configure default Y-axis scale ranges for equipment performance graphs. These settings apply to all graphs and can help standardize the view across different equipment types.</p>
            
            <?php
            // Get current graph settings
            $graph_settings = [
                'graph_rpm_min' => $settings['graph_rpm_min'] ?? '0',
                'graph_rpm_max' => $settings['graph_rpm_max'] ?? '2000',
                'graph_pressure_min' => $settings['graph_pressure_min'] ?? '0',
                'graph_pressure_max' => $settings['graph_pressure_max'] ?? '100',
                'graph_temp_min' => $settings['graph_temp_min'] ?? '100',
                'graph_temp_max' => $settings['graph_temp_max'] ?? '300'
            ];
            ?>
            
            <form method="POST" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
                <div class="form-grid">
                    <div class="form-group">
                        <label>RPM Scale Range:</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" name="graph_rpm_min" value="<?php echo htmlspecialchars($graph_settings['graph_rpm_min']); ?>" placeholder="Min RPM" style="width: 100px;">
                            <span>to</span>
                            <input type="number" name="graph_rpm_max" value="<?php echo htmlspecialchars($graph_settings['graph_rpm_max']); ?>" placeholder="Max RPM" style="width: 100px;">
                            <span class="form-hint">RPM</span>
                        </div>
                        <p class="form-hint">Typical range: 0-2000 RPM for main engines, 0-2200 for generators</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Pressure Scale Range:</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" name="graph_pressure_min" value="<?php echo htmlspecialchars($graph_settings['graph_pressure_min']); ?>" placeholder="Min PSI" style="width: 100px;">
                            <span>to</span>
                            <input type="number" name="graph_pressure_max" value="<?php echo htmlspecialchars($graph_settings['graph_pressure_max']); ?>" placeholder="Max PSI" style="width: 100px;">
                            <span class="form-hint">PSI</span>
                        </div>
                        <p class="form-hint">Typical range: 0-100 PSI for most marine engine pressures</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Temperature Scale Range:</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" name="graph_temp_min" value="<?php echo htmlspecialchars($graph_settings['graph_temp_min']); ?>" placeholder="Min °F" style="width: 100px;">
                            <span>to</span>
                            <input type="number" name="graph_temp_max" value="<?php echo htmlspecialchars($graph_settings['graph_temp_max']); ?>" placeholder="Max °F" style="width: 100px;">
                            <span class="form-hint">°F</span>
                        </div>
                        <p class="form-hint">Typical range: 100-300°F for engine operating temperatures</p>
                    </div>
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <button type="submit" name="update_graph_settings" class="btn btn-primary">
                        📊 Update Graph Settings
                    </button>
                    <a href="equipment_graphs.php" class="btn btn-secondary" style="margin-left: 10px;">
                        📈 Open Performance Graphs
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Equipment Hours Management -->
        <div class="form-section" style="border: 2px solid #dc3545; background: #fff5f5;">
            <h3 style="color: #dc3545;">⚠️ Equipment Hours Management</h3>
            <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                <p style="color: #721c24; margin: 0; font-weight: bold;">
                    🚨 CAUTION: These controls directly modify equipment hours and service tracking. Use only for:
                </p>
                <ul style="color: #721c24; margin: 10px 0 0 20px;">
                    <li>Correcting data entry errors</li>
                    <li>Resetting after equipment overhaul or replacement</li>
                    <li>Initial setup of accurate equipment hours</li>
                </ul>
            </div>
            
            <?php
            // Get current equipment hours
            $equipment_hours = [];
            try {
                // Get engine hours
                $stmt = $pdo->query("SELECT engine_type, total_hours FROM engine_hours ORDER BY engine_type");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $equipment_hours['engine'][$row['engine_type']] = $row['total_hours'];
                }
                
                // Get gearbox hours  
                $stmt = $pdo->query("SELECT gearbox_type, total_hours FROM gearbox_hours ORDER BY gearbox_type");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $equipment_hours['gearbox'][$row['gearbox_type']] = $row['total_hours'];
                }
                
                // Get generator hours
                $stmt = $pdo->query("SELECT generator_type, total_hours FROM generator_hours ORDER BY generator_type");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $equipment_hours['generator'][$row['generator_type']] = $row['total_hours'];
                }
            } catch (Exception $e) {
                // Ignore errors for display
            }
            ?>
            
            <form method="POST" onsubmit="return confirm('Are you sure you want to modify equipment hours? This will affect all service tracking and will be logged in the engine room logbook.');">
                <div class="form-grid" style="grid-template-columns: 150px 200px 200px 200px 1fr;">
                    <div class="form-group">
                        <label>Equipment Type:</label>
                        <select name="equipment_type" required onchange="updateEquipmentOptions()">
                            <option value="">Choose Type...</option>
                            <option value="engine">Main Engine</option>
                            <option value="gearbox">Gearbox</option>
                            <option value="generator">Generator</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Select Equipment:</label>
                        <select name="equipment_id" required id="equipment_select">
                            <option value="">Choose Equipment...</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Action:</label>
                        <select name="hours_action" required onchange="toggleHoursInput(this.value)">
                            <option value="">Choose Action...</option>
                            <option value="adjust">Adjust Hours</option>
                            <option value="reset">Reset to Zero</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="hours_input_group" style="display: none;">
                        <label>New Total Hours:</label>
                        <input type="number" name="new_hours" step="0.1" min="0" placeholder="2874.5">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="manage_equipment_hours" class="btn btn-danger" style="margin-top: 25px;">
                            🔧 Apply Changes
                        </button>
                    </div>
                </div>
            </form>
            
            <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 8px; border-left: 4px solid #0066cc;">
                <h4 style="color: #0066cc; margin-top: 0;">📋 Current Equipment Hours:</h4>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <div>
                        <h5>🔧 Main Engines:</h5>
                        <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
                            <li>Port Main: <?php echo $equipment_hours['engine']['port_main'] ?? '0'; ?> hrs</li>
                            <li>Center Main: <?php echo $equipment_hours['engine']['center_main'] ?? '0'; ?> hrs</li>  
                            <li>Starboard Main: <?php echo $equipment_hours['engine']['starboard_main'] ?? '0'; ?> hrs</li>
                        </ul>
                    </div>
                    <div>
                        <h5>⚙️ Gearboxes:</h5>
                        <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
                            <li>Port Main: <?php echo $equipment_hours['gearbox']['port_main'] ?? '0'; ?> hrs</li>
                            <li>Center Main: <?php echo $equipment_hours['gearbox']['center_main'] ?? '0'; ?> hrs</li>
                            <li>Starboard Main: <?php echo $equipment_hours['gearbox']['starboard_main'] ?? '0'; ?> hrs</li>
                        </ul>
                    </div>
                    <div>
                        <h5>🔌 Generators:</h5>
                        <ul style="margin: 0; padding-left: 20px; font-size: 14px;">
                            <li>Port Gen: <?php echo $equipment_hours['generator']['port_gen'] ?? '0'; ?> hrs</li>
                            <li>Center Gen: <?php echo $equipment_hours['generator']['center_gen'] ?? '0'; ?> hrs</li>
                            <li>Starboard Gen: <?php echo $equipment_hours['generator']['starboard_gen'] ?? '0'; ?> hrs</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Configuration Info -->
        <div class="form-section">
            <h3>ℹ️ Configuration Information</h3>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8;">
                <p><strong>How it works:</strong></p>
                <ul style="margin-left: 20px; line-height: 1.6;">
                    <li>✅ <strong>Active equipment</strong> will display normally on the dashboard</li>
                    <li>❌ <strong>Inactive equipment</strong> will be greyed out and management pages will be disabled</li>
                    <li>🚢 <strong>Vessel type</strong> presets automatically configure common arrangements</li>
                    <li>⚙️ <strong>Custom configuration</strong> allows manual selection of active equipment</li>
                    <li>💾 Settings are saved and applied immediately to the dashboard</li>
                </ul>
                
                <p style="margin-top: 15px; font-size: 14px; color: #6c757d;">
                    <strong>Note:</strong> Inactive equipment cards remain visible but disabled to maintain dashboard layout consistency.
                </p>
            </div>
        </div>
    </div>

    <script>
    function updateVesselConfig() {
        const vesselType = document.getElementById('vessel_type').value;
        
        // Get all checkboxes
        const enginePort = document.querySelector('input[name="engine_port_main"]');
        const engineCenter = document.querySelector('input[name="engine_center_main"]');
        const engineStarboard = document.querySelector('input[name="engine_starboard_main"]');
        const gearboxPort = document.querySelector('input[name="gearbox_port_main"]');
        const gearboxCenter = document.querySelector('input[name="gearbox_center_main"]');
        const gearboxStarboard = document.querySelector('input[name="gearbox_starboard_main"]');
        const genPort = document.querySelector('input[name="generator_port_gen"]');
        const genCenter = document.querySelector('input[name="generator_center_gen"]');
        const genStarboard = document.querySelector('input[name="generator_starboard_gen"]');
        
        // Reset all
        [enginePort, engineCenter, engineStarboard, gearboxPort, gearboxCenter, gearboxStarboard,
         genPort, genCenter, genStarboard].forEach(cb => cb.checked = false);
        
        // Apply vessel type settings
        switch(vesselType) {
            case 'single_screw':
                engineCenter.checked = true;
                gearboxCenter.checked = true;
                genCenter.checked = true;
                break;
            case 'twin_screw':
                enginePort.checked = true;
                engineStarboard.checked = true;
                gearboxPort.checked = true;
                gearboxStarboard.checked = true;
                genPort.checked = true;
                genStarboard.checked = true;
                break;
            case 'triple_screw':
                enginePort.checked = true;
                engineCenter.checked = true;
                engineStarboard.checked = true;
                gearboxPort.checked = true;
                gearboxCenter.checked = true;
                gearboxStarboard.checked = true;
                genPort.checked = true;
                genCenter.checked = true;
                genStarboard.checked = true;
                break;
            case 'custom':
                // Don't change anything for custom
                break;
        }
    }
    
    function toggleHoursInput(action) {
        const hoursInputGroup = document.getElementById('hours_input_group');
        if (action === 'adjust') {
            hoursInputGroup.style.display = 'block';
            hoursInputGroup.querySelector('input').required = true;
        } else {
            hoursInputGroup.style.display = 'none';
            hoursInputGroup.querySelector('input').required = false;
        }
    }
    
    function updateEquipmentOptions() {
        const equipmentType = document.querySelector('select[name="equipment_type"]').value;
        const equipmentSelect = document.getElementById('equipment_select');
        
        // Clear existing options
        equipmentSelect.innerHTML = '<option value="">Choose Equipment...</option>';
        
        const equipmentHours = <?php echo json_encode($equipment_hours); ?>;
        
        if (equipmentType === 'engine') {
            equipmentSelect.innerHTML += '<option value="port_main">Port Main (' + (equipmentHours.engine?.port_main || '0') + ' hrs)</option>';
            equipmentSelect.innerHTML += '<option value="center_main">Center Main (' + (equipmentHours.engine?.center_main || '0') + ' hrs)</option>';
            equipmentSelect.innerHTML += '<option value="starboard_main">Starboard Main (' + (equipmentHours.engine?.starboard_main || '0') + ' hrs)</option>';
        } else if (equipmentType === 'gearbox') {
            equipmentSelect.innerHTML += '<option value="port_main">Port Main (' + (equipmentHours.gearbox?.port_main || '0') + ' hrs)</option>';
            equipmentSelect.innerHTML += '<option value="center_main">Center Main (' + (equipmentHours.gearbox?.center_main || '0') + ' hrs)</option>';
            equipmentSelect.innerHTML += '<option value="starboard_main">Starboard Main (' + (equipmentHours.gearbox?.starboard_main || '0') + ' hrs)</option>';
        } else if (equipmentType === 'generator') {
            equipmentSelect.innerHTML += '<option value="port_gen">Port Gen (' + (equipmentHours.generator?.port_gen || '0') + ' hrs)</option>';
            equipmentSelect.innerHTML += '<option value="center_gen">Center Gen (' + (equipmentHours.generator?.center_gen || '0') + ' hrs)</option>';
            equipmentSelect.innerHTML += '<option value="starboard_gen">Starboard Gen (' + (equipmentHours.generator?.starboard_gen || '0') + ' hrs)</option>';
        }
    }
    </script>
</body>
</html>
