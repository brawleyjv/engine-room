<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logicdock_tracker.php';

// Check if user is super admin (only LogicDock staff should access this)
if (!isset($_SESSION['is_super_admin']) || $_SESSION['is_super_admin'] !== true) {
    // For testing, allow access with a special key
    if (!isset($_GET['access_key']) || $_GET['access_key'] !== 'logicdock_admin_2024') {
        die('Access denied. LogicDock administrative access required.');
    }
}

// Connect to master database
try {
    $master_pdo = new PDO("mysql:host=$db_host;dbname=vessellogger_master", $db_user, $db_pass);
    $master_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Master database connection failed: " . $e->getMessage());
}

// Handle actions
if ($_POST['action'] ?? '') {
    switch ($_POST['action']) {
        case 'send_pending_notifications':
            $tracker = new LogicDockTracker($master_pdo);
            $sent_count = $tracker->sendPendingNotifications();
            $success_message = "Sent $sent_count pending notifications to LogicDock.";
            break;
            
        case 'cleanup_expired_trials':
            $cleanup_count = cleanupExpiredTrials($master_pdo);
            $success_message = "Cleaned up $cleanup_count expired trial accounts.";
            break;
            
        case 'generate_daily_stats':
            generateDailyStatistics($master_pdo);
            $success_message = "Daily statistics updated successfully.";
            break;
    }
}

// Get dashboard data
$dashboard_data = getDashboardData($master_pdo);
$recent_activity = getRecentActivity($master_pdo);
$customer_stats = getCustomerStatistics($master_pdo);
$revenue_stats = getRevenueStatistics($master_pdo);
$pending_notifications = getPendingNotifications($master_pdo);

function getDashboardData($pdo) {
    $sql = "SELECT * FROM logicdock_dashboard";
    return $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
}

