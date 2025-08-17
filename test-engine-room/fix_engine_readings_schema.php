<?php
/**
 * Fix Engine Readings Table Schema
 * Update the engine_readings table to have engine_type column instead of engine_side
 */

require_once 'test_db.php';

try {
    $pdo = getTestDatabase();
    echo "<h2>Fixing engine_readings Table Schema</h2>\n";

    // First, check if we have data to preserve
    $count = $pdo->query("SELECT COUNT(*) FROM engine_readings")->fetchColumn();
    echo "<p>Current records in engine_readings: $count</p>\n";

    if ($count > 0) {
        // Backup existing data
        echo "<p>Backing up existing data...</p>\n";
        $existing_data = $pdo->query("SELECT * FROM engine_readings")->fetchAll(PDO::FETCH_ASSOC);
        
        // Drop the old table
        $pdo->exec("DROP TABLE engine_readings");
        echo "<p>Dropped old engine_readings table</p>\n";
        
        // Recreate with correct schema
        $pdo->exec("CREATE TABLE engine_readings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vessel_id INTEGER NOT NULL,
            reading_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            engine_type TEXT NOT NULL, -- 'port_main', 'center_main', 'starboard_main'
            
            -- Basic engine data
            rpm INTEGER,
            fuel_pressure REAL,
            oil_pressure REAL,
            water_temp_in REAL,
            water_temp_out REAL,
            oil_temp_in REAL,
            oil_temp_out REAL,
            
            -- Advanced engine readings
            turbo_oil_pressure REAL,
            governor_air_pressure REAL,
            aftercooler_water_pressure REAL,
            lube_oil_filter_pressure_in REAL,
            lube_oil_filter_pressure_out REAL,
            aftercooler_water_temp_out REAL,
            air_box_pressure REAL,
            crankcase_vacuum REAL,
            ship_air_pressure REAL,
            
            -- Engine hours tracking
            engine_hours_total REAL,
            hours_ran_today REAL,
            
            notes TEXT,
            created_by TEXT,
            FOREIGN KEY (vessel_id) REFERENCES test_vessels(id)
        )");
        echo "<p>Created new engine_readings table with correct schema</p>\n";
        
        // Restore data, mapping engine_side to engine_type
        foreach ($existing_data as $row) {
            // Map old engine_side values to new engine_type values
            $engine_type = $row['engine_side'] ?? 'port_main';
            if ($engine_type == 'port') $engine_type = 'port_main';
            if ($engine_type == 'starboard') $engine_type = 'starboard_main';
            if ($engine_type == 'center') $engine_type = 'center_main';
            
            $stmt = $pdo->prepare("INSERT INTO engine_readings 
                (vessel_id, reading_date, engine_type, rpm, fuel_pressure, oil_pressure, 
                water_temp_in, water_temp_out, oil_temp_in, oil_temp_out, turbo_oil_pressure, 
                governor_air_pressure, aftercooler_water_pressure, lube_oil_filter_pressure_in, 
                lube_oil_filter_pressure_out, aftercooler_water_temp_out, air_box_pressure, 
                crankcase_vacuum, ship_air_pressure, engine_hours_total, hours_ran_today, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $row['vessel_id'],
                $row['reading_date'],
                $engine_type,
                $row['rpm'] ?? null,
                $row['fuel_pressure'] ?? null,
                $row['oil_pressure'] ?? null,
                $row['water_temp_in'] ?? null,
                $row['water_temp_out'] ?? null,
                $row['oil_temp_in'] ?? null,
                $row['oil_temp_out'] ?? null,
                $row['turbo_oil_pressure'] ?? null,
                $row['governor_air_pressure'] ?? null,
                $row['aftercooler_water_pressure'] ?? null,
                $row['lube_oil_filter_pressure_in'] ?? null,
                $row['lube_oil_filter_pressure_out'] ?? null,
                $row['aftercooler_water_temp_out'] ?? null,
                $row['air_box_pressure'] ?? null,
                $row['crankcase_vacuum'] ?? null,
                $row['ship_air_pressure'] ?? null,
                $row['engine_hours_total'] ?? $row['engine_hours'],
                $row['hours_ran_today'] ?? null,
                $row['notes'] ?? null,
                $row['created_by'] ?? 'System'
            ]);
        }
        echo "<p>Restored " . count($existing_data) . " records with updated schema</p>\n";
        
    } else {
        // No data to preserve, just recreate table
        $pdo->exec("DROP TABLE IF EXISTS engine_readings");
        $pdo->exec("CREATE TABLE engine_readings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vessel_id INTEGER NOT NULL,
            reading_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            engine_type TEXT NOT NULL, -- 'port_main', 'center_main', 'starboard_main'
            
            -- Basic engine data
            rpm INTEGER,
            fuel_pressure REAL,
            oil_pressure REAL,
            water_temp_in REAL,
            water_temp_out REAL,
            oil_temp_in REAL,
            oil_temp_out REAL,
            
            -- Advanced engine readings
            turbo_oil_pressure REAL,
            governor_air_pressure REAL,
            aftercooler_water_pressure REAL,
            lube_oil_filter_pressure_in REAL,
            lube_oil_filter_pressure_out REAL,
            aftercooler_water_temp_out REAL,
            air_box_pressure REAL,
            crankcase_vacuum REAL,
            ship_air_pressure REAL,
            
            -- Engine hours tracking
            engine_hours_total REAL,
            hours_ran_today REAL,
            
            notes TEXT,
            created_by TEXT,
            FOREIGN KEY (vessel_id) REFERENCES test_vessels(id)
        )");
        echo "<p>Created new engine_readings table with correct schema</p>\n";
    }

    // Verify the fix
    echo "<h3>Verification:</h3>\n";
    $result = $pdo->query("PRAGMA table_info(engine_readings)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        if ($column['name'] === 'engine_type') {
            echo "<p>✅ engine_type column exists: {$column['name']} ({$column['type']})</p>\n";
        }
    }

    // Test the query that was failing
    $stmt = $pdo->prepare("SELECT * FROM engine_readings WHERE engine_type = ? ORDER BY reading_date DESC LIMIT 10");
    $stmt->execute(['port_main']);
    echo "<p>✅ Query test successful</p>\n";

    echo "<p><strong>✅ Schema fix completed successfully!</strong></p>\n";

} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
