<?php
/**
 * Engine Room Database Schema Update
 * Creates tables for engine readings and maintenance logs
 */

require_once __DIR__ . '/../includes/db.php';

try {
    $pdo = getDatabase();
    
    // Create engine_readings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS engine_readings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vessel_id INTEGER NOT NULL,
        reading_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        engine_side TEXT NOT NULL DEFAULT 'main',
        engine_temp REAL,
        oil_pressure REAL,
        coolant_temp REAL,
        rpm INTEGER,
        engine_hours REAL,
        fuel_level REAL,
        notes TEXT,
        created_by TEXT,
        FOREIGN KEY (vessel_id) REFERENCES vessels(id)
    )");
    
    // Create maintenance_log table
    $pdo->exec("CREATE TABLE IF NOT EXISTS maintenance_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vessel_id INTEGER NOT NULL,
        log_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        equipment TEXT NOT NULL,
        maintenance_type TEXT NOT NULL,
        description TEXT,
        next_due_date DATE,
        completed BOOLEAN DEFAULT 0,
        created_by TEXT,
        FOREIGN KEY (vessel_id) REFERENCES vessels(id)
    )");
    
    echo "Engine room database tables created successfully!";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
