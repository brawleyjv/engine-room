<?php
/**
 * Vessel Logger - Main Dashboard
 * Primary interface for vessel logging operations
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/includes/database.php';
require_once __DIR__ . '/app/includes/functions.php';
require_once __DIR__ . '/app/security/auth.php';

// Security checks
requireLogin();

$user = getCurrentUser();
$vessel_config = getVesselConfig();

// Get system status
$system_status = [
    'database' => checkDatabaseHealth(),
    'storage' => getStorageInfo(),
    'sync' => getSyncStatus(),
    'online' => isOnline()
];

// Get recent activity
$recent_logs = getRecentLogs(10);
$pending_sync = getPendingSyncCount();
$daily_stats = getDailyStats();

// Handle AJAX requests
if (isset($_GET['action']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'sync_now':
                if ($system_status['online']) {
                    $result = performSync();
                    echo json_encode(['success' => true, 'message' => 'Sync completed successfully', 'data' => $result]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Cannot sync while offline']);
                }
                break;
                
            case 'system_info':
                $info = getSystemInfo();
                echo json_encode(['success' => true, 'data' => $info]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Logger - Dashboard</title>
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
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header-left {
            display: flex;
            align-items: center;
        }
        
        .header-left h1 {
            font-size: 24px;
            margin-right: 30px;
            font-weight: 300;
        }
        
        .vessel-info {
            background: rgba(255,255,255,0.1);
            padding: 8px 15px;
            border-radius: 6px;
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
            font-size: 14px;
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-online { background: #27ae60; }
        .status-offline { background: #e74c3c; }
        .status-syncing { background: #f39c12; animation: pulse 1.5s infinite; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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
        
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 6px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            min-width: 200px;
            margin-top: 5px;
            display: none;
        }
        
        .user-dropdown a {
            display: block;
            padding: 12px 15px;
            color: #2c3e50;
            text-decoration: none;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .user-dropdown a:hover {
            background: #f8f9fa;
        }
        
        .user-dropdown a:last-child {
            border-bottom: none;
            color: #e74c3c;
        }
        
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-btn {
            background: white;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            padding: 20px;
            text-decoration: none;
            color: #2c3e50;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .action-btn:hover {
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .action-btn.primary {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .action-btn.primary:hover {
            background: #2980b9;
            border-color: #2980b9;
        }
        
        .action-btn-icon {
            font-size: 24px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #ecf0f1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn.primary .action-btn-icon {
            background: rgba(255,255,255,0.2);
        }
        
        .content-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #e1e8ed;
            font-weight: 600;
            font-size: 18px;
        }
        
        .card-content {
            padding: 20px;
        }
        
        .log-entry {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-info {
            flex: 1;
        }
        
        .log-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .log-meta {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .log-time {
            font-size: 12px;
            color: #95a5a6;
        }
        
        .system-metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .system-metric:last-child {
            border-bottom: none;
        }
        
        .metric-label {
            font-weight: 500;
        }
        
        .metric-value {
            font-weight: 600;
            color: #27ae60;
        }
        
        .metric-value.warning {
            color: #f39c12;
        }
        
        .metric-value.error {
            color: #e74c3c;
        }
        
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #95a5a6;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .content-row {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="header-left">
                <h1>Vessel Logger</h1>
                <div class="vessel-info">
                    <strong><?php echo htmlspecialchars($vessel_config['vessel_name'] ?? 'Unknown Vessel'); ?></strong><br>
                    <small><?php echo htmlspecialchars($vessel_config['company_name'] ?? 'Unknown Company'); ?></small>
                </div>
            </div>
            
            <div class="header-right">
                <div class="sync-status">
                    <div class="status-dot <?php echo $system_status['online'] ? 'status-online' : 'status-offline'; ?>"></div>
                    <span><?php echo $system_status['online'] ? 'Online' : 'Offline'; ?></span>
                    <?php if ($pending_sync > 0): ?>
                        <span style="margin-left: 15px; color: #f39c12;">
                            (<?php echo $pending_sync; ?> pending sync)
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="user-menu">
                    <button class="user-button" onclick="toggleUserMenu()">
                        <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                        <span>▼</span>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="/profile.php">Profile Settings</a>
                        <a href="/users.php">Manage Users</a>
                        <a href="/settings.php">System Settings</a>
                        <a href="/logout.php">Sign Out</a>
                    </div>
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
                <strong>Low Storage:</strong> Only <?php echo number_format($system_status['storage']['free_percent'], 1); ?>% storage remaining. Consider backing up and purging old logs.
            </div>
        <?php endif; ?>
        
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($daily_stats['logs_today']); ?></div>
                <div class="stat-label">Logs Today</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($daily_stats['total_logs']); ?></div>
                <div class="stat-label">Total Logs</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $pending_sync; ?></div>
                <div class="stat-label">Pending Sync</div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="/add_log.php" class="action-btn primary">
                <div class="action-btn-icon">📝</div>
                <div>
                    <strong>Add New Log</strong><br>
                    <small>Record engine log entry</small>
                </div>
            </a>
            
            <a href="/view_logs.php" class="action-btn">
                <div class="action-btn-icon">📊</div>
                <div>
                    <strong>View Logs</strong><br>
                    <small>Browse and search logs</small>
                </div>
            </a>
            
            <a href="/reports.php" class="action-btn">
                <div class="action-btn-icon">📈</div>
                <div>
                    <strong>Reports</strong><br>
                    <small>Generate reports</small>
                </div>
            </a>
            
            <button class="action-btn" onclick="syncNow()" <?php echo !$system_status['online'] ? 'disabled' : ''; ?>>
                <div class="action-btn-icon">🔄</div>
                <div>
                    <strong>Sync Now</strong><br>
                    <small>Upload to server</small>
                </div>
            </button>
        </div>
        
        <div class="content-row">
            <div class="card">
                <div class="card-header">Recent Activity</div>
                <div class="card-content">
                    <?php if (empty($recent_logs)): ?>
                        <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                            No recent activity. <a href="/add_log.php">Add your first log entry</a>.
                        </p>
                    <?php else: ?>
                        <?php foreach ($recent_logs as $log): ?>
                            <div class="log-entry">
                                <div class="log-info">
                                    <div class="log-title"><?php echo htmlspecialchars($log['summary'] ?? 'Log Entry'); ?></div>
                                    <div class="log-meta">
                                        Engine <?php echo htmlspecialchars($log['engine_number'] ?? 'N/A'); ?> • 
                                        <?php echo htmlspecialchars($log['equipment_type'] ?? 'Engine'); ?>
                                    </div>
                                </div>
                                <div class="log-time">
                                    <?php echo date('M j, H:i', strtotime($log['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="/view_logs.php" class="btn btn-secondary">View All Logs</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">System Status</div>
                <div class="card-content">
                    <div class="system-metric">
                        <span class="metric-label">Database</span>
                        <span class="metric-value <?php echo $system_status['database'] ? '' : 'error'; ?>">
                            <?php echo $system_status['database'] ? 'Healthy' : 'Error'; ?>
                        </span>
                    </div>
                    
                    <div class="system-metric">
                        <span class="metric-label">Storage Free</span>
                        <span class="metric-value <?php echo $system_status['storage']['free_percent'] < 10 ? 'error' : ($system_status['storage']['free_percent'] < 25 ? 'warning' : ''); ?>">
                            <?php echo number_format($system_status['storage']['free_percent'], 1); ?>%
                        </span>
                    </div>
                    
                    <div class="system-metric">
                        <span class="metric-label">Database Size</span>
                        <span class="metric-value">
                            <?php echo formatFileSize($system_status['storage']['database_size']); ?>
                        </span>
                    </div>
                    
                    <div class="system-metric">
                        <span class="metric-label">Last Sync</span>
                        <span class="metric-value">
                            <?php 
                            $last_sync = $system_status['sync']['last_sync'];
                            if ($last_sync) {
                                echo date('M j, H:i', strtotime($last_sync));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="system-metric">
                        <span class="metric-label">Network</span>
                        <span class="metric-value <?php echo $system_status['online'] ? '' : 'warning'; ?>">
                            <?php echo $system_status['online'] ? 'Connected' : 'Offline'; ?>
                        </span>
                    </div>
                    
                    <div style="text-align: center; margin-top: 15px;">
                        <button class="btn btn-secondary" onclick="showSystemInfo()">System Details</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- System Info Modal -->
    <div id="systemInfoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; padding: 20px;">
        <div style="background: white; border-radius: 10px; max-width: 600px; margin: 0 auto; max-height: 90vh; overflow-y: auto;">
            <div style="padding: 20px; border-bottom: 1px solid #e1e8ed; display: flex; justify-content: space-between; align-items: center;">
                <h3>System Information</h3>
                <button onclick="closeModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <div id="systemInfoContent" style="padding: 20px;">
                Loading...
            </div>
        </div>
    </div>
    
    <script>
        // User menu toggle
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
        
        // Close user menu when clicking outside
        document.addEventListener('click', function(e) {
            const userMenu = document.querySelector('.user-menu');
            if (!userMenu.contains(e.target)) {
                document.getElementById('userDropdown').style.display = 'none';
            }
        });
        
        // Sync now function
        async function syncNow() {
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = '<div class="action-btn-icon">🔄</div><div><strong>Syncing...</strong><br><small>Please wait</small></div>';
            
            try {
                const response = await fetch('/dashboard.php?action=sync_now', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Sync completed successfully!');
                    // Refresh the page to show updated stats
                    window.location.reload();
                } else {
                    alert('Sync failed: ' + result.message);
                }
            } catch (error) {
                alert('Network error: ' + error.message);
            } finally {
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }
        
        // Show system info modal
        async function showSystemInfo() {
            const modal = document.getElementById('systemInfoModal');
            const content = document.getElementById('systemInfoContent');
            
            modal.style.display = 'block';
            content.innerHTML = 'Loading...';
            
            try {
                const response = await fetch('/dashboard.php?action=system_info', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const info = result.data;
                    content.innerHTML = `
                        <div class="system-metric">
                            <span class="metric-label">PHP Version</span>
                            <span class="metric-value">${info.php_version}</span>
                        </div>
                        <div class="system-metric">
                            <span class="metric-label">SQLite Version</span>
                            <span class="metric-value">${info.sqlite_version}</span>
                        </div>
                        <div class="system-metric">
                            <span class="metric-label">Vessel Logger Version</span>
                            <span class="metric-value">${info.vessel_version}</span>
                        </div>
                        <div class="system-metric">
                            <span class="metric-label">Hardware ID</span>
                            <span class="metric-value">${info.hardware_id}</span>
                        </div>
                        <div class="system-metric">
                            <span class="metric-label">Operating System</span>
                            <span class="metric-value">${info.os}</span>
                        </div>
                        <div class="system-metric">
                            <span class="metric-label">Memory Usage</span>
                            <span class="metric-value">${info.memory_usage}</span>
                        </div>
                        <div class="system-metric">
                            <span class="metric-label">Uptime</span>
                            <span class="metric-value">${info.uptime}</span>
                        </div>
                    `;
                } else {
                    content.innerHTML = '<p style="color: #e74c3c;">Failed to load system information.</p>';
                }
            } catch (error) {
                content.innerHTML = '<p style="color: #e74c3c;">Network error: ' + error.message + '</p>';
            }
        }
        
        // Close modal
        function closeModal() {
            document.getElementById('systemInfoModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('systemInfoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Auto-refresh sync status every 30 seconds
        setInterval(function() {
            // Only refresh if we're not in a modal or actively syncing
            if (document.getElementById('systemInfoModal').style.display === 'none') {
                fetch('/dashboard.php?status_check=1')
                    .then(response => response.json())
                    .then(data => {
                        if (data.pending_sync !== undefined) {
                            const current = parseInt(document.querySelector('.stat-card:nth-child(3) .stat-number').textContent.replace(/,/g, ''));
                            if (data.pending_sync !== current) {
                                window.location.reload();
                            }
                        }
                    })
                    .catch(() => {
                        // Ignore errors for auto-refresh
                    });
            }
        }, 30000);
    </script>
</body>
</html>
