<?php
require_once '../config_installed.php';
require_once '../auth_functions.php';
require_once '../license_manager.php';

require_login();

// Check if user has office access
$user = get_logged_in_user();
if (!in_array($user['role'], ['owner', 'manager', 'admin'])) {
    header('Location: ../vessel/');
    exit;
}

$license = new LicenseManager($conn, CUSTOMER_ID);
$license_status = $license->getLicenseStatus();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Dashboard - <?php echo htmlspecialchars(COMPANY_NAME); ?></title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <?php show_license_banner(); ?>
    
    <div class="container">
        <header style="background: #1e3a8a; color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>🏢 Office Dashboard</h1>
                    <p><?php echo htmlspecialchars(COMPANY_NAME); ?> - Fleet Management</p>
                </div>
                <div style="text-align: right;">
                    <p>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></p>
                    <p><small><?php echo ucfirst($user['role']); ?></small></p>
                    <a href="../logout.php" style="color: white; text-decoration: underline;">Logout</a>
                </div>
            </div>
        </header>

        <!-- Fleet Overview -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <?php
            // Get vessels for this customer
            $vessels_query = "SELECT * FROM vessels WHERE customer_id = ? ORDER BY VesselName";
            $stmt = $conn->prepare($vessels_query);
            $stmt->bind_param('s', CUSTOMER_ID);
            $stmt->execute();
            $vessels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            ?>
            
            <div style="background: #e3f2fd; padding: 20px; border-radius: 10px;">
                <h3>🚢 Fleet Status</h3>
                <div style="font-size: 24px; font-weight: bold; color: #1976d2;">
                    <?php echo count($vessels); ?> / <?php echo $license_status['vessel_limit']; ?>
                </div>
                <p>Vessels Active</p>
            </div>
            
            <div style="background: #e8f5e8; padding: 20px; border-radius: 10px;">
                <h3>📊 Data Sync</h3>
                <div style="font-size: 24px; font-weight: bold; color: #2e7d32;">
                    <?php echo count(array_filter($vessels, function($v) { return strtotime($v['last_sync'] ?? '0') > strtotime('-24 hours'); })); ?>
                </div>
                <p>Synced Today</p>
            </div>
            
            <div style="background: #fff3e0; padding: 20px; border-radius: 10px;">
                <h3>⚠️ Alerts</h3>
                <div style="font-size: 24px; font-weight: bold; color: #f57c00;">0</div>
                <p>Active Alerts</p>
            </div>
        </div>

        <!-- Vessel List -->
        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Fleet Management</h2>
                <?php if ($license->canAddVessel()): ?>
                    <a href="../manage_vessels.php?action=add" style="background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">
                        + Add Vessel
                    </a>
                <?php else: ?>
                    <span style="color: #666; font-style: italic;">Vessel limit reached - <a href="../subscription.php">Upgrade</a></span>
                <?php endif; ?>
            </div>
            
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Vessel</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Type</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Last Sync</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Status</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vessels as $vessel): ?>
                        <tr style="border-bottom: 1px solid #dee2e6;">
                            <td style="padding: 15px;">
                                <strong><?php echo htmlspecialchars($vessel['VesselName']); ?></strong>
                                <br><small style="color: #666;"><?php echo htmlspecialchars($vessel['CallSign'] ?? 'N/A'); ?></small>
                            </td>
                            <td style="padding: 15px;"><?php echo htmlspecialchars(ucfirst($vessel['VesselType'])); ?></td>
                            <td style="padding: 15px;">
                                <?php 
                                $last_sync = $vessel['last_sync'] ?? null;
                                if ($last_sync) {
                                    echo date('M j, Y g:i A', strtotime($last_sync));
                                } else {
                                    echo '<span style="color: #dc3545;">Never</span>';
                                }
                                ?>
                            </td>
                            <td style="padding: 15px;">
                                <?php
                                $sync_age = $last_sync ? time() - strtotime($last_sync) : null;
                                if (!$sync_age) {
                                    echo '<span style="color: #dc3545;">●</span> Offline';
                                } elseif ($sync_age < 3600) {
                                    echo '<span style="color: #28a745;">●</span> Online';
                                } elseif ($sync_age < 86400) {
                                    echo '<span style="color: #ffc107;">●</span> Recent';
                                } else {
                                    echo '<span style="color: #dc3545;">●</span> Stale';
                                }
                                ?>
                            </td>
                            <td style="padding: 15px;">
                                <a href="../vessel/engineroom/view_logs.php?vessel_id=<?php echo $vessel['VesselID']; ?>" 
                                   style="color: #007cba; text-decoration: none; margin-right: 10px;">View Logs</a>
                                <a href="../manage_vessels.php?action=edit&id=<?php echo $vessel['VesselID']; ?>" 
                                   style="color: #6c757d; text-decoration: none;">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Quick Actions -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px;">
            <a href="../view_logs.php" style="background: #007cba; color: white; padding: 20px; text-decoration: none; border-radius: 10px; text-align: center;">
                <h4>📊 View All Logs</h4>
                <p>Fleet-wide data analysis</p>
            </a>
            
            <a href="../manage_users.php" style="background: #6c757d; color: white; padding: 20px; text-decoration: none; border-radius: 10px; text-align: center;">
                <h4>👥 Manage Users</h4>
                <p>Add crew and staff</p>
            </a>
            
            <a href="../reports.php" style="background: #28a745; color: white; padding: 20px; text-decoration: none; border-radius: 10px; text-align: center;">
                <h4>📈 Reports</h4>
                <p>Performance analytics</p>
            </a>
            
            <a href="../subscription.php" style="background: #ffc107; color: #212529; padding: 20px; text-decoration: none; border-radius: 10px; text-align: center;">
                <h4>⚙️ Settings</h4>
                <p>Account & billing</p>
            </a>
        </div>
    </div>
</body>
</html>
