<?php
/**
 * Auto Sync Service
 * Automatically syncs data every 30 minutes if vessel is online
 * This would run as a background process or scheduled task
 */

// Define constant before including config (only if not already defined)
if (!defined('VESSEL_LOGGER')) {
    define('VESSEL_LOGGER', true);
}

// Include vessel sync manager
require_once 'vessel_sync.php';

// Log file for sync activities
$log_file = __DIR__ . '/auto_sync.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message" . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function auto_sync() {
    try {
        // Check if vessel is registered
        if (!VesselSyncManager::isVesselRegistered()) {
            log_message("Auto sync skipped - vessel not registered");
            return false;
        }
        
        $sync_manager = new VesselSyncManager();
        
        // This will validate with server every 30 minutes automatically
        $sync_result = $sync_manager->syncToServer();
        
        if ($sync_result['success']) {
            $results = $sync_result['results'];
            $validation_mode = $results['validation_mode'] ?? 'unknown';
            
            log_message("Auto sync completed successfully:");
            log_message("- Validation mode: $validation_mode");
            log_message("- Logs synced: " . $results['logs_synced']);
            log_message("- Fuel levels synced: " . $results['fuel_levels_synced']);
            log_message("- Maintenance synced: " . $results['maintenance_synced']);
            
            return true;
        } else {
            log_message("Auto sync failed: " . $sync_result['error']);
            return false;
        }
        
    } catch (Exception $e) {
        log_message("Auto sync error: " . $e->getMessage());
        return false;
    }
}

// If called directly (not included), run the sync
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    log_message("Auto sync service started");
    auto_sync();
    log_message("Auto sync service completed");
}
?>
