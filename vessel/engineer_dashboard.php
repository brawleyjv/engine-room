<?php
/**
 * Engineer Dashboard - Chief Engineer, Assistant Engineer
 * Engine room operations, maintenance, and system monitoring
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/includes/database.php';
require_once __DIR__ . '/app/includes/functions.php';
require_once __DIR__ . '/app/security/auth.php';

// Security checks
requireLogin();

$user = getCurrentUser();
if ($user['role'] !== 'engineer') {
    header('Location: /dashboard.php');
    exit;
}

$vessel_config = getVesselConfig();

// Get real engine data from database
$engine_stats = getEngineStats();
$recent_logs = getRecentLogs(5);
$daily_stats = getDailyStats();
$pending_sync = getPendingSyncCount();

// System status
$system_status = [
    'database' => checkDatabaseHealth(),
    'storage' => getStorageInfo(),
    'sync' => getSyncStatus(),
    'online' => isOnline()
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineer Dashboard - <?php echo htmlspecialchars($vessel_config['vessel_name'] ?? 'Vessel Logger'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #2c3e50;
        }
        
        .header {
            background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header-left h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header-left p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .sync-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #27ae60;
        }
        
        .status-dot.status-offline {
            background: #e74c3c;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-button {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .engine-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e1e8ed;
        }
        
        .engine-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .engine-name {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-running {
            background: #d5f4e6;
            color: #27ae60;
        }
        
        .status-stopped {
            background: #fadbd8;
            color: #e74c3c;
        }
        
        .status-warning {
            background: #fef5e7;
            color: #f39c12;
        }
        
        .engine-metrics {
            display: grid;
            gap: 15px;
        }
        
        .metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .metric:last-child {
            border-bottom: none;
        }
        
        .metric-label {
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .metric-value {
            font-weight: 600;
            font-size: 16px;
        }
        
        .metric-temp {
            color: #e67e22;
        }
        
        .metric-pressure {
            color: #27ae60;
        }
        
        .metric-coolant {
            color: #3498db;
        }
        
        .dashboard-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e1e8ed;
            margin-bottom: 20px;
        }
        
        .section-header {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #e67e22;
            padding-bottom: 10px;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e1e8ed;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #e67e22;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
            font-weight: 500;
        }
        
        .recent-logs {
            display: grid;
            gap: 10px;
        }
        
        .log-entry {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #e67e22;
        }
        
        .log-info {
            flex: 1;
        }
        
        .log-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .log-meta {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .log-time {
            font-size: 12px;
            color: #95a5a6;
            font-weight: 500;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            gap: 15px;
            background: white;
            border: 2px solid #e67e22;
            color: #e67e22;
            padding: 15px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: #e67e22;
            color: white;
        }
        
        .action-btn-primary {
            background: #e67e22;
            color: white;
        }
        
        .action-btn-primary:hover {
            background: #d35400;
        }
        
        .action-btn-icon {
            font-size: 24px;
        }
        
        .no-data {
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
            padding: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-warning {
            background: #fef5e7;
            border: 1px solid #f39c12;
            color: #d68910;
        }
        
        .alert-error {
            background: #fadbd8;
            border: 1px solid #e74c3c;
            color: #c0392b;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="header-left">
                <h1>Engineer Dashboard</h1>
                <p><?php echo htmlspecialchars($vessel_config['vessel_name'] ?? 'Vessel Logger'); ?> • Engine Room Operations</p>
            </div>
            
            <div class="header-right">
                <div class="sync-status">
                    <div class="status-dot <?php echo $system_status['online'] ? 'status-online' : 'status-offline'; ?>"></div>
                    <span><?php echo $system_status['online'] ? 'Online' : 'Offline'; ?></span>
                    <?php if ($pending_sync > 0): ?>
                        <span style="margin-left: 15px; color: rgba(255,255,255,0.8);">
                            (<?php echo $pending_sync; ?> pending sync)
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="user-menu">
                    <button class="user-button">
                        <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                        <span>▼</span>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <?php if (!$system_status['database']): ?>
            <div class="alert alert-error">
                <strong>Database Issue:</strong> There are problems with the vessel database. Some features may not work properly.
            </div>
        <?php endif; ?>
        
        <?php if ($system_status['storage']['free_percent'] < 10): ?>
            <div class="alert alert-warning">
                <strong>Low Storage:</strong> Only <?php echo number_format($system_status['storage']['free_percent'], 1); ?>% storage remaining.
            </div>
        <?php endif; ?>
        
        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($daily_stats['logs_today']); ?></div>
                <div class="stat-label">Logs Today</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo count($engine_stats); ?></div>
                <div class="stat-label">Active Engines</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_sync; ?></div>
                <div class="stat-label">Pending Sync</div>
            </div>
        </div>
        
        <!-- Engine Status Cards -->
        <?php if (!empty($engine_stats)): ?>
        <div class="dashboard-section">
            <div class="section-header">Engine Status (Last 24 Hours)</div>
            <div class="stats-grid">
                <?php foreach ($engine_stats as $engine): ?>
                    <div class="engine-card">
                        <div class="engine-header">
                            <div class="engine-name"><?php echo htmlspecialchars($engine['display_name']); ?></div>
                            <div class="status-badge status-<?php echo $engine['log_count'] > 0 ? 'running' : 'stopped'; ?>">
                                <?php echo $engine['log_count'] > 0 ? 'Active' : 'No Data'; ?>
                            </div>
                        </div>
                        
                        <?php if ($engine['log_count'] > 0): ?>
                            <div class="engine-metrics">
                                <?php if ($engine['avg_temp']): ?>
                                    <div class="metric">
                                        <span class="metric-label">Temperature</span>
                                        <span class="metric-value metric-temp"><?php echo $engine['avg_temp']; ?>°F</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($engine['avg_oil_pressure']): ?>
                                    <div class="metric">
                                        <span class="metric-label">Oil Pressure</span>
                                        <span class="metric-value metric-pressure"><?php echo $engine['avg_oil_pressure']; ?> PSI</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($engine['avg_coolant_pressure']): ?>
                                    <div class="metric">
                                        <span class="metric-label">Coolant Pressure</span>
                                        <span class="metric-value metric-coolant"><?php echo $engine['avg_coolant_pressure']; ?> PSI</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="metric">
                                    <span class="metric-label">Data Points</span>
                                    <span class="metric-value"><?php echo $engine['log_count']; ?> logs</span>
                                </div>
                                
                                <?php if ($engine['last_log']): ?>
                                    <div class="metric">
                                        <span class="metric-label">Last Update</span>
                                        <span class="metric-value" style="font-size: 14px;">
                                            <?php echo date('M j, H:i', strtotime($engine['last_log'])); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-data">
                                No recent data available
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="/add_log.php" class="action-btn action-btn-primary">
                <div class="action-btn-icon">📝</div>
                <div>
                    <strong>Add Engine Log</strong><br>
                    <small>Record engine parameters</small>
                </div>
            </a>
            
            <a href="/view_logs.php" class="action-btn">
                <div class="action-btn-icon">📊</div>
                <div>
                    <strong>View All Logs</strong><br>
                    <small>Browse engine history</small>
                </div>
            </a>
            
            <a href="/reports.php" class="action-btn">
                <div class="action-btn-icon">📈</div>
                <div>
                    <strong>Engine Reports</strong><br>
                    <small>Generate reports</small>
                </div>
            </a>
            
            <a href="/dashboard.php" class="action-btn">
                <div class="action-btn-icon">🏠</div>
                <div>
                    <strong>Main Dashboard</strong><br>
                    <small>Return to overview</small>
                </div>
            </a>
        </div>
        
        <!-- Recent Activity -->
        <?php if (!empty($recent_logs)): ?>
        <div class="dashboard-section">
            <div class="section-header">Recent Engine Logs</div>
            <div class="recent-logs">
                <?php foreach ($recent_logs as $log): ?>
                    <div class="log-entry">
                        <div class="log-info">
                            <div class="log-title"><?php echo htmlspecialchars($log['summary'] ?? 'Engine Log'); ?></div>
                            <div class="log-meta"><?php echo htmlspecialchars($log['engine_number'] ?? 'Engine'); ?> • <?php echo htmlspecialchars($log['equipment_type'] ?? 'Engine'); ?></div>
                        </div>
                        <div class="log-time">
                            <?php echo date('M j, H:i', strtotime($log['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>
