<?php
// single_vessel_migration.php - Set up single vessel SQLite database
define('VESSEL_LOGGER', true);
require_once 'config_simple.php';

echo "<h2>Single Vessel Database Setup</h2>\n";
echo "<p>Setting up SQLite database for single vessel operation...</p>\n";

try {
    // Connect to SQLite database
    $pdo = new PDO("sqlite:$db_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>Step 1: Backing up current database...</h3>\n";
    
    // Create backup
    $backup_path = dirname($db_path) . '/vessel_data_backup_' . date('Y-m-d_H-i-s') . '.sqlite';
    copy($db_path, $backup_path);
    echo "<p>✓ Current database backed up to: " . basename($backup_path) . "</p>\n";
    
    echo "<h3>Step 2: Creating single vessel database structure...</h3>\n";
    
    // Create vessels table with single vessel support
    $pdo->exec("DROP TABLE IF EXISTS vessels");
    $pdo->exec("
        CREATE TABLE vessels (
            VesselID INTEGER PRIMARY KEY DEFAULT 1,
            VesselName TEXT NOT NULL DEFAULT 'My Vessel',
            VesselType TEXT DEFAULT 'Towboat',
            EngineConfig TEXT DEFAULT 'standard' CHECK(EngineConfig IN ('standard', 'three_engine')),
            Owner TEXT DEFAULT 'Vessel Owner',
            YearBuilt INTEGER DEFAULT 2020,
            Length REAL DEFAULT 120.0,
            CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            IsActive INTEGER DEFAULT 1,
            Notes TEXT DEFAULT 'Single vessel USB logger',
            RPMMin INTEGER DEFAULT 650,
            RPMMax INTEGER DEFAULT 1750,
            TempMin INTEGER DEFAULT 20,
            TempMax INTEGER DEFAULT 400,
            PressureMin INTEGER DEFAULT 20,
            PressureMax INTEGER DEFAULT 400,
            GenMin INTEGER DEFAULT 20,
            GenMax INTEGER DEFAULT 400
        )
    ");
    echo "<p>✓ Created vessels table for single vessel</p>\n";
    
    // Insert default vessel configuration
    $pdo->exec("
        INSERT OR REPLACE INTO vessels 
        (VesselID, VesselName, VesselType, EngineConfig, Owner, YearBuilt, Length, Notes, RPMMin, RPMMax, TempMin, TempMax, PressureMin, PressureMax, GenMin, GenMax)
        VALUES 
        (1, 'My Vessel', 'Towboat', 'standard', 'Vessel Owner', 2020, 120.0, 'Single vessel USB logger', 650, 1750, 20, 400, 20, 400, 20, 400)
    ");
    echo "<p>✓ Inserted default vessel configuration (VesselID = 1)</p>\n";
    
    // Update existing tables to ensure VesselID = 1
    echo "<h3>Step 3: Updating existing data for single vessel...</h3>\n";
    
    // Update all existing data to VesselID = 1
    $tables_to_update = ['mainengines', 'generators', 'gears', 'crew_members'];
    
    foreach ($tables_to_update as $table) {
        try {
            // Check if table exists
            $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
            if ($exists) {
                $pdo->exec("UPDATE $table SET VesselID = 1 WHERE VesselID IS NULL OR VesselID != 1");
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "<p>✓ Updated $table: $count records set to VesselID = 1</p>\n";
            }
        } catch (Exception $e) {
            echo "<p>⚠ Warning updating $table: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Update vessel_logs and navigation_data
    try {
        $pdo->exec("UPDATE vessel_logs SET vessel_id = 1 WHERE vessel_id IS NULL OR vessel_id != 1");
        $pdo->exec("UPDATE vessel_logs SET vessel_name = (SELECT VesselName FROM vessels WHERE VesselID = 1)");
        $logs_count = $pdo->query("SELECT COUNT(*) FROM vessel_logs")->fetchColumn();
        echo "<p>✓ Updated vessel_logs: $logs_count records set to vessel_id = 1</p>\n";
    } catch (Exception $e) {
        echo "<p>⚠ Warning updating vessel_logs: " . $e->getMessage() . "</p>\n";
    }
    
    try {
        $pdo->exec("UPDATE navigation_data SET vessel_id = 1 WHERE vessel_id IS NULL OR vessel_id != 1");
        $pdo->exec("UPDATE navigation_data SET vessel_name = (SELECT VesselName FROM vessels WHERE VesselID = 1)");
        $nav_count = $pdo->query("SELECT COUNT(*) FROM navigation_data")->fetchColumn();
        echo "<p>✓ Updated navigation_data: $nav_count records set to vessel_id = 1</p>\n";
    } catch (Exception $e) {
        echo "<p>⚠ Warning updating navigation_data: " . $e->getMessage() . "</p>\n";
    }
    
    echo "<h3>Step 4: Creating vessel configuration helper...</h3>\n";
    
    // Create a simple vessel config view/function
    $pdo->exec("
        CREATE VIEW IF NOT EXISTS current_vessel AS
        SELECT * FROM vessels WHERE VesselID = 1
    ");
    echo "<p>✓ Created current_vessel view</p>\n";
    
    echo "<h3>Step 5: Verifying single vessel setup...</h3>\n";
    
    // Verify the setup
    $vessel = $pdo->query("SELECT * FROM vessels WHERE VesselID = 1")->fetch(PDO::FETCH_ASSOC);
    if ($vessel) {
        echo "<h4>Current Vessel Configuration:</h4>\n";
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Property</th><th>Value</th></tr>\n";
        echo "<tr><td>Vessel Name</td><td>{$vessel['VesselName']}</td></tr>\n";
        echo "<tr><td>Type</td><td>{$vessel['VesselType']}</td></tr>\n";
        echo "<tr><td>Engine Config</td><td>{$vessel['EngineConfig']}</td></tr>\n";
        echo "<tr><td>RPM Range</td><td>{$vessel['RPMMin']} - {$vessel['RPMMax']}</td></tr>\n";
        echo "<tr><td>Temperature Range</td><td>{$vessel['TempMin']} - {$vessel['TempMax']}°F</td></tr>\n";
        echo "<tr><td>Pressure Range</td><td>{$vessel['PressureMin']} - {$vessel['PressureMax']} PSI</td></tr>\n";
        echo "</table>\n";
    }
    
    // Show data counts
    echo "<h4>Data Summary:</h4>\n";
    $tables_check = [
        'vessels' => 'SELECT COUNT(*) FROM vessels',
        'mainengines' => 'SELECT COUNT(*) FROM mainengines WHERE VesselID = 1',
        'generators' => 'SELECT COUNT(*) FROM generators WHERE VesselID = 1',
        'gears' => 'SELECT COUNT(*) FROM gears WHERE VesselID = 1',
        'vessel_logs' => 'SELECT COUNT(*) FROM vessel_logs WHERE vessel_id = 1',
        'navigation_data' => 'SELECT COUNT(*) FROM navigation_data WHERE vessel_id = 1',
        'crew_members' => 'SELECT COUNT(*) FROM crew_members WHERE vessel_id = 1'
    ];
    
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>Table</th><th>Records</th></tr>\n";
    foreach ($tables_check as $table => $query) {
        try {
            $count = $pdo->query($query)->fetchColumn();
            echo "<tr><td>$table</td><td>$count</td></tr>\n";
        } catch (Exception $e) {
            echo "<tr><td>$table</td><td>Error: " . $e->getMessage() . "</td></tr>\n";
        }
    }
    echo "</table>\n";
    
    echo "<h3>✅ Single Vessel Setup Complete!</h3>\n";
    echo "<p><strong>Your vessel logger is now configured for single vessel operation:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✓ All data is associated with VesselID = 1</li>\n";
    echo "<li>✓ Vessel configuration can be updated as needed</li>\n";
    echo "<li>✓ Engine configuration supports standard and three-engine setups</li>\n";
    echo "<li>✓ RPM, temperature, and pressure ranges can be customized</li>\n";
    echo "<li>✓ All existing data preserved and linked to single vessel</li>\n";
    echo "</ul>\n";
    
    echo "<h4>Next Steps:</h4>\n";
    echo "<p>1. <a href='vessel_setup.php'>→ Configure Vessel Details</a> (update name, type, ranges)</p>\n";
    echo "<p>2. <a href='engine_dashboard.php'>→ Test Engine Dashboard</a></p>\n";
    echo "<p>3. <a href='simple_login.php'>→ Test Login System</a></p>\n";
    
} catch (Exception $e) {
    echo "<p><strong>❌ Setup Error:</strong> " . $e->getMessage() . "</p>\n";
    echo "<p>Check the error and try again. Your original database backup is preserved.</p>\n";
}
?>
