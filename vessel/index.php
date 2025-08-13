<?php
// Redirect to the engine room dashboard
header('Location: engineroom/dashboard.php');
exit;
?>

// Check if user has vessel access
$user = get_logged_in_user();
if (!in_array($user['role'], ['crew', 'captain', 'engineer', 'admin'])) {
    header('Location: ../office/');
    exit;
}

$license = new LicenseManager($conn, CUSTOMER_ID);
$license_status = $license->getLicenseStatus();

// Get current vessel
$vessel_id = $_SESSION['current_vessel_id'] ?? null;
$vessel = null;
if ($vessel_id) {
    $vessel_query = "SELECT * FROM vessels WHERE VesselID = ? AND customer_id = ?";
    $stmt = $conn->prepare($vessel_query);
    $stmt->bind_param('is', $vessel_id, CUSTOMER_ID);
    $stmt->execute();
    $vessel = $stmt->get_result()->fetch_assoc();
}

// Get recent logs for this vessel
$recent_logs = [];
if ($vessel) {
    $logs_query = "SELECT * FROM engine_logs WHERE VesselID = ? ORDER BY LogDate DESC, LogTime DESC LIMIT 10";
    $stmt = $conn->prepare($logs_query);
    $stmt->bind_param('i', $vessel_id);
    $stmt->execute();
    $recent_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Dashboard - <?php echo $vessel ? htmlspecialchars($vessel['VesselName']) : 'Select Vessel'; ?></title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .metric-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .metric-value {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-online { background: #28a745; }
        .status-warning { background: #ffc107; }
        .status-offline { background: #dc3545; }
    </style>
</head>
<body>
    <?php show_license_banner(); ?>
    
    <div class="container">
        <header style="background: #2c3e50; color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>⚓ Vessel Dashboard</h1>
                    <?php if ($vessel): ?>
                        <p><?php echo htmlspecialchars($vessel['VesselName']); ?> - Engine Room Control</p>
                    <?php else: ?>
                        <p>No vessel selected</p>
                    <?php endif; ?>
                </div>
                <div style="text-align: right;">
                    <p>Welcome, <?php echo htmlspecialchars($user['full_name']); ?></p>
                    <p><small><?php echo ucfirst($user['role']); ?></small></p>
                    <div>
                        <a href="../select_vessel.php" style="color: white; text-decoration: underline; margin-right: 10px;">Switch Vessel</a>
                        <a href="../logout.php" style="color: white; text-decoration: underline;">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <?php if (!$vessel): ?>
            <div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                <h3>No Vessel Selected</h3>
                <p>Please select a vessel to access the dashboard.</p>
                <a href="../select_vessel.php" style="background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">
                    Select Vessel
                </a>
            </div>
        <?php else: ?>
            
            <!-- Engine Status Overview -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <?php
                // Get latest engine data
                $engines = ['main', 'auxiliary', 'emergency'];
                foreach ($engines as $engine_type):
                    $engine_query = "SELECT * FROM engine_logs WHERE VesselID = ? AND EngineType = ? ORDER BY LogDate DESC, LogTime DESC LIMIT 1";
                    $stmt = $conn->prepare($engine_query);
                    $stmt->bind_param('is', $vessel_id, $engine_type);
                    $stmt->execute();
                    $latest_data = $stmt->get_result()->fetch_assoc();
                ?>
                    <div class="metric-card">
                        <h4><?php echo ucfirst($engine_type); ?> Engine</h4>
                        <?php if ($latest_data): ?>
                            <div class="metric-value" style="color: #28a745;">
                                <span class="status-indicator status-online"></span>
                                Running
                            </div>
                            <p><strong>RPM:</strong> <?php echo number_format($latest_data['RPM'] ?? 0); ?></p>
                            <p><strong>Temp:</strong> <?php echo number_format($latest_data['CoolantTemp'] ?? 0, 1); ?>°C</p>
                            <small>Updated: <?php echo date('H:i', strtotime($latest_data['LogTime'])); ?></small>
                        <?php else: ?>
                            <div class="metric-value" style="color: #dc3545;">
                                <span class="status-indicator status-offline"></span>
                                No Data
                            </div>
                            <p>No recent logs</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <!-- Generator Status -->
                <div class="metric-card">
                    <h4>Generators</h4>
                    <?php
                    $gen_query = "SELECT COUNT(*) as gen_count FROM generator_logs WHERE VesselID = ? AND LogDate = CURDATE()";
                    $stmt = $conn->prepare($gen_query);
                    $stmt->bind_param('i', $vessel_id);
                    $stmt->execute();
                    $gen_data = $stmt->get_result()->fetch_assoc();
                    ?>
                    <div class="metric-value" style="color: #007cba;">
                        <?php echo $gen_data['gen_count']; ?>
                    </div>
                    <p>Logs Today</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <a href="../add_log.php" style="background: #28a745; color: white; padding: 30px; text-decoration: none; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>📝 Add Log Entry</h3>
                    <p>Record engine room data</p>
                </a>
                
                <a href="../view_logs.php?vessel_id=<?php echo $vessel_id; ?>" style="background: #007cba; color: white; padding: 30px; text-decoration: none; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>📊 View Logs</h3>
                    <p>Browse historical data</p>
                </a>
                
                <a href="../graph_logs.php?vessel_id=<?php echo $vessel_id; ?>" style="background: #6f42c1; color: white; padding: 30px; text-decoration: none; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>📈 Charts</h3>
                    <p>Visual analytics</p>
                </a>
                
                <a href="../maintenance.php" style="background: #fd7e14; color: white; padding: 30px; text-decoration: none; border-radius: 10px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>🔧 Maintenance</h3>
                    <p>Schedule & track</p>
                </a>
            </div>

            <!-- Recent Activity -->
            <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2>Recent Activity</h2>
                <?php if (empty($recent_logs)): ?>
                    <p style="color: #666; font-style: italic;">No recent logs found. <a href="../add_log.php">Add your first log entry</a>.</p>
                <?php else: ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Date/Time</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Engine</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">RPM</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Temp</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Pressure</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Engineer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr style="border-bottom: 1px solid #dee2e6;">
                                    <td style="padding: 12px;">
                                        <?php echo date('M j', strtotime($log['LogDate'])) . ' ' . date('H:i', strtotime($log['LogTime'])); ?>
                                    </td>
                                    <td style="padding: 12px;"><?php echo ucfirst($log['EngineType']); ?></td>
                                    <td style="padding: 12px;"><?php echo number_format($log['RPM'] ?? 0); ?></td>
                                    <td style="padding: 12px;"><?php echo number_format($log['CoolantTemp'] ?? 0, 1); ?>°C</td>
                                    <td style="padding: 12px;"><?php echo number_format($log['OilPressure'] ?? 0, 1); ?> bar</td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($log['Engineer'] ?? 'Unknown'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="../view_logs.php?vessel_id=<?php echo $vessel_id; ?>" style="color: #007cba;">
                            View All Logs →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh page every 5 minutes to keep data current
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
