<?php
/**
 * Settings Helper Functions
 * Functions to load and manage vessel configuration settings
 */

function getVesselSettings($pdo = null) {
    if (!$pdo) {
        $pdo = getTestDatabase();
    }
    
    try {
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
        
        return $settings;
        
    } catch (Exception $e) {
        // Return defaults if there's any error
        return [
            'engine_port_main_active' => '1',
            'engine_center_main_active' => '1',
            'engine_starboard_main_active' => '1',
            'gearbox_port_main_active' => '1',
            'gearbox_center_main_active' => '1',
            'gearbox_starboard_main_active' => '1',
            'generator_port_gen_active' => '1',
            'generator_center_gen_active' => '1',
            'generator_starboard_gen_active' => '1',
            'vessel_type' => 'triple_screw'
        ];
    }
}

function isEngineActive($engine_type, $settings) {
    return ($settings['engine_' . $engine_type . '_active'] ?? '1') == '1';
}

function isGearboxActive($gearbox_type, $settings) {
    return ($settings['gearbox_' . $gearbox_type . '_active'] ?? '1') == '1';
}

function isGeneratorActive($generator_type, $settings) {
    return ($settings['generator_' . $generator_type . '_active'] ?? '1') == '1';
}

// Overhaul interval helper functions
function getEngineOverhaulInterval($settings) {
    return floatval($settings['engine_overhaul_interval'] ?? 8000);
}

function getGearboxOverhaulInterval($settings) {
    return floatval($settings['gearbox_overhaul_interval'] ?? 6000);
}

function getGeneratorOverhaulInterval($settings) {
    return floatval($settings['generator_overhaul_interval'] ?? 4000);
}

function calculateOverhaulProgress($hours_since_overhaul, $overhaul_interval) {
    return round(($hours_since_overhaul / $overhaul_interval) * 100, 1);
}

function getOverhaulStatus($progress_pct) {
    if ($progress_pct >= 90) {
        return ['status' => 'OVERHAUL DUE', 'color' => '🔴', 'class' => 'status-critical'];
    } elseif ($progress_pct >= 75) {
        return ['status' => 'PLAN OVERHAUL', 'color' => '🟡', 'class' => 'status-warning'];
    } else {
        return ['status' => 'OPERATIONAL', 'color' => '🟢', 'class' => 'status-normal'];
    }
}
?>
