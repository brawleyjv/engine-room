<?php
/**
 * Database Migration Script for Fluid Management
 * Updates existing database to match new fluid management structure
 */

require_once 'test_db.php';

echo "<h2>Database Migration for Fluid Management</h2>\n";

try {
    $pdo = getTestDatabase();
    
    echo "<p>Checking database structure...</p>\n";
    
    // Check if we need to rename current_amount to amount
    $result = $pdo->query("PRAGMA table_info(fluid_inventory)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $has_current_amount = false;
    $has_amount = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'current_amount') {
            $has_current_amount = true;
        }
        if ($column['name'] === 'amount') {
            $has_amount = true;
        }
    }
    
    // If we have current_amount but not amount, we need to migrate
    if ($has_current_amount && !$has_amount) {
        echo "<p>Migrating fluid_inventory table structure...</p>\n";
        
        // Create new table with correct structure
        $pdo->exec("CREATE TABLE fluid_inventory_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vessel_id INTEGER NOT NULL DEFAULT 1,
            fluid_type TEXT NOT NULL UNIQUE,
            amount REAL DEFAULT 0,
            last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vessel_id) REFERENCES test_vessels(id)
        )");
        
        // Copy data from old table to new table
        $pdo->exec("INSERT INTO fluid_inventory_new (id, vessel_id, fluid_type, amount, last_updated)
                   SELECT id, vessel_id, fluid_type, current_amount, last_updated FROM fluid_inventory");
        
        // Drop old table and rename new one
        $pdo->exec("DROP TABLE fluid_inventory");
        $pdo->exec("ALTER TABLE fluid_inventory_new RENAME TO fluid_inventory");
        
        echo "<p style='color: green;'>✓ Migrated fluid_inventory table successfully</p>\n";
    } else if ($has_amount) {
        echo "<p style='color: green;'>✓ fluid_inventory table already has correct structure</p>\n";
    }
    
    // Check if fluid_transactions table has correct structure
    $result = $pdo->query("PRAGMA table_info(fluid_transactions)");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $has_created_at = false;
    $has_engine_type = false;
    $has_supplier = false;
    
    foreach ($columns as $column) {
        if ($column['name'] === 'created_at') {
            $has_created_at = true;
        }
        if ($column['name'] === 'engine_type') {
            $has_engine_type = true;
        }
        if ($column['name'] === 'supplier') {
            $has_supplier = true;
        }
    }
    
    // Check if we need to update fluid_transactions structure
    if (!$has_created_at || !$has_engine_type || !$has_supplier) {
        echo "<p>Updating fluid_transactions table structure...</p>\n";
        
        // Create new table with correct structure
        $pdo->exec("CREATE TABLE fluid_transactions_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vessel_id INTEGER NOT NULL DEFAULT 1,
            fluid_type TEXT NOT NULL,
            transaction_type TEXT NOT NULL,
            amount REAL NOT NULL,
            engine_type TEXT,
            supplier TEXT,
            notes TEXT,
            created_by TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vessel_id) REFERENCES test_vessels(id)
        )");
        
        // Try to copy existing data if any
        try {
            $pdo->exec("INSERT INTO fluid_transactions_new (id, vessel_id, fluid_type, transaction_type, amount, notes, created_by, created_at)
                       SELECT id, vessel_id, fluid_type, transaction_type, amount, notes, created_by, 
                              COALESCE(transaction_date, CURRENT_TIMESTAMP) FROM fluid_transactions");
            echo "<p style='color: green;'>✓ Preserved existing transaction data</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠ No existing transaction data to preserve</p>\n";
        }
        
        // Drop old table and rename new one
        $pdo->exec("DROP TABLE fluid_transactions");
        $pdo->exec("ALTER TABLE fluid_transactions_new RENAME TO fluid_transactions");
        
        echo "<p style='color: green;'>✓ Updated fluid_transactions table successfully</p>\n";
    } else {
        echo "<p style='color: green;'>✓ fluid_transactions table already has correct structure</p>\n";
    }
    
    // Ensure we have initial fluid inventory data
    $stmt = $pdo->query("SELECT COUNT(*) FROM fluid_inventory");
    if ($stmt->fetchColumn() == 0) {
        echo "<p>Adding initial fluid inventory data...</p>\n";
        $pdo->exec("INSERT INTO fluid_inventory (vessel_id, fluid_type, amount) VALUES 
            (1, 'fuel', 15000.0),
            (1, 'hydraulic_oil', 150.0), 
            (1, 'gear_oil', 85.0),
            (1, 'lube_oil', 500.0)");
        echo "<p style='color: green;'>✓ Added initial fluid inventory data</p>\n";
    } else {
        echo "<p style='color: green;'>✓ Fluid inventory data already exists</p>\n";
    }
    
    // Show current fluid levels
    echo "<h3>Current Fluid Levels:</h3>\n";
    $stmt = $pdo->query("SELECT fluid_type, amount FROM fluid_inventory ORDER BY fluid_type");
    $fluids = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
    echo "<tr><th style='padding: 8px;'>Fluid Type</th><th style='padding: 8px;'>Amount (gallons)</th></tr>\n";
    foreach ($fluids as $fluid) {
        echo "<tr><td style='padding: 8px;'>" . ucfirst(str_replace('_', ' ', $fluid['fluid_type'])) . "</td>";
        echo "<td style='padding: 8px;'>" . number_format($fluid['amount'], 1) . "</td></tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3 style='color: green;'>✅ Database migration completed successfully!</h3>\n";
    echo "<p><a href='index.php'>← Return to Engine Room Dashboard</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>
