<?php
/**
 * Vessel Configuration Settings - Marine Engine Room
 * Configure active engines and gearboxes for different vessel types
 */

require_once 'test_db.php';

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
    
} catch (Exception $e) {
    $error = "Error loading settings: " . $e->getMessage();
    $settings = [];
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
    </script>
</body>
</html>
