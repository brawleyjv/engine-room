<?php
/**
 * Setup engine hours tracking for dashboard cards
 */

require_once 'test_db.php';

try {
    $pdo = getTestDatabase();
    
    // Create engine hours tracking table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS engine_hours (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vessel_id INTEGER NOT NULL DEFAULT 1,
        engine_type TEXT NOT NULL UNIQUE,
        total_hours REAL DEFAULT 0,
        hours_since_overhaul REAL DEFAULT 0,
        last_overhaul_date DATE,
        next_overhaul_hours REAL DEFAULT 8000,
        status TEXT DEFAULT 'operational',
        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vessel_id) REFERENCES test_vessels(id)
    )");
    
    // Insert initial engine hours data if none exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM engine_hours");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO engine_hours (engine_type, total_hours, hours_since_overhaul, last_overhaul_date, next_overhaul_hours) VALUES 
            ('port_main', 15847.5, 2347.5, '2023-06-15', 8000),
            ('center_main', 14920.3, 1920.3, '2023-08-22', 8000),
            ('starboard_main', 16234.8, 3234.8, '2023-02-10', 8000)");
        
        echo "✅ Engine hours data initialized\n";
    }
    
    // Create gearbox hours tracking table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS gearbox_hours (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        vessel_id INTEGER NOT NULL DEFAULT 1,
        gearbox_type TEXT NOT NULL UNIQUE,
        total_hours REAL DEFAULT 0,
        hours_since_overhaul REAL DEFAULT 0,
        last_overhaul_date DATE,
        next_overhaul_hours REAL DEFAULT 12000,
        status TEXT DEFAULT 'operational',
        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (vessel_id) REFERENCES test_vessels(id)
    )");
    
    // Insert initial gearbox hours data if none exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM gearbox_hours");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO gearbox_hours (gearbox_type, total_hours, hours_since_overhaul, last_overhaul_date, next_overhaul_hours) VALUES 
            ('port_main', 15847.5, 2347.5, '2023-06-15', 12000),
            ('center_main', 14920.3, 1920.3, '2023-08-22', 12000),
            ('starboard_main', 16234.8, 3234.8, '2023-02-10', 12000)");
        
        echo "✅ Gearbox hours data initialized\n";
    }
    
    // Show current data
    echo "\n📊 Current Engine Hours:\n";
    $engines = $pdo->query("SELECT * FROM engine_hours ORDER BY engine_type")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($engines as $engine) {
        $overhaul_pct = round(($engine['hours_since_overhaul'] / $engine['next_overhaul_hours']) * 100, 1);
        echo "• " . ucfirst(str_replace('_', ' ', $engine['engine_type'])) . ": " . number_format($engine['hours_since_overhaul'], 1) . " hrs since overhaul ({$overhaul_pct}%)\n";
    }
    
    echo "\n📊 Current Gearbox Hours:\n";
    $gearboxes = $pdo->query("SELECT * FROM gearbox_hours ORDER BY gearbox_type")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($gearboxes as $gearbox) {
        $overhaul_pct = round(($gearbox['hours_since_overhaul'] / $gearbox['next_overhaul_hours']) * 100, 1);
        echo "• " . ucfirst(str_replace('_', ' ', $gearbox['gearbox_type'])) . ": " . number_format($gearbox['hours_since_overhaul'], 1) . " hrs since overhaul ({$overhaul_pct}%)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
