<?php
/**
 * Generator Hours Setup - Professional Marine Engine Room
 * Initialize generator hours tracking for dashboard cards
 */

require_once 'test_db.php';

try {
    $pdo = getTestDatabase();
    
    // Create generator_hours table
    $pdo->exec("CREATE TABLE IF NOT EXISTS generator_hours (
        id INTEGER PRIMARY KEY,
        vessel_id INTEGER DEFAULT 1,
        generator_type TEXT NOT NULL,
        total_hours REAL DEFAULT 0,
        hours_since_overhaul REAL DEFAULT 0,
        next_overhaul_hours REAL DEFAULT 8000,
        last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "✅ Generator hours table created successfully!\n\n";
    
    // Check if data already exists
    $count = $pdo->query("SELECT COUNT(*) FROM generator_hours")->fetchColumn();
    
    if ($count == 0) {
        // Insert initial generator hours data
        $generators = [
            ['port_gen', 2450.5, 2450.5, 8000],
            ['center_gen', 3120.8, 3120.8, 8000],
            ['starboard_gen', 2876.2, 2876.2, 8000]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO generator_hours 
            (vessel_id, generator_type, total_hours, hours_since_overhaul, next_overhaul_hours) 
            VALUES (1, ?, ?, ?, ?)");
        
        foreach ($generators as $gen) {
            $stmt->execute($gen);
            echo "✅ Initialized {$gen[0]}: {$gen[1]} total hours, {$gen[2]} since overhaul\n";
        }
        
        echo "\n🎯 Generator hours initialization complete!\n\n";
    } else {
        echo "⚠️  Generator hours data already exists ($count records)\n\n";
    }
    
    // Display current generator hours
    echo "📊 Current Generator Hours:\n";
    echo "=" . str_repeat("=", 50) . "\n";
    
    $stmt = $pdo->query("SELECT generator_type, total_hours, hours_since_overhaul, next_overhaul_hours 
                        FROM generator_hours ORDER BY generator_type");
    $generators = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($generators as $gen) {
        $overhaul_pct = round(($gen['hours_since_overhaul'] / $gen['next_overhaul_hours']) * 100, 1);
        $gen_name = ucwords(str_replace('_', ' ', $gen['generator_type']));
        
        echo sprintf("%-20s | Total: %8.1f hrs | Since Overhaul: %8.1f hrs | Progress: %5.1f%%\n",
            $gen_name, $gen['total_hours'], $gen['hours_since_overhaul'], $overhaul_pct);
    }
    
    echo "\n💡 Generator hours setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up generator hours: " . $e->getMessage() . "\n";
}
