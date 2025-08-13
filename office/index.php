<?php
session_start();
require_once __DIR__ . '/../config_saas.php';
require_once __DIR__ . '/../auth_functions_enhanced.php';
require_once __DIR__ . '/../vessel_functions.php';

// Check if user is logged in with SaaS session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
    header('Location: ' . BASE_URL . '/login_enhanced.php');
    exit;
}

// Get user and company info from SaaS session
$user_id = $_SESSION['user_id'];
$company_id = $_SESSION['company_id'];
$company_name = $_SESSION['company_name'];
$company_domain = $_SESSION['company_domain'];
$subscription_plan = $_SESSION['subscription_plan'] ?? 'trial';

// Initialize database connection for this company
$conn = initializeCompanyDatabase();
if (!$conn) {
    die('Failed to connect to company database');
}

// Check if this is a trial account
$is_trial = ($subscription_plan === 'trial');
$trial_end = null;
$days_remaining = null;

// Get trial info if applicable
if ($is_trial) {
    // You can add trial end date logic here if needed
    $days_remaining = 30; // Default for now
}

// Get dashboard data
$dashboard_stats = getDashboardStats($conn);
$recent_activity = getRecentActivity($conn);
$vessel_overview = getVesselOverview($conn);
$alerts = getSystemAlerts($conn);
$quick_stats = getQuickStats($conn);

