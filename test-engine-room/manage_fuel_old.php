<?php
/**
 * Fuel Management Page
 * Handles fuel transactions with automatic calculations
 */

require_once 'test_db.php';

$message = '';
$pdo = getTestDatabase();

// Get current fuel level
$current_fuel = $pdo->query("SELECT amount FROM fluid_inventory WHERE vessel_id = 1 AND fluid_type = 'fuel'")->fetchColumn();
if (!$current_fuel) {
    $current_fuel = 0;
}

// Handle form submission
if ($_POST) {
    try {
        if (isset($_POST['record_usage'])) {
            $amount_used = floatval($_POST['amount_used']);
            $notes = $_POST['notes'];
            
            if ($amount_used > 0) {
                $new_level = $current_fuel - $amount_used;
                
                if ($new_level < 0) {
                    $message = "Error: Cannot use more fuel than available on board!";
                } else {
                    // Record the transaction
                    $stmt = $pdo->prepare("INSERT INTO fluid_transactions 
                        (vessel_id, fluid_type, transaction_type, amount, amount_before, amount_after, notes, created_by) 
                        VALUES (1, 'fuel', 'used', ?, ?, ?, ?, 'Marine Engineer')");
                    $stmt->execute([$amount_used, $current_fuel, $new_level, $notes]);
                    
                    // Update current inventory
                    $pdo->prepare("UPDATE fluid_inventory SET amount = ?, last_updated = CURRENT_TIMESTAMP WHERE vessel_id = 1 AND fluid_type = 'fuel'")->execute([$new_level]);
                    
                    $current_fuel = $new_level;
                    $message = "Fuel usage recorded! New level: " . number_format($new_level, 1) . " gallons";
                }
            }
        }
        
        if (isset($_POST['record_receipt'])) {
            $amount_received = floatval($_POST['amount_received']);
            $notes = $_POST['notes'];
            
            if ($amount_received > 0) {
                $new_level = $current_fuel + $amount_received;
                
                // Record the transaction
                $stmt = $pdo->prepare("INSERT INTO fluid_transactions 
                    (vessel_id, fluid_type, transaction_type, amount, amount_before, amount_after, notes, created_by) 
                    VALUES (1, 'fuel', 'received', ?, ?, ?, ?, 'Marine Engineer')");
                $stmt->execute([$amount_received, $current_fuel, $new_level, $notes]);
                
                // Update current inventory
                $pdo->prepare("UPDATE fluid_inventory SET amount = ?, last_updated = CURRENT_TIMESTAMP WHERE vessel_id = 1 AND fluid_type = 'fuel'")->execute([$new_level]);
                
                $current_fuel = $new_level;
                $message = "Fuel receipt recorded! New level: " . number_format($new_level, 1) . " gallons";
            }
        }
        
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Get recent fuel transactions
$transactions = $pdo->query("SELECT * FROM fluid_transactions WHERE vessel_id = 1 AND fluid_type = 'fuel' ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);

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
        <h1>⛽ Fuel Management - TEST DEMO</h1>
        <p>Record fuel usage and receipts with automatic inventory calculations</p>
    </div>
    
    <div class="container">
        <div class="nav-links" style="margin-bottom: 20px;">
            <a href="index.php" class="nav-link">← Back to Dashboard</a>
            <a href="manage_lube_oil.php" class="nav-link">Lube Oil</a>
            <a href="manage_gear_oil.php" class="nav-link">Gear Oil</a>
            <a href="manage_hydraulic_oil.php" class="nav-link">Hydraulic Oil</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <!-- Current Fuel Level Display -->
        <div class="fuel-status-card">
            <h3>🛢️ Current Fuel On Board</h3>
            <div class="current-level"><?php echo number_format($current_fuel, 1); ?> gallons</div>
            <div class="level-status">
                <?php if ($current_fuel < 1000): ?>
                    <span style="color: red;">⚠️ LOW FUEL WARNING</span>
                <?php elseif ($current_fuel < 5000): ?>
                    <span style="color: orange;">⚠️ Fuel getting low</span>
                <?php else: ?>
                    <span style="color: green;">✅ Fuel level OK</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="fuel-management-grid">
            <!-- Record Fuel Usage -->
            <div class="form-section">
                <h3>📉 Record Fuel Usage</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Fuel Used (Gallons):</label>
                        <input type="number" name="amount_used" step="0.1" min="0.1" max="<?php echo $current_fuel; ?>" required placeholder="850.5">
                        <small>Maximum available: <?php echo number_format($current_fuel, 1); ?> gallons</small>
                    </div>
                    <div class="form-group">
                        <label>Notes:</label>
                        <textarea name="notes" rows="2" placeholder="Daily consumption, engine hours, weather conditions..."></textarea>
                    </div>
                    <button type="submit" name="record_usage" class="btn" style="background: #e74c3c;">Record Usage</button>
                </form>
            </div>
            
            <!-- Record Fuel Receipt -->
            <div class="form-section">
                <h3>📈 Record Fuel Receipt</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Fuel Received (Gallons):</label>
                        <input type="number" name="amount_received" step="0.1" min="0.1" required placeholder="5000.0">
                        <small>Enter amount of fuel taken on board</small>
                    </div>
                    <div class="form-group">
                        <label>Notes:</label>
                        <textarea name="notes" rows="2" placeholder="Fuel dock location, price per gallon, supplier..."></textarea>
                    </div>
                    <button type="submit" name="record_receipt" class="btn" style="background: #27ae60;">Record Receipt</button>
                </form>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="form-section">
            <h3>📋 Recent Fuel Transactions</h3>
            <table class="readings-table">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Before</th>
                        <th>After</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="6" style="text-align: center; color: #666;">No fuel transactions yet</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $trans): ?>
                            <tr>
                                <td><?php echo date('M j, Y H:i', strtotime($trans['transaction_date'])); ?></td>
                                <td>
                                    <span style="color: <?php echo $trans['transaction_type'] == 'used' ? '#e74c3c' : '#27ae60'; ?>">
                                        <?php echo $trans['transaction_type'] == 'used' ? '📉 Used' : '📈 Received'; ?>
                                    </span>
                                </td>
                                <td style="font-weight: bold;"><?php echo number_format($trans['amount'], 1); ?> gal</td>
                                <td><?php echo number_format($trans['amount_before'], 1); ?> gal</td>
                                <td><?php echo number_format($trans['amount_after'], 1); ?> gal</td>
                                <td><?php echo htmlspecialchars($trans['notes']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #e8f4f8; border-radius: 8px;">
            <h4>⛽ Fuel Management Features:</h4>
            <ul>
                <li><strong>Automatic Calculations:</strong> System calculates new fuel levels automatically</li>
                <li><strong>Usage Validation:</strong> Cannot use more fuel than available</li>
                <li><strong>Transaction Logging:</strong> Every fuel movement is recorded with before/after amounts</li>
                <li><strong>Low Fuel Alerts:</strong> Visual warnings when fuel gets low</li>
                <li><strong>Detailed History:</strong> Complete audit trail of all fuel transactions</li>
            </ul>
            <p><strong>This is how each fluid type would work:</strong> Fuel (daily usage), Lube Oil (periodic changes), Gear Oil (rare usage), Hydraulic Oil (occasional top-offs).</p>
        </div>
    </div>
</body>
</html>
