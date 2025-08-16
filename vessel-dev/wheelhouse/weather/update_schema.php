<?php
/**
 * Update vessel database schema to include location information
 */

function getDB() {
    try {
        $db = new PDO('sqlite:../data/vessel.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch(PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

try {
    $db = getDB();
    
    // Check if weather_location column exists
    $stmt = $db->prepare("PRAGMA table_info(vessels)");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    $has_location = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'weather_location') {
            $has_location = true;
            break;
        }
    }
    
    if (!$has_location) {
        // Add weather_location column to vessels table
        $db->exec("ALTER TABLE vessels ADD COLUMN weather_location TEXT DEFAULT NULL");
        echo "✅ Added weather_location column to vessels table\n";
    } else {
        echo "ℹ️  weather_location column already exists\n";
    }
    
    // Check if weather_data column exists for caching
    $has_cache = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'weather_data') {
            $has_cache = true;
            break;
        }
    }
    
    if (!$has_cache) {
        // Add weather_data column for caching weather information
        $db->exec("ALTER TABLE vessels ADD COLUMN weather_data TEXT DEFAULT NULL");
        $db->exec("ALTER TABLE vessels ADD COLUMN weather_updated DATETIME DEFAULT NULL");
        echo "✅ Added weather caching columns to vessels table\n";
    } else {
        echo "ℹ️  weather caching columns already exist\n";
    }
    
    echo "✅ Database schema update completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error updating database: " . $e->getMessage() . "\n";
}
?>
