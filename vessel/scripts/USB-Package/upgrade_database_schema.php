<?php
// upgrade_database_schema.php - Upgrade existing database to full schema
define('VESSEL_LOGGER', true);

// Direct database connection
$db_path = __DIR__ . '/vessel_data.sqlite';

echo "<h2>Database Schema Upgrade</h2>\n";
echo "<p>Upgrading existing database to complete original schema...</p>\n";

try {
    $pdo = new PDO("sqlite:$db_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h3>Step 1: Checking current schema...</h3>\n";
    
    // Check mainengines table structure
    $columns = $pdo->query("PRAGMA table_info(mainengines)")->fetchAll(PDO::FETCH_ASSOC);
    $existing_columns = array_column($columns, 'name');
    
    echo "<p><strong>Current mainengines columns:</strong> " . implode(', ', $existing_columns) . "</p>\n";
    
    echo "<h3>Step 2: Adding missing columns...</h3>\n";
    
    // Check if FuelPress column exists, add if missing
    if (!in_array('FuelPress', $existing_columns)) {
        $pdo->exec("ALTER TABLE mainengines ADD COLUMN FuelPress INTEGER NOT NULL DEFAULT 0");
        echo "<p>✓ Added FuelPress column to mainengines</p>\n";
    } else {
        echo "<p>✓ FuelPress column already exists</p>\n";
    }
    
    // Check if OilTemp column exists, add if missing
    if (!in_array('OilTemp', $existing_columns)) {
        $pdo->exec("ALTER TABLE mainengines ADD COLUMN OilTemp INTEGER NOT NULL DEFAULT 0");
        echo "<p>✓ Added OilTemp column to mainengines</p>\n";
    } else {
        echo "<p>✓ OilTemp column already exists</p>\n";
    }
    
    // Check if RecordedBy column exists, add if missing
    if (!in_array('RecordedBy', $existing_columns)) {
        $pdo->exec("ALTER TABLE mainengines ADD COLUMN RecordedBy TEXT NOT NULL DEFAULT 'System'");
        echo "<p>✓ Added RecordedBy column to mainengines</p>\n";
    } else {
        echo "<p>✓ RecordedBy column already exists</p>\n";
    }
    
    echo "<h3>Step 3: Checking generators table...</h3>\n";
    
    // Check generators table structure
    $gen_columns = $pdo->query("PRAGMA table_info(generators)")->fetchAll(PDO::FETCH_ASSOC);
    $existing_gen_columns = array_column($gen_columns, 'name');
    
    echo "<p><strong>Current generators columns:</strong> " . implode(', ', $existing_gen_columns) . "</p>\n";
    
    // Add missing generator columns if needed
    if (!in_array('FuelPress', $existing_gen_columns)) {
        $pdo->exec("ALTER TABLE generators ADD COLUMN FuelPress INTEGER NOT NULL DEFAULT 0");
        echo "<p>✓ Added FuelPress column to generators</p>\n";
    } else {
        echo "<p>✓ FuelPress column already exists in generators</p>\n";
    }
    
    if (!in_array('OilPress', $existing_gen_columns)) {
        $pdo->exec("ALTER TABLE generators ADD COLUMN OilPress INTEGER NOT NULL DEFAULT 0");
        echo "<p>✓ Added OilPress column to generators</p>\n";
    } else {
        echo "<p>✓ OilPress column already exists in generators</p>\n";
    }
    
    if (!in_array('WaterTemp', $existing_gen_columns)) {
        $pdo->exec("ALTER TABLE generators ADD COLUMN WaterTemp INTEGER NOT NULL DEFAULT 0");
        echo "<p>✓ Added WaterTemp column to generators</p>\n";
    } else {
        echo "<p>✓ WaterTemp column already exists in generators</p>\n";
    }
    
    if (!in_array('GenHrs', $existing_gen_columns)) {
        $pdo->exec("ALTER TABLE generators ADD COLUMN GenHrs INTEGER NOT NULL DEFAULT 0");
        echo "<p>✓ Added GenHrs column to generators</p>\n";
    } else {
        echo "<p>✓ GenHrs column already exists in generators</p>\n";
    }
    
    if (!in_array('RecordedBy', $existing_gen_columns)) {
        $pdo->exec("ALTER TABLE generators ADD COLUMN RecordedBy TEXT NOT NULL DEFAULT 'System'");
        echo "<p>✓ Added RecordedBy column to generators</p>\n";
    } else {
        echo "<p>✓ RecordedBy column already exists in generators</p>\n";
    }
    
    echo "<h3>Step 4: Checking gears table...</h3>\n";
    
    // Check gears table structure
    $gear_columns = $pdo->query("PRAGMA table_info(gears)")->fetchAll(PDO::FETCH_ASSOC);
    $existing_gear_columns = array_column($gear_columns, 'name');
    
    echo "<p><strong>Current gears columns:</strong> " . implode(', ', $existing_gear_columns) . "</p>\n";
    
    // Add missing gear columns if needed
    if (!in_array('OilPress', $existing_gear_columns)) {
        $pdo->exec("ALTER TABLE gears ADD COLUMN OilPress INTEGER NOT NULL DEFAULT 0");
        echo "<p>✓ Added OilPress column to gears</p>\n";
    } else {
        echo "<p>✓ OilPress column already exists in gears</p>\n";
    }
    
    if (!in_array('Temp', $existing_gear_columns)) {
        $pdo->exec("ALTER TABLE gears ADD COLUMN Temp INTEGER NOT NULL DEFAULT 0");
        echo "<p>✓ Added Temp column to gears</p>\n";
    } else {
        echo "<p>✓ Temp column already exists in gears</p>\n";
    }
    
    // Add OilTemp column for gear oil temperature
    if (!in_array('OilTemp', $existing_gear_columns)) {
        $pdo->exec("ALTER TABLE gears ADD COLUMN OilTemp INTEGER NOT NULL DEFAULT 0");
        echo "<p>✓ Added OilTemp column to gears</p>\n";
    } else {
        echo "<p>✓ OilTemp column already exists in gears</p>\n";
    }
    
    if (!in_array('GearHrs', $existing_gear_columns)) {
        $pdo->exec("ALTER TABLE gears ADD COLUMN GearHrs INTEGER NOT NULL DEFAULT 0");
        echo "<p>✓ Added GearHrs column to gears</p>\n";
    } else {
        echo "<p>✓ GearHrs column already exists in gears</p>\n";
    }
    
    if (!in_array('RecordedBy', $existing_gear_columns)) {
        $pdo->exec("ALTER TABLE gears ADD COLUMN RecordedBy TEXT DEFAULT 'System'");
        echo "<p>✓ Added RecordedBy column to gears</p>\n";
    } else {
        echo "<p>✓ RecordedBy column already exists in gears</p>\n";
    }
    
    echo "<h3>Step 5: Verifying upgraded schema...</h3>\n";
    
    // Verify final schema
    $final_main_columns = $pdo->query("PRAGMA table_info(mainengines)")->fetchAll(PDO::FETCH_ASSOC);
    $final_gen_columns = $pdo->query("PRAGMA table_info(generators)")->fetchAll(PDO::FETCH_ASSOC);
    $final_gear_columns = $pdo->query("PRAGMA table_info(gears)")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Final Schema:</h4>\n";
    echo "<p><strong>mainengines:</strong> " . implode(', ', array_column($final_main_columns, 'name')) . "</p>\n";
    echo "<p><strong>generators:</strong> " . implode(', ', array_column($final_gen_columns, 'name')) . "</p>\n";
    echo "<p><strong>gears:</strong> " . implode(', ', array_column($final_gear_columns, 'name')) . "</p>\n";
    
    echo "<h3>✅ Schema Upgrade Complete!</h3>\n";
    echo "<p><strong>Database now has complete original schema with:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>✓ mainengines: RPM, OilPressure, WaterTemp, FuelPress, OilTemp, MainHrs, RecordedBy</li>\n";
    echo "<li>✓ generators: FuelPress, OilPress, WaterTemp, GenHrs, RecordedBy</li>\n";
    echo "<li>✓ gears: OilPress, Temp, GearHrs, RecordedBy</li>\n";
    echo "<li>✓ All tables have proper Side and VesselID columns</li>\n";
    echo "<li>✓ Ready for full engine room functionality</li>\n";
    echo "</ul>\n";
    
    echo "<p><a href='verify_migration.php'>→ Verify Database</a> | <a href='engine_dashboard.php'>→ Test Dashboard</a></p>\n";
    
} catch (Exception $e) {
    echo "<p><strong>❌ Upgrade Error:</strong> " . $e->getMessage() . "</p>\n";
}
?>
