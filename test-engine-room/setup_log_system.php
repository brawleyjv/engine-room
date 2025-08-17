<?php
/**
 * Engine Room Log Book System
 * Traditional marine logbook with editing capabilities and audit trail
 */

require_once 'test_db.php';
require_once 'settings_helper.php';

// Create/update log tables
try {
    $pdo = getTestDatabase();
    
    // Create comprehensive log entries table
    $pdo->exec("CREATE TABLE IF NOT EXISTS log_entries (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vessel_id INTEGER DEFAULT 1,
        log_date DATE NOT NULL,
        log_time TIME NOT NULL,
        entry_type TEXT NOT NULL, -- 'engine', 'gearbox', 'generator', 'fluid', 'maintenance', 'manual'
        equipment_id TEXT, -- 'port_main', 'center_main', etc.
        log_entry TEXT NOT NULL,
        created_by TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_edited INTEGER DEFAULT 0,
        UNIQUE(log_date, log_time, entry_type, equipment_id)
    )");
    
    // Create log edit audit trail
    $pdo->exec("CREATE TABLE IF NOT EXISTS log_edits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        log_entry_id INTEGER NOT NULL,
        original_entry TEXT NOT NULL,
        new_entry TEXT NOT NULL,
        edit_reason TEXT NOT NULL,
        edited_by TEXT NOT NULL,
        edited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (log_entry_id) REFERENCES log_entries(id)
    )");
    
    echo "Log system tables created successfully!\n";
    
} catch (Exception $e) {
    echo "Error creating log tables: " . $e->getMessage() . "\n";
}
?>
