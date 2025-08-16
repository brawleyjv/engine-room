<?php
/**
 * Database Status Check for Fluid Management
 * Quick verification of database structure and data
 */

require_once 'test_db.php';

try {
    $pdo = getTestDatabase();
    
    echo "<h2>🔧 Database Status Check</h2>\n";
    
    // Check fluid inventory
    echo "<h3>📊 Current Fluid Inventory:</h3>\n";
    $stmt = $pdo->query("SELECT fluid_type, amount, last_updated FROM fluid_inventory ORDER BY fluid_type");
    $fluids = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($fluids)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th style='padding: 8px;'>Fluid</th><th style='padding: 8px;'>Amount (gal)</th><th style='padding: 8px;'>Last Updated</th></tr>\n";
        foreach ($fluids as $fluid) {
            $status = '';
            $amount = $fluid['amount'];
            
            // Add status indicators
            switch ($fluid['fluid_type']) {
                case 'fuel':
                    $status = $amount < 1000 ? '🔴 CRITICAL' : ($amount < 5000 ? '🟡 LOW' : '🟢 GOOD');
                    break;
                case 'lube_oil':
                    $status = $amount < 100 ? '🟡 LOW' : '🟢 GOOD';
                    break;
                case 'gear_oil':
                    $status = $amount < 20 ? '🟡 LOW' : '🟢 GOOD';
                    break;
                case 'hydraulic_oil':
                    $status = $amount < 50 ? '🟡 LOW' : '🟢 GOOD';
                    break;
            }
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . ucfirst(str_replace('_', ' ', $fluid['fluid_type'])) . "</td>";
            echo "<td style='padding: 8px; font-weight: bold;'>" . number_format($amount, 1) . "</td>";
            echo "<td style='padding: 8px;'>" . date('M j, H:i', strtotime($fluid['last_updated'])) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p style='color: red;'>❌ No fluid inventory data found!</p>\n";
    }
    
    // Check recent transactions
    echo "<h3>📋 Recent Fluid Transactions (Last 5):</h3>\n";
    $stmt = $pdo->query("SELECT * FROM fluid_transactions ORDER BY created_at DESC LIMIT 5");
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($transactions)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
        echo "<tr><th style='padding: 8px;'>Date</th><th style='padding: 8px;'>Fluid</th><th style='padding: 8px;'>Type</th><th style='padding: 8px;'>Amount</th><th style='padding: 8px;'>Notes</th></tr>\n";
        foreach ($transactions as $trans) {
            $type_icon = $trans['transaction_type'] === 'usage' ? '🔽' : '🔼';
            $amount_sign = $trans['transaction_type'] === 'usage' ? '-' : '+';
            
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . date('M j, H:i', strtotime($trans['created_at'])) . "</td>";
            echo "<td style='padding: 8px;'>" . ucfirst(str_replace('_', ' ', $trans['fluid_type'])) . "</td>";
            echo "<td style='padding: 8px;'>{$type_icon} " . ucfirst($trans['transaction_type']) . "</td>";
            echo "<td style='padding: 8px; font-weight: bold;'>{$amount_sign}" . number_format($trans['amount'], 1) . " gal</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($trans['notes'] ?: 'N/A') . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p style='color: orange;'>⚠ No transactions recorded yet</p>\n";
    }
    
    // Check database table structure
    echo "<h3>🗃️ Database Tables:</h3>\n";
    $tables = ['fluid_inventory', 'fluid_transactions', 'engine_readings', 'gearbox_readings', 'generator_readings'];
    
    echo "<ul>\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<li><strong>$table</strong>: $count records</li>\n";
    }
    echo "</ul>\n";
    
    echo "<h3 style='color: green;'>✅ Database Status: HEALTHY</h3>\n";
    echo "<p><a href='index.php'>← Return to Engine Room Dashboard</a></p>\n";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Database Error</h3>\n";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