function getDashboardStats($conn) {
    // Get basic statistics for the dashboard
    $stats = [];
    
    // Total vessels
    $result = $conn->query("SELECT COUNT(*) as count FROM vessels WHERE IsActive = 1");
    $stats['total_vessels'] = $result->fetch_assoc()['count'];
    
    // Total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE IsActive = 1");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    // Recent log entries (last 7 days)
    $result = $conn->query("SELECT COUNT(*) as count FROM mainengines WHERE EntryDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['recent_entries'] = $result->fetch_assoc()['count'];
    
    // Active vessels (had log entries in last 30 days)
    $result = $conn->query("
        SELECT COUNT(DISTINCT VesselID) as count 
        FROM mainengines 
        WHERE EntryDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats['active_vessels'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

function getRecentActivity($conn) {
    // Get recent log entries with vessel names
    $sql = "SELECT 
                me.EntryDate,
                me.Side,
                me.RPM,
                me.MainHrs,
                v.VesselName,
                u.FirstName,
                u.LastName
            FROM mainengines me
            LEFT JOIN vessels v ON me.VesselID = v.VesselID
            LEFT JOIN users u ON me.CreatedBy = u.UserID
            ORDER BY me.EntryDate DESC, me.CreatedDate DESC
            LIMIT 10";
    
    $result = $conn->query($sql);
    $activity = [];
    
    while ($row = $result->fetch_assoc()) {
        $activity[] = $row;
    }
    
    return $activity;
}

function getVesselOverview($conn) {
    // Get overview of all vessels
    $sql = "SELECT 
                v.VesselID,
                v.VesselName,
                v.VesselType,
                v.EngineConfig,
                COUNT(me.EntryID) as total_entries,
                MAX(me.EntryDate) as last_entry,
                MAX(me.MainHrs) as latest_hours
            FROM vessels v
            LEFT JOIN mainengines me ON v.VesselID = me.VesselID
            WHERE v.IsActive = 1
            GROUP BY v.VesselID
            ORDER BY v.VesselName";
    
    $result = $conn->query($sql);
    $vessels = [];
    
    while ($row = $result->fetch_assoc()) {
        $vessels[] = $row;
    }
    
    return $vessels;
}

function getSystemAlerts($conn) {
    $alerts = [];
    
    // Check for vessels without recent entries
    $sql = "SELECT v.VesselName 
            FROM vessels v
            LEFT JOIN mainengines me ON v.VesselID = me.VesselID AND me.EntryDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            WHERE v.IsActive = 1 AND me.VesselID IS NULL";
    
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $alerts[] = [
            'type' => 'warning',
            'message' => "No entries for {$row['VesselName']} in the last 7 days",
            'action' => 'add_log.php'
        ];
    }
    
    // Check for trial expiration
    global $is_trial, $days_remaining;
    if ($is_trial && $days_remaining !== null) {
        if ($days_remaining <= 3) {
            $alerts[] = [
                'type' => 'danger',
                'message' => "Trial expires in {$days_remaining} days. Upgrade to continue service.",
                'action' => 'subscription.php'
            ];
        } elseif ($days_remaining <= 7) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Trial expires in {$days_remaining} days. Consider upgrading soon.",
                'action' => 'subscription.php'
            ];
        }
    }
    
    return $alerts;
}

function getQuickStats($conn) {
    $stats = [];
    
    // Today's entries
    $result = $conn->query("SELECT COUNT(*) as count FROM mainengines WHERE DATE(EntryDate) = CURDATE()");
    $stats['today_entries'] = $result->fetch_assoc()['count'];
    
    // This week's entries
    $result = $conn->query("SELECT COUNT(*) as count FROM mainengines WHERE EntryDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['week_entries'] = $result->fetch_assoc()['count'];
    
    // Average daily entries (last 30 days)
    $result = $conn->query("
        SELECT COUNT(*) / 30 as avg_daily 
        FROM mainengines 
        WHERE EntryDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stats['avg_daily'] = round($result->fetch_assoc()['avg_daily'], 1);
    
    return $stats;
}

function getUserInfo($conn, $user_id) {
    $sql = "SELECT FirstName, LastName, Email, IsAdmin FROM users WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($company_name) ?> - Master Dashboard</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .header .company-info {
            text-align: right;
        }
        
        .header .company-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .header .user-info {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-top: 0.25rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .trial-banner {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .trial-banner.warning {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .trial-info h3 {
            margin-bottom: 0.5rem;
        }
        
        .upgrade-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.75rem 1.5rem;
            border: 2px solid white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .upgrade-btn:hover {
            background: white;
            color: #333;
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
            text-align: center;
        }
        
        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card .value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .label {
            color: #7f8c8d;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-btn {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 12px;
            padding: 1.5rem;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            color: #2c3e50;
        }
        
        .action-btn .icon {
            font-size: 2rem;
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
        
        .badge.success { background: #d4edda; color: #155724; }
        .badge.warning { background: #fff3cd; color: #856404; }
        .badge.danger { background: #f8d7da; color: #721c24; }
        .badge.info { background: #d1ecf1; color: #0c5460; }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .alert.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert.danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-btn {
            background: rgba(0, 0, 0, 0.1);
            color: inherit;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .alert-btn:hover {
            background: rgba(0, 0, 0, 0.2);
        }
        
        .vessel-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #3498db;
        }
        
        .vessel-card h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .vessel-meta {
            font-size: 0.85rem;
            color: #666;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-menu {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 0.5rem;
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .nav-menu a.active {
            background: rgba(255, 255, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .trial-banner {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .container {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏢 Master Dashboard</h1>
        <div class="company-info">
            <div class="company-name"><?= htmlspecialchars($company_name) ?></div>
            <div class="user-info">
                Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>
                | <a href="<?php echo BASE_URL; ?>/logout.php" style="color: #e74c3c;">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Navigation Menu -->
        <div class="nav-menu">
            <a href="<?php echo BASE_URL; ?>/office/index.php" class="active">🏢 Master Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/vessel/engineroom/dashboard.php">⚙️ Vessel Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/vessel/engineroom/add_log.php">📝 Add Entry</a>
            <a href="<?php echo BASE_URL; ?>/vessel/engineroom/view_logs.php">📋 View Logs</a>
            <a href="<?php echo BASE_URL; ?>/manage_vessels.php">🚢 Vessels</a>
            <a href="<?php echo BASE_URL; ?>/manage_users.php">👥 Users</a>
            <a href="<?php echo BASE_URL; ?>/vessel/engineroom/graph_logs.php">📈 Reports</a>
        </div>

        <!-- Trial Banner -->
        <?php if ($is_trial && $days_remaining !== null): ?>
            <div class="trial-banner <?= $days_remaining <= 3 ? 'warning' : '' ?>">
                <div class="trial-info">
                    <h3>⏰ Trial Account</h3>
                    <p><?= $days_remaining ?> days remaining in your free trial</p>
                    <p><small>Customer ID: <?= htmlspecialchars($customer_id) ?></small></p>
                </div>
                <a href="<?php echo BASE_URL; ?>/subscription.php" class="upgrade-btn">Upgrade Now</a>
            </div>
        <?php endif; ?>

        <!-- System Alerts -->
        <?php if (!empty($alerts)): ?>
            <?php foreach ($alerts as $alert): ?>
                <div class="alert <?= $alert['type'] ?>">
                    <span><?= htmlspecialchars($alert['message']) ?></span>
                    <a href="<?= htmlspecialchars($alert['action']) ?>" class="alert-btn">Take Action</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Key Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">🚢</div>
                <div class="value"><?= $dashboard_stats['total_vessels'] ?></div>
                <div class="label">Active Vessels</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">👥</div>
                <div class="value"><?= $dashboard_stats['total_users'] ?></div>
                <div class="label">System Users</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">📊</div>
                <div class="value"><?= $quick_stats['today_entries'] ?></div>
                <div class="label">Today's Entries</div>
            </div>
            
            <div class="stat-card">
                <div class="icon">📈</div>
                <div class="value"><?= $quick_stats['week_entries'] ?></div>
                <div class="label">This Week</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="<?php echo BASE_URL; ?>/vessel/engineroom/dashboard.php" class="action-btn">
                <span class="icon">⚙️</span>
                <span>Vessel Dashboard</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/vessel/engineroom/add_log.php" class="action-btn">
                <span class="icon">📝</span>
                <span>Add Log Entry</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/vessel/engineroom/view_logs.php" class="action-btn">
                <span class="icon">📋</span>
                <span>View All Logs</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/manage_vessels.php" class="action-btn">
                <span class="icon">🚢</span>
                <span>Manage Vessels</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/manage_users.php" class="action-btn">
                <span class="icon">👥</span>
                <span>User Management</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/vessel/engineroom/graph_logs.php" class="action-btn">
                <span class="icon">📈</span>
                <span>Reports & Analytics</span>
            </a>
            
            <?php if ($is_trial): ?>
            <a href="<?php echo BASE_URL; ?>/subscription.php" class="action-btn" style="background: linear-gradient(135deg, #e74c3c, #c0392b); color: white;">
                <span class="icon">⭐</span>
                <span>Upgrade Account</span>
            </a>
            <?php endif; ?>
        </div>

        <div class="content-grid">
            <!-- Recent Activity -->
            <div class="panel">
                <h2>📊 Recent Activity Across All Vessels</h2>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Vessel</th>
                                <th>Side</th>
                                <th>RPM</th>
                                <th>Hours</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_activity)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #666; padding: 2rem;">
                                        No log entries yet. <a href="<?php echo BASE_URL; ?>/vessel/engineroom/add_log.php">Add your first entry</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_activity as $activity): ?>
                                    <tr>
                                        <td><?= date('M j, Y', strtotime($activity['EntryDate'])) ?></td>
                                        <td><?= htmlspecialchars($activity['VesselName']) ?></td>
                                        <td><span class="badge info"><?= htmlspecialchars($activity['Side']) ?></span></td>
                                        <td><?= number_format($activity['RPM']) ?></td>
                                        <td><?= number_format($activity['MainHrs'], 1) ?></td>
                                        <td><?= htmlspecialchars($activity['FirstName'] . ' ' . $activity['LastName']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!empty($recent_activity)): ?>
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="<?php echo BASE_URL; ?>/vessel/engineroom/view_logs.php" class="action-btn" style="display: inline-flex; padding: 0.75rem 1.5rem;">
                            View All Entries →
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Fleet Overview -->
            <div class="panel">
                <h2>🚢 Fleet Overview</h2>
                <?php if (empty($vessel_overview)): ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <p>No vessels configured.</p>
                        <a href="<?php echo BASE_URL; ?>/manage_vessels.php" style="color: #3498db;">Add your first vessel</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($vessel_overview as $vessel): ?>
                        <div class="vessel-card">
                            <h4><?= htmlspecialchars($vessel['VesselName']) ?></h4>
                            <div class="vessel-meta">
                                <span><?= htmlspecialchars($vessel['VesselType']) ?> • <?= htmlspecialchars($vessel['EngineConfig']) ?></span>
                                <span class="badge <?= $vessel['last_entry'] && strtotime($vessel['last_entry']) > strtotime('-7 days') ? 'success' : 'warning' ?>">
                                    <?= $vessel['total_entries'] ?> entries
                                </span>
                            </div>
                            <?php if ($vessel['last_entry']): ?>
                                <div style="font-size: 0.8rem; color: #666; margin-top: 0.5rem;">
                                    Last entry: <?= date('M j, Y', strtotime($vessel['last_entry'])) ?>
                                    <?php if ($vessel['latest_hours']): ?>
                                        • <?= number_format($vessel['latest_hours'], 1) ?> hours
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div style="font-size: 0.8rem; color: #e74c3c; margin-top: 0.5rem;">
                                    No entries yet
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="<?php echo BASE_URL; ?>/manage_vessels.php" class="action-btn" style="display: inline-flex; padding: 0.75rem 1.5rem;">
                            Manage Vessels →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Performance Summary -->
        <div class="panel">
            <h2>📈 Business Performance Summary</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="value"><?= $quick_stats['avg_daily'] ?></div>
                    <div class="label">Avg Daily Entries</div>
                </div>
                
                <div class="stat-card">
                    <div class="value"><?= $dashboard_stats['active_vessels'] ?></div>
                    <div class="label">Active Vessels (30d)</div>
                </div>
                
                <div class="stat-card">
                    <div class="value"><?= date('M j') ?></div>
                    <div class="label">Today's Date</div>
                </div>
                
                <div class="stat-card">
                    <div class="value"><?= defined('VMS_VERSION') ? VMS_VERSION : '1.0' ?></div>
                    <div class="label">System Version</div>
                </div>
            </div>
        </div>

        <!-- Company Information Panel -->
        <div class="panel">
            <h2>🏢 Company Information</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                    <h4 style="color: #2c3e50; margin-bottom: 0.5rem;">Account Status</h4>
                    <p><strong>Company:</strong> <?= htmlspecialchars($company_name) ?></p>
                    <p><strong>Customer ID:</strong> <?= htmlspecialchars($customer_id) ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge <?= $is_trial ? 'warning' : 'success' ?>">
                            <?= $is_trial ? 'Trial' : 'Active' ?>
                        </span>
                    </p>
                    <?php if ($is_trial && $trial_end): ?>
                        <p><strong>Trial Ends:</strong> <?= date('M j, Y', strtotime($trial_end)) ?></p>
                    <?php endif; ?>
                </div>
                
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                    <h4 style="color: #2c3e50; margin-bottom: 0.5rem;">System Summary</h4>
                    <p><strong>Total Vessels:</strong> <?= $dashboard_stats['total_vessels'] ?></p>
                    <p><strong>Total Users:</strong> <?= $dashboard_stats['total_users'] ?></p>
                    <p><strong>Database:</strong> <?= defined('DB_NAME') ? DB_NAME : 'Unknown' ?></p>
                    <p><strong>Installed:</strong> <?= date('M j, Y') ?></p>
                </div>
                
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                    <h4 style="color: #2c3e50; margin-bottom: 0.5rem;">Quick Links</h4>
                    <p><a href="<?php echo BASE_URL; ?>/subscription.php">Subscription Management</a></p>
                    <p><a href="<?php echo BASE_URL; ?>/manage_users.php">User Administration</a></p>
                    <p><a href="<?php echo BASE_URL; ?>/manage_vessels.php">Fleet Management</a></p>
                    <p><a href="<?php echo BASE_URL; ?>/vessel/engineroom/graph_logs.php">Analytics & Reports</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh dashboard every 10 minutes
        setTimeout(function() {
            window.location.reload();
        }, 600000);
        
        // Add visual feedback for action buttons
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 100);
            });
        });
        
        // Highlight today's entries
        const today = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        document.querySelectorAll('.table tbody tr').forEach(row => {
            const dateCell = row.querySelector('td');
            if (dateCell && dateCell.textContent.includes(today.replace(',', ''))) {
                row.style.background = '#e8f5e8';
                row.style.border = '1px solid #28a745';
            }
        });
        
        // Add loading state for navigation
        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                if (this.href && !this.href.includes('#')) {
                    this.style.opacity = '0.7';
                    this.innerHTML += ' <small>(Loading...)</small>';
                }
            });
        });
    </script>
</body>
</html>
