<?php
require_once __DIR__ . '/../config_installed.php';
require_once __DIR__ . '/../auth_functions.php';
require_once __DIR__ . '/../license_manager.php';

require_login();

// Check if user has admin access
$user = get_logged_in_user();
if ($user['role'] !== 'admin') {
    header('Location: ../office/');
    exit;
}

$license = new LicenseManager($conn, CUSTOMER_ID);
$license_status = $license->getLicenseStatus();

// Get system statistics
$stats = [];

// User count
$user_query = "SELECT COUNT(*) as user_count FROM users WHERE customer_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param('s', CUSTOMER_ID);
$stmt->execute();
$stats['users'] = $stmt->get_result()->fetch_assoc()['user_count'];

// Vessel count
$vessel_query = "SELECT COUNT(*) as vessel_count FROM vessels WHERE customer_id = ?";
$stmt = $conn->prepare($vessel_query);
$stmt->bind_param('s', CUSTOMER_ID);
$stmt->execute();
$stats['vessels'] = $stmt->get_result()->fetch_assoc()['vessel_count'];

// Log entries today
$log_query = "SELECT COUNT(*) as log_count FROM engine_logs el 
              JOIN vessels v ON el.VesselID = v.VesselID 
              WHERE v.customer_id = ? AND el.LogDate = CURDATE()";
$stmt = $conn->prepare($log_query);
$stmt->bind_param('s', CUSTOMER_ID);
$stmt->execute();
$stats['logs_today'] = $stmt->get_result()->fetch_assoc()['log_count'];

// Total log entries
$total_log_query = "SELECT COUNT(*) as total_logs FROM engine_logs el 
                    JOIN vessels v ON el.VesselID = v.VesselID 
                    WHERE v.customer_id = ?";
$stmt = $conn->prepare($total_log_query);
$stmt->bind_param('s', CUSTOMER_ID);
$stmt->execute();
$stats['total_logs'] = $stmt->get_result()->fetch_assoc()['total_logs'];