function getRecentActivity($pdo) {
    $sql = "SELECT 
                lt.*, 
                cl.company_name,
                cl.database_name
            FROM logicdock_tracking lt
            JOIN customer_licenses cl ON lt.customer_id = cl.customer_id
            ORDER BY lt.created_at DESC 
            LIMIT 20";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getCustomerStatistics($pdo) {
    $sql = "SELECT 
                customer_status,
                COUNT(*) as count,
                AVG(days_remaining) as avg_days_remaining,
                SUM(monthly_module_revenue) as total_monthly_revenue
            FROM customer_overview 
            GROUP BY customer_status
            ORDER BY count DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getRevenueStatistics($pdo) {
    $sql = "SELECT 
                DATE(processed_at) as date,
                SUM(amount) as daily_revenue,
                COUNT(*) as transaction_count,
                AVG(amount) as avg_transaction
            FROM revenue_tracking 
            WHERE processed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(processed_at)
            ORDER BY date DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getPendingNotifications($pdo) {
    $sql = "SELECT 
                lt.*,
                cl.company_name,
                cl.database_name
            FROM logicdock_tracking lt
            JOIN customer_licenses cl ON lt.customer_id = cl.customer_id
            WHERE lt.sent_to_logicdock = FALSE
            ORDER BY lt.created_at DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function cleanupExpiredTrials($pdo) {
    // Get expired trials
    $sql = "SELECT customer_id, database_name FROM customer_licenses 
            WHERE plan = 'trial' 
            AND expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND status = 'active'";
    $expired_trials = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    $cleanup_count = 0;
    foreach ($expired_trials as $trial) {
        try {
            // Mark as suspended
            $update_sql = "UPDATE customer_licenses SET status = 'suspended' WHERE customer_id = ?";
            $pdo->prepare($update_sql)->execute([$trial['customer_id']]);
            
            // Log the cleanup
            $tracker = new LogicDockTracker($pdo);
            $tracker->trackEvent($trial['customer_id'], 'account_suspended', [
                'reason' => 'Trial expired over 7 days ago',
                'database_name' => $trial['database_name']
            ]);
            
            $cleanup_count++;
        } catch (Exception $e) {
            error_log("Cleanup failed for customer {$trial['customer_id']}: " . $e->getMessage());
        }
    }
    
    return $cleanup_count;
}

function generateDailyStatistics($pdo) {
    $today = date('Y-m-d');
    
    // Calculate daily stats
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_customers,
            SUM(CASE WHEN plan = 'trial' AND status = 'active' THEN 1 ELSE 0 END) as active_trials,
            SUM(CASE WHEN plan != 'trial' AND status = 'active' THEN 1 ELSE 0 END) as active_subscriptions,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_trials_today,
            SUM(total_log_entries) as total_log_entries_all_time
        FROM customer_licenses
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Get revenue data
    $revenue = $pdo->query("
        SELECT 
            COALESCE(SUM(CASE WHEN DATE(processed_at) = CURDATE() THEN amount ELSE 0 END), 0) as daily_revenue,
            COALESCE(SUM(CASE WHEN transaction_type = 'subscription' THEN amount ELSE 0 END), 0) as monthly_recurring_revenue
        FROM revenue_tracking 
        WHERE DATE(processed_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Insert or update daily stats
    $sql = "INSERT INTO daily_statistics 
            (stat_date, total_customers, active_trials, active_subscriptions, new_trials_today, daily_revenue, monthly_recurring_revenue, total_log_entries_today)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            total_customers = VALUES(total_customers),
            active_trials = VALUES(active_trials),
            active_subscriptions = VALUES(active_subscriptions),
            new_trials_today = VALUES(new_trials_today),
            daily_revenue = VALUES(daily_revenue),
            monthly_recurring_revenue = VALUES(monthly_recurring_revenue),
            total_log_entries_today = VALUES(total_log_entries_today)";
    
    $pdo->prepare($sql)->execute([
        $today,
        $stats['total_customers'],
        $stats['active_trials'],
        $stats['active_subscriptions'],
        $stats['new_trials_today'],
        $revenue['daily_revenue'],
        $revenue['monthly_recurring_revenue'],
        $stats['total_log_entries_all_time']
    ]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogicDock - Vessel Logger SaaS Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            border-bottom: 1px solid #e1e5e9;
            backdrop-filter: blur(10px);
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .header .subtitle {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .stat-card h3 {
            color: #7f8c8d;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .stat-card .change {
            font-size: 0.8rem;
            color: #27ae60;
        }
        
        .stat-card.warning .value {
            color: #e67e22;
        }
        
        .stat-card.danger .value {
            color: #e74c3c;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .panel {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .panel h2 {
            color: #2c3e50;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            text-align: left;
            padding: 0.75rem;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge.active { background: #d4edda; color: #155724; }
        .badge.trial { background: #fff3cd; color: #856404; }
        .badge.expired { background: #f8d7da; color: #721c24; }
        .badge.suspended { background: #f1f3f4; color: #5f6368; }
        
        .actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .chart-container {
            height: 300px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚢 LogicDock - Vessel Logger SaaS Dashboard</h1>
        <div class="subtitle">Real-time monitoring of customer trials, subscriptions, and database activity</div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <!-- Key Metrics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Customers</h3>
                <div class="value"><?= number_format($dashboard_data['total_customers'] ?? 0) ?></div>
                <div class="change">All time signups</div>
            </div>
            
            <div class="stat-card">
                <h3>Active Trials</h3>
                <div class="value"><?= number_format($dashboard_data['active_trials'] ?? 0) ?></div>
                <div class="change">Currently evaluating</div>
            </div>
            
            <div class="stat-card">
                <h3>Paying Customers</h3>
                <div class="value"><?= number_format($dashboard_data['paying_customers'] ?? 0) ?></div>
                <div class="change">Subscription revenue</div>
            </div>
            
            <div class="stat-card <?= ($dashboard_data['signups_today'] ?? 0) > 0 ? '' : 'warning' ?>">
                <h3>Signups Today</h3>
                <div class="value"><?= number_format($dashboard_data['signups_today'] ?? 0) ?></div>
                <div class="change">New trial starts</div>
            </div>
            
            <div class="stat-card <?= ($dashboard_data['expired_trials'] ?? 0) > 0 ? 'danger' : '' ?>">
                <h3>Expired Trials</h3>
                <div class="value"><?= number_format($dashboard_data['expired_trials'] ?? 0) ?></div>
                <div class="change">Need attention</div>
            </div>
            
            <div class="stat-card">
                <h3>Avg Log Entries</h3>
                <div class="value"><?= number_format($dashboard_data['avg_log_entries_per_customer'] ?? 0, 1) ?></div>
                <div class="change">Per customer</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="actions">
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="send_pending_notifications">
                <button type="submit" class="btn btn-primary">
                    📡 Send Pending Notifications (<?= count($pending_notifications) ?>)
                </button>
            </form>
            
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="cleanup_expired_trials">
                <button type="submit" class="btn btn-danger" onclick="return confirm('This will suspend expired trial accounts. Continue?')">
                    🧹 Cleanup Expired Trials
                </button>
            </form>
            
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="generate_daily_stats">
                <button type="submit" class="btn btn-success">
                    📊 Update Daily Statistics
                </button>
            </form>
        </div>

        <div class="content-grid">
            <!-- Recent Activity -->
            <div class="panel">
                <h2>🔄 Recent Customer Activity</h2>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Event</th>
                                <th>Database</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($recent_activity, 0, 10) as $activity): ?>
                                <tr>
                                    <td><?= date('M j, H:i', strtotime($activity['created_at'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($activity['company_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($activity['customer_id']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= str_replace('_', '-', $activity['event_type']) ?>">
                                            <?= ucwords(str_replace('_', ' ', $activity['event_type'])) ?>
                                        </span>
                                    </td>
                                    <td><code><?= htmlspecialchars($activity['database_name'] ?? 'N/A') ?></code></td>
                                    <td>
                                        <?php if ($activity['sent_to_logicdock']): ?>
                                            <span class="badge active">✓ Sent</span>
                                        <?php else: ?>
                                            <span class="badge trial">⏳ Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Customer Status Breakdown -->
            <div class="panel">
                <h2>👥 Customer Status</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Revenue/Mo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customer_stats as $stat): ?>
                            <tr>
                                <td>
                                    <span class="badge <?= strtolower(str_replace('_', '-', $stat['customer_status'])) ?>">
                                        <?= ucwords(str_replace('_', ' ', $stat['customer_status'])) ?>
                                    </span>
                                </td>
                                <td><strong><?= $stat['count'] ?></strong></td>
                                <td>$<?= number_format($stat['total_monthly_revenue'] ?? 0, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Notifications -->
        <?php if (!empty($pending_notifications)): ?>
            <div class="panel">
                <h2>📬 Pending LogicDock Notifications</h2>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Created</th>
                                <th>Customer</th>
                                <th>Event Type</th>
                                <th>Database Name</th>
                                <th>Event Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_notifications as $notification): ?>
                                <tr>
                                    <td><?= date('M j, H:i', strtotime($notification['created_at'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($notification['company_name']) ?></strong><br>
                                        <small><?= htmlspecialchars($notification['customer_id']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= str_replace('_', '-', $notification['event_type']) ?>">
                                            <?= ucwords(str_replace('_', ' ', $notification['event_type'])) ?>
                                        </span>
                                    </td>
                                    <td><code><?= htmlspecialchars($notification['database_name'] ?? 'N/A') ?></code></td>
                                    <td>
                                        <small><?= htmlspecialchars(substr(json_encode(json_decode($notification['event_data'] ?? '{}'), JSON_PRETTY_PRINT), 0, 100)) ?>...</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Revenue Trends -->
        <?php if (!empty($revenue_stats)): ?>
            <div class="panel">
                <h2>💰 Revenue Trends (Last 30 Days)</h2>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Daily Revenue</th>
                                <th>Transactions</th>
                                <th>Avg Transaction</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($revenue_stats, 0, 10) as $stat): ?>
                                <tr>
                                    <td><?= date('M j, Y', strtotime($stat['date'])) ?></td>
                                    <td><strong>$<?= number_format($stat['daily_revenue'], 2) ?></strong></td>
                                    <td><?= $stat['transaction_count'] ?></td>
                                    <td>$<?= number_format($stat['avg_transaction'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            window.location.reload();
        }, 300000);
        
        // Add some visual feedback for button clicks
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 100);
            });
        });
    </script>
</body>
</html>
