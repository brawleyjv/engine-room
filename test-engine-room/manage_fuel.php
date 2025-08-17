<?php
/**
 * Fuel Management - Professional Marine Engine Room
 * Dedicated fuel usage and receipt tracking
 */

require_once 'test_db.php';
require_once 'log_helper.php';

$message = '';
$error = '';

// Handle form submission
if ($_POST) {
    try {
        $pdo = getTestDatabase();
        
        if (isset($_POST['record_usage'])) {
            $amount = floatval($_POST['amount']);
            $equipment_type = $_POST['equipment_type'];
            $notes = $_POST['notes'];
            
            if ($amount <= 0) {
                throw new Exception("Usage amount must be greater than 0");
            }
            
            // Check current inventory
            $stmt = $pdo->prepare("SELECT amount FROM fluid_inventory WHERE fluid_type = 'fuel'");
            $stmt->execute();
            $current = $stmt->fetchColumn() ?: 0;
            
            if ($amount > $current) {
                throw new Exception("Insufficient fuel inventory. Current: " . number_format($current, 1) . " gallons");
            }
            
            $pdo->beginTransaction();
            
            // Record usage transaction
            $stmt = $pdo->prepare("INSERT INTO fluid_transactions 
                (fluid_type, transaction_type, amount, engine_type, notes, created_by, created_at) 
                VALUES ('fuel', 'usage', ?, ?, ?, 'Marine Engineer', datetime('now'))");
            $stmt->execute([$amount, $equipment_type, $notes]);
            
            // Update inventory
            $stmt = $pdo->prepare("UPDATE fluid_inventory SET amount = amount - ?, last_updated = datetime('now') WHERE fluid_type = 'fuel'");
            $stmt->execute([$amount]);
            
            // Create logbook entry
            $log_action = "Used {$amount} gallons for {$equipment_type}" . ($notes ? " - {$notes}" : "");
            createFluidLogEntry($pdo, 'fuel', $log_action, $amount, 'Marine Engineer');
            
            $pdo->commit();
            $message = "Fuel usage recorded: " . number_format($amount, 1) . " gallons";
        }
        
        if (isset($_POST['record_receipt'])) {
            $amount = floatval($_POST['amount']);
            $supplier = $_POST['supplier'];
            $notes = $_POST['notes'];
            
            if ($amount <= 0) {
                throw new Exception("Receipt amount must be greater than 0");
            }
            
            $pdo->beginTransaction();
            
            // Record receipt transaction
            $stmt = $pdo->prepare("INSERT INTO fluid_transactions 
                (fluid_type, transaction_type, amount, supplier, notes, created_by, created_at) 
                VALUES ('fuel', 'receipt', ?, ?, ?, 'Marine Engineer', datetime('now'))");
            $stmt->execute([$amount, $supplier, $notes]);
            
            // Update inventory
            $stmt = $pdo->prepare("UPDATE fluid_inventory SET amount = amount + ?, last_updated = datetime('now') WHERE fluid_type = 'fuel'");
            $stmt->execute([$amount]);
            
            // Create logbook entry
            $log_action = "Received {$amount} gallons from {$supplier}" . ($notes ? " - {$notes}" : "");
            createFluidLogEntry($pdo, 'fuel', $log_action, $amount, 'Marine Engineer');
            
            $pdo->commit();
            $message = "Fuel receipt recorded: " . number_format($amount, 1) . " gallons";
        }
        
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Get current fuel level and recent transactions
try {
    $pdo = getTestDatabase();
    
    $stmt = $pdo->prepare("SELECT amount FROM fluid_inventory WHERE fluid_type = 'fuel'");
    $stmt->execute();
    $current_level = $stmt->fetchColumn() ?: 0;
    
    $stmt = $pdo->prepare("SELECT * FROM fluid_transactions WHERE fluid_type = 'fuel' ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $current_level = 0;
    $recent_transactions = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Management - Marine Engine Room</title>
    <link rel="stylesheet" href="test_styles.css">
</head>
<body>
    <div class="header">
        <h1>⛽ Fuel Management</h1>
        <p>Professional fuel inventory tracking and management</p>
        <a href="index.php" style="color: white; text-decoration: none;">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <!-- Current Status Card -->
        <div class="fuel-status-card">
            <h3>Current Fuel Inventory</h3>
            <div class="fuel-amount-display">
                <span class="fuel-amount-large"><?php echo number_format($current_level, 1); ?></span>
                <span class="fuel-unit">gallons</span>
            </div>
            <div class="fuel-status">
                <?php if ($current_level < 1000): ?>
                    <span class="status-critical">🔴 CRITICAL LOW</span>
                    <p style="margin: 10px 0 0 0; font-size: 14px;">Immediate refueling required!</p>
                <?php elseif ($current_level < 5000): ?>
                    <span class="status-warning">🟡 LOW FUEL</span>
                    <p style="margin: 10px 0 0 0; font-size: 14px;">Plan refueling soon</p>
                <?php else: ?>
                    <span class="status-good">🟢 ADEQUATE</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- Record Usage -->
            <div class="form-section">
                <h3>🔽 Record Fuel Usage</h3>
                <form method="POST">
                    <div class="form-group">
                        <label><strong>Amount Used (gallons):</strong></label>
                        <input type="number" name="amount" step="0.1" required placeholder="250.5">
                    </div>
                    
                    <div class="form-group">
                        <label><strong>Engine/Equipment:</strong></label>
                        <select name="equipment_type" required>
                            <option value="">Select Engine/Equipment</option>
                            <option value="port_main">Port Main Engine</option>
                            <option value="starboard_main">Starboard Main Engine</option>
                            <option value="center_main">Center Main Engine</option>
                            <option value="port_generator">Port Generator</option>
                            <option value="starboard_generator">Starboard Generator</option>
                            <option value="fore_generator">Fore Generator</option>
                            <option value="aft_generator">Aft Generator</option>
                            <option value="bow_thruster">Bow Thruster</option>
                            <option value="stern_thruster">Stern Thruster</option>
                            <option value="general_consumption">General Consumption</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Notes:</label>
                        <textarea name="notes" rows="3" placeholder="Daily consumption - port main engine 8 hours runtime"></textarea>
                    </div>
                    
                    <button type="submit" name="record_usage" class="btn" style="background: #e74c3c;">Record Usage</button>
                </form>
            </div>
            
            <!-- Record Receipt -->
            <div class="form-section">
                <h3>🔼 Record Fuel Receipt</h3>
                <form method="POST">
                    <div class="form-group">
                        <label><strong>Amount Received (gallons):</strong></label>
                        <input type="number" name="amount" step="0.1" required placeholder="2000.0">
                    </div>
                    
                    <div class="form-group">
                        <label><strong>Supplier:</strong></label>
                        <input type="text" name="supplier" required placeholder="Marine Fuel Co.">
                    </div>
                    
                    <div class="form-group">
                        <label>Notes:</label>
                        <textarea name="notes" rows="3" placeholder="Fuel delivery - diesel marine fuel, quality tested"></textarea>
                    </div>
                    
                    <button type="submit" name="record_receipt" class="btn btn-success">Record Receipt</button>
                </form>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="form-section">
            <h3>📋 Recent Fuel Transactions</h3>
            <?php if (!empty($recent_transactions)): ?>
                <div class="readings-table-container">
                    <table class="readings-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Engine/Supplier</th>
                                <th>Notes</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $trans): ?>
                                <tr>
                                    <td><?php echo date('M j, Y H:i', strtotime($trans['created_at'])); ?></td>
                                    <td>
                                        <?php if ($trans['transaction_type'] === 'usage'): ?>
                                            <span style="color: #e74c3c;">🔽 Usage</span>
                                        <?php else: ?>
                                            <span style="color: #27ae60;">🔼 Receipt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight: bold;">
                                        <?php echo ($trans['transaction_type'] === 'usage' ? '-' : '+') . number_format($trans['amount'], 1); ?> gal
                                    </td>
                                    <td><?php echo htmlspecialchars($trans['engine_type'] ?: $trans['supplier'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($trans['notes'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($trans['created_by']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #7f8c8d; font-style: italic;">No fuel transactions recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