// Recent users
$recent_users_query = "SELECT * FROM users WHERE customer_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($recent_users_query);
$stmt->bind_param('s', CUSTOMER_ID);
$stmt->execute();
$recent_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// System health checks
$health_checks = [
    'database' => true,
    'license' => $license_status['status'] === 'active',
    'modules' => count($license_status['active_modules']) > 0,
    'vessels' => $stats['vessels'] > 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars(COMPANY_NAME); ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .health-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .health-good { background: #28a745; }
        .health-warning { background: #ffc107; }
        .health-bad { background: #dc3545; }
        .admin-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php show_license_banner(); ?>
    
    <div class="container">
        <header style="background: #6c757d; color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>⚙️ System Administration</h1>
                    <p><?php echo htmlspecialchars(COMPANY_NAME); ?> - Platform Management</p>
                </div>
                <div style="text-align: right;">
                    <p>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></p>
                    <p><small>System Administrator</small></p>
                    <div>
                        <a href="../office/" style="color: white; text-decoration: underline; margin-right: 10px;">Office View</a>
                        <a href="../logout.php" style="color: white; text-decoration: underline;">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- System Overview -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: #e3f2fd; padding: 20px; border-radius: 10px;">
                <h3>👥 Users</h3>
                <div style="font-size: 28px; font-weight: bold; color: #1976d2;">
                    <?php echo $stats['users']; ?>
                </div>
                <p>Active accounts</p>
            </div>
            
            <div style="background: #e8f5e8; padding: 20px; border-radius: 10px;">
                <h3>🚢 Vessels</h3>
                <div style="font-size: 28px; font-weight: bold; color: #2e7d32;">
                    <?php echo $stats['vessels']; ?> / <?php echo $license_status['vessel_limit']; ?>
                </div>
                <p>Fleet size</p>
            </div>
            
            <div style="background: #fff3e0; padding: 20px; border-radius: 10px;">
                <h3>📊 Logs Today</h3>
                <div style="font-size: 28px; font-weight: bold; color: #f57c00;">
                    <?php echo number_format($stats['logs_today']); ?>
                </div>
                <p>Data entries</p>
            </div>
            
            <div style="background: #f3e5f5; padding: 20px; border-radius: 10px;">
                <h3>📈 Total Data</h3>
                <div style="font-size: 28px; font-weight: bold; color: #8e24aa;">
                    <?php echo number_format($stats['total_logs']); ?>
                </div>
                <p>All-time logs</p>
            </div>
        </div>

        <!-- System Health -->
        <div class="admin-card">
            <h2>System Health</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4>
                        <span class="health-indicator <?php echo $health_checks['database'] ? 'health-good' : 'health-bad'; ?>"></span>
                        Database Connection
                    </h4>
                    <p><?php echo $health_checks['database'] ? 'Connected and operational' : 'Connection issues detected'; ?></p>
                </div>
                
                <div>
                    <h4>
                        <span class="health-indicator <?php echo $health_checks['license'] ? 'health-good' : 'health-bad'; ?>"></span>
                        License Status
                    </h4>
                    <p><?php echo ucfirst($license_status['status']); ?> - Expires <?php echo date('M j, Y', strtotime($license_status['expires_at'])); ?></p>
                </div>
                
                <div>
                    <h4>
                        <span class="health-indicator <?php echo $health_checks['modules'] ? 'health-good' : 'health-warning'; ?>"></span>
                        Active Modules
                    </h4>
                    <p><?php echo count($license_status['active_modules']); ?> modules enabled</p>
                </div>
                
                <div>
                    <h4>
                        <span class="health-indicator <?php echo $health_checks['vessels'] ? 'health-good' : 'health-warning'; ?>"></span>
                        Fleet Setup
                    </h4>
                    <p><?php echo $stats['vessels']; ?> vessels configured</p>
                </div>
            </div>
        </div>

        <!-- Quick Management -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <a href="../manage_users.php" style="background: #007cba; color: white; padding: 25px; text-decoration: none; border-radius: 10px; text-align: center;">
                <h3>👥 Manage Users</h3>
                <p>Add, edit, and remove user accounts</p>
                <small>Current: <?php echo $stats['users']; ?> users</small>
            </a>
            
            <a href="../manage_vessels.php" style="background: #28a745; color: white; padding: 25px; text-decoration: none; border-radius: 10px; text-align: center;">
                <h3>🚢 Manage Vessels</h3>
                <p>Configure fleet and vessel settings</p>
                <small>Current: <?php echo $stats['vessels']; ?>/<?php echo $license_status['vessel_limit']; ?> vessels</small>
            </a>
            
            <a href="../subscription.php" style="background: #6c757d; color: white; padding: 25px; text-decoration: none; border-radius: 10px; text-align: center;">
                <h3>💳 Subscription</h3>
                <p>Billing and module management</p>
                <small>Status: <?php echo ucfirst($license_status['status']); ?></small>
            </a>
            
            <a href="../system_logs.php" style="background: #dc3545; color: white; padding: 25px; text-decoration: none; border-radius: 10px; text-align: center;">
                <h3>📋 System Logs</h3>
                <p>View system events and errors</p>
                <small>Monitor system activity</small>
            </a>
        </div>

        <!-- Recent Activity -->
        <div class="admin-card">
            <h2>Recent User Activity</h2>
            <?php if (empty($recent_users)): ?>
                <p style="color: #666; font-style: italic;">No recent user activity.</p>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8f9fa;">
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">User</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Email</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Role</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Created</th>
                            <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Last Login</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $recent_user): ?>
                            <tr style="border-bottom: 1px solid #dee2e6;">
                                <td style="padding: 15px;">
                                    <strong><?php echo htmlspecialchars($recent_user['full_name']); ?></strong>
                                </td>
                                <td style="padding: 15px;"><?php echo htmlspecialchars($recent_user['email']); ?></td>
                                <td style="padding: 15px;">
                                    <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                        <?php echo ucfirst($recent_user['role']); ?>
                                    </span>
                                </td>
                                <td style="padding: 15px;">
                                    <?php echo $recent_user['created_at'] ? date('M j, Y', strtotime($recent_user['created_at'])) : 'Unknown'; ?>
                                </td>
                                <td style="padding: 15px;">
                                    <?php echo $recent_user['last_login'] ? date('M j, Y g:i A', strtotime($recent_user['last_login'])) : 'Never'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- License Information -->
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2>License & Subscription</h2>
                    <p><strong>Plan:</strong> <?php echo ucfirst($license_status['plan']); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($license_status['status']); ?></p>
                    <p><strong>Expires:</strong> <?php echo date('F j, Y', strtotime($license_status['expires_at'])); ?></p>
                </div>
                <div>
                    <a href="../subscription.php" style="background: #007cba; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px;">
                        Manage Subscription
                    </a>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <h4>Active Modules:</h4>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($license_status['active_modules'] as $module): ?>
                        <span style="background: #e8f5e8; color: #2e7d32; padding: 6px 12px; border-radius: 20px; font-size: 14px;">
                            <?php echo ucfirst(str_replace('_', ' ', $module)); ?>
                        </span>
                    <?php endforeach; ?>
                    <?php if (empty($license_status['active_modules'])): ?>
                        <span style="color: #666; font-style: italic;">No additional modules active</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
