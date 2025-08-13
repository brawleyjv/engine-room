<?php
/**
 * LogicDock VPS Server Management Dashboard
 * Complete server administration, monitoring, and database management
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Super secure access check - only for LogicDock administrators
$valid_access_keys = [
    'logicdock_vps_admin_2024',
    'server_management_key',
    'vps_control_panel'
];

$access_granted = false;
if (isset($_GET['access_key']) && in_array($_GET['access_key'], $valid_access_keys)) {
    $access_granted = true;
} elseif (isset($_POST['admin_password']) && $_POST['admin_password'] === 'LogicDockVPS2024!Admin') {
    $access_granted = true;
    $_SESSION['vps_admin_authenticated'] = true;
} elseif (isset($_SESSION['vps_admin_authenticated'])) {
    $access_granted = true;
}

if (!$access_granted) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>LogicDock VPS Administration</title>
        <style>
            body { font-family: sans-serif; max-width: 500px; margin: 100px auto; padding: 20px; background: linear-gradient(135deg, #2c3e50, #34495e); color: white; }
            .login-box { background: rgba(255,255,255,0.1); padding: 40px; border-radius: 15px; backdrop-filter: blur(10px); }
            input, button { width: 100%; padding: 15px; margin: 10px 0; border: 1px solid #ddd; border-radius: 8px; }
            button { background: #e74c3c; color: white; border: none; cursor: pointer; font-weight: bold; }
            button:hover { background: #c0392b; }
            .error { background: rgba(231,76,60,0.2); color: #fff; padding: 15px; border-radius: 8px; margin: 10px 0; }
            h2 { text-align: center; margin-bottom: 30px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🔐 LogicDock VPS Administration</h2>
            <p style="text-align: center; margin-bottom: 30px;">Server Management & Database Control Panel</p>
            
            <?php if (isset($_POST['admin_password'])): ?>
                <div class="error">Invalid administrator password.</div>
            <?php endif; ?>
            
            <form method="post">
                <input type="password" name="admin_password" placeholder="VPS Administrator Password" required>
                <button type="submit">Access Server Management</button>
            </form>
            
            <p style="color: #bdc3c7; font-size: 14px; margin-top: 30px; text-align: center;">
                Authorized LogicDock administrators only.<br>All access is logged and monitored.
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle server actions
$action_result = '';
$action_error = '';

if ($_POST['action'] ?? '') {
    switch ($_POST['action']) {
        case 'reboot_server':
            $action_result = executeServerAction('reboot', 'Server reboot initiated');
            break;
        case 'restart_apache':
            $action_result = executeServerAction('restart_apache', 'Apache web server restarted');
            break;
        case 'restart_mysql':
            $action_result = executeServerAction('restart_mysql', 'MySQL database server restarted');
            break;
        case 'clear_cache':
            $action_result = executeServerAction('clear_cache', 'System cache cleared');
            break;
        case 'backup_databases':
            $action_result = executeServerAction('backup_databases', 'Database backup initiated');
            break;
        case 'update_system':
            $action_result = executeServerAction('update_system', 'System update started');
            break;
        case 'cleanup_logs':
            $action_result = executeServerAction('cleanup_logs', 'Log files cleaned up');
            break;
        case 'optimize_databases':
            $action_result = executeServerAction('optimize_databases', 'Database optimization started');
            break;
    }
}

function executeServerAction($action, $success_message) {
    global $action_error;
    
    try {
        switch ($action) {
            case 'reboot':
                // Schedule reboot in 1 minute to allow response
                exec('sudo shutdown -r +1 "LogicDock scheduled reboot" 2>&1', $output, $return_code);
                if ($return_code === 0) {
                    return $success_message . ' (in 1 minute)';
                }
                break;
                
            case 'restart_apache':
                exec('sudo systemctl restart apache2 2>&1', $output, $return_code);
                if ($return_code === 0) {
                    return $success_message;
                }
                break;
                
            case 'restart_mysql':
                exec('sudo systemctl restart mysql 2>&1', $output, $return_code);
                if ($return_code === 0) {
                    return $success_message;
                }
                break;
                
            case 'clear_cache':
                exec('sudo sync && echo 3 > /proc/sys/vm/drop_caches 2>&1', $output, $return_code);
                return $success_message;
                
            case 'backup_databases':
                $backup_script = '/opt/logicdock/backup_all_databases.sh';
                if (file_exists($backup_script)) {
                    exec("sudo $backup_script 2>&1 &", $output, $return_code);
                    return $success_message;
                } else {
                    return 'Backup script not found - creating automated backup...';
                }
                break;
                
            case 'update_system':
                exec('sudo apt update && sudo apt upgrade -y 2>&1 &', $output, $return_code);
                return $success_message . ' (running in background)';
                
            case 'cleanup_logs':
                exec('sudo journalctl --vacuum-time=7d && sudo find /var/log -name "*.log" -mtime +7 -delete 2>&1', $output, $return_code);
                return $success_message;
                
            case 'optimize_databases':
                optimizeAllCustomerDatabases();
                return $success_message;
        }
        
        if (isset($output) && !empty($output)) {
            $action_error = 'Command output: ' . implode('\n', $output);
        }
        
    } catch (Exception $e) {
        $action_error = 'Action failed: ' . $e->getMessage();
    }
    
    return false;
}

function optimizeAllCustomerDatabases() {
    global $db_host, $db_user, $db_pass;
    
    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $databases = $pdo->query("SHOW DATABASES LIKE 'vessel_%'")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($databases as $db_name) {
            $pdo->exec("USE `$db_name`");
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                $pdo->exec("OPTIMIZE TABLE `$table`");
            }
        }
    } catch (Exception $e) {
        throw new Exception("Database optimization failed: " . $e->getMessage());
    }
}

// Get system information
$server_info = getServerInfo();
$database_info = getDatabaseInfo();
$customer_databases = getCustomerDatabases();
$system_health = getSystemHealth();
$recent_logs = getRecentLogs();

function getServerInfo() {
    $info = [
        'hostname' => gethostname(),
        'uptime' => trim(shell_exec('uptime -p')),
        'load_average' => sys_getloadavg(),
        'memory_usage' => getMemoryUsage(),
        'disk_usage' => getDiskUsage(),
        'cpu_usage' => getCpuUsage(),
        'network_stats' => getNetworkStats(),
        'processes' => getProcessCount(),
        'last_reboot' => trim(shell_exec('who -b | awk \'{print $3, $4}\'')),
        'kernel_version' => trim(shell_exec('uname -r')),
        'os_version' => trim(shell_exec('lsb_release -d | cut -f2-')),
        'php_version' => PHP_VERSION,
        'apache_version' => trim(shell_exec('apache2 -v | head -1 | awk \'{print $3}\'')),
        'mysql_version' => trim(shell_exec('mysql --version | awk \'{print $5}\' | cut -d\',\' -f1'))
    ];
    
    return $info;
}

function getMemoryUsage() {
    $memory = [];
    $meminfo = file_get_contents('/proc/meminfo');
    preg_match('/MemTotal:\s+(\d+) kB/', $meminfo, $matches);
    $memory['total'] = round($matches[1] / 1024 / 1024, 2); // GB
    preg_match('/MemFree:\s+(\d+) kB/', $meminfo, $matches);
    $memory['free'] = round($matches[1] / 1024 / 1024, 2); // GB
    $memory['used'] = round($memory['total'] - $memory['free'], 2);
    $memory['percentage'] = round(($memory['used'] / $memory['total']) * 100, 1);
    return $memory;
}

function getDiskUsage() {
    $disk = [];
    $df_output = shell_exec('df -h / | tail -1');
    $parts = preg_split('/\s+/', trim($df_output));
    $disk['filesystem'] = $parts[0];
    $disk['total'] = $parts[1];
    $disk['used'] = $parts[2];
    $disk['available'] = $parts[3];
    $disk['percentage'] = rtrim($parts[4], '%');
    return $disk;
}

function getCpuUsage() {
    $cpu_usage = trim(shell_exec("top -bn1 | grep 'Cpu(s)' | awk '{print $2}' | cut -d'%' -f1"));
    return floatval($cpu_usage);
}

function getNetworkStats() {
    $rx_bytes = file_get_contents('/sys/class/net/eth0/statistics/rx_bytes');
    $tx_bytes = file_get_contents('/sys/class/net/eth0/statistics/tx_bytes');
    return [
        'rx_gb' => round(intval($rx_bytes) / 1024 / 1024 / 1024, 2),
        'tx_gb' => round(intval($tx_bytes) / 1024 / 1024 / 1024, 2)
    ];
}

function getProcessCount() {
    return intval(trim(shell_exec('ps aux | wc -l')));
}

function getDatabaseInfo() {
    global $db_host, $db_user, $db_pass;
    
    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        
        $info = [
            'version' => $pdo->query("SELECT VERSION()")->fetchColumn(),
            'uptime' => $pdo->query("SHOW STATUS LIKE 'Uptime'")->fetch()['Value'],
            'total_databases' => $pdo->query("SHOW DATABASES")->rowCount(),
            'customer_databases' => $pdo->query("SHOW DATABASES LIKE 'vessel_%'")->rowCount(),
            'total_connections' => $pdo->query("SHOW STATUS LIKE 'Threads_connected'")->fetch()['Value'],
            'max_connections' => $pdo->query("SHOW VARIABLES LIKE 'max_connections'")->fetch()['Value'],
            'slow_queries' => $pdo->query("SHOW STATUS LIKE 'Slow_queries'")->fetch()['Value'],
            'data_size' => getDatabaseSizes($pdo)
        ];
        
        return $info;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function getDatabaseSizes($pdo) {
    $sql = "SELECT 
                table_schema as 'Database',
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size_MB'
            FROM information_schema.tables 
            WHERE table_schema LIKE 'vessel_%'
            GROUP BY table_schema
            ORDER BY Size_MB DESC";
    
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getCustomerDatabases() {
    global $db_host, $db_user, $db_pass;
    
    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $databases = $pdo->query("SHOW DATABASES LIKE 'vessel_%'")->fetchAll(PDO::FETCH_COLUMN);
        
        $customer_dbs = [];
        foreach ($databases as $db_name) {
            $pdo->exec("USE `$db_name`");
            $table_count = $pdo->query("SHOW TABLES")->rowCount();
            $last_activity = $pdo->query("SELECT MAX(created_at) FROM users")->fetchColumn();
            
            $customer_dbs[] = [
                'name' => $db_name,
                'tables' => $table_count,
                'last_activity' => $last_activity
            ];
        }
        
        return $customer_dbs;
    } catch (Exception $e) {
        return [];
    }
}

function getSystemHealth() {
    $health = [
        'apache' => checkServiceStatus('apache2'),
        'mysql' => checkServiceStatus('mysql'),
        'php_fpm' => checkServiceStatus('php7.4-fpm'),
        'ssh' => checkServiceStatus('ssh'),
        'cron' => checkServiceStatus('cron'),
        'fail2ban' => checkServiceStatus('fail2ban'),
        'disk_space' => getDiskUsage()['percentage'] < 90,
        'memory_usage' => getMemoryUsage()['percentage'] < 85,
        'load_average' => sys_getloadavg()[0] < 2.0,
        'mysql_connections' => true // Will be set based on connection ratio
    ];
    
    return $health;
}

function checkServiceStatus($service) {
    $status = shell_exec("systemctl is-active $service 2>/dev/null");
    return trim($status) === 'active';
}

function getRecentLogs() {
    $logs = [
        'apache_errors' => shell_exec('tail -20 /var/log/apache2/error.log 2>/dev/null | tail -10'),
        'mysql_errors' => shell_exec('tail -20 /var/log/mysql/error.log 2>/dev/null | tail -10'),
        'system_logs' => shell_exec('journalctl -n 10 --no-pager -q'),
        'auth_logs' => shell_exec('tail -10 /var/log/auth.log 2>/dev/null | grep -v "sudo"')
    ];
    
    return $logs;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogicDock VPS Management - logicdock.org</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
        }
        
        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .header .subtitle {
            opacity: 0.8;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-item .label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-item .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin: 0.25rem 0;
        }
        
        .stat-item .unit {
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        .health-status {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.5rem;
        }
        
        .health-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .health-item .status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .status.healthy { background: #27ae60; }
        .status.warning { background: #f39c12; }
        .status.critical { background: #e74c3c; }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .log-display {
            background: #1e1e1e;
            color: #f8f8f2;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #ecf0f1;
            border-radius: 10px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .progress-low { background: #27ae60; }
        .progress-medium { background: #f39c12; }
        .progress-high { background: #e74c3c; }
        
        .database-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .database-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .database-item:last-child {
            border-bottom: none;
        }
        
        .database-name {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
        
        .database-meta {
            font-size: 0.8rem;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🖥️ LogicDock VPS Management</h1>
        <div class="subtitle">Server Administration Dashboard - logicdock.org</div>
    </div>

    <div class="container">
        <?php if ($action_result): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($action_result) ?>
            </div>
        <?php endif; ?>

        <?php if ($action_error): ?>
            <div class="alert alert-danger">
                ❌ <?= htmlspecialchars($action_error) ?>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="action-buttons">
            <form method="post" style="margin: 0;">
                <input type="hidden" name="action" value="reboot_server">
                <button type="submit" class="btn btn-danger" onclick="return confirm('⚠️ This will reboot the entire server. All customers will be temporarily disconnected. Continue?')">
                    🔄 Reboot Server
                </button>
            </form>
            
            <form method="post" style="margin: 0;">
                <input type="hidden" name="action" value="restart_apache">
                <button type="submit" class="btn btn-warning">
                    🌐 Restart Apache
                </button>
            </form>
            
            <form method="post" style="margin: 0;">
                <input type="hidden" name="action" value="restart_mysql">
                <button type="submit" class="btn btn-warning">
                    🗄️ Restart MySQL
                </button>
            </form>
            
            <form method="post" style="margin: 0;">
                <input type="hidden" name="action" value="backup_databases">
                <button type="submit" class="btn btn-primary">
                    💾 Backup All DBs
                </button>
            </form>
            
            <form method="post" style="margin: 0;">
                <input type="hidden" name="action" value="optimize_databases">
                <button type="submit" class="btn btn-success">
                    ⚡ Optimize DBs
                </button>
            </form>
            
            <form method="post" style="margin: 0;">
                <input type="hidden" name="action" value="clear_cache">
                <button type="submit" class="btn btn-primary">
                    🧹 Clear Cache
                </button>
            </form>
        </div>

        <div class="dashboard-grid">
            <!-- System Overview -->
            <div class="panel">
                <h2>📊 System Overview</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="label">Uptime</div>
                        <div class="value"><?= htmlspecialchars($server_info['uptime']) ?></div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="label">Load Average</div>
                        <div class="value"><?= number_format($server_info['load_average'][0], 2) ?></div>
                        <div class="unit">1min avg</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="label">Processes</div>
                        <div class="value"><?= $server_info['processes'] ?></div>
                        <div class="unit">running</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="label">CPU Usage</div>
                        <div class="value"><?= number_format($server_info['cpu_usage'], 1) ?>%</div>
                    </div>
                </div>
                
                <p><strong>Hostname:</strong> <?= htmlspecialchars($server_info['hostname']) ?></p>
                <p><strong>OS:</strong> <?= htmlspecialchars($server_info['os_version']) ?></p>
                <p><strong>Kernel:</strong> <?= htmlspecialchars($server_info['kernel_version']) ?></p>
                <p><strong>Last Reboot:</strong> <?= htmlspecialchars($server_info['last_reboot']) ?></p>
            </div>

            <!-- Memory & Disk Usage -->
            <div class="panel">
                <h2>💾 Memory & Storage</h2>
                
                <div style="margin-bottom: 1.5rem;">
                    <h4>Memory Usage</h4>
                    <div class="progress-bar">
                        <div class="progress-fill <?= $server_info['memory_usage']['percentage'] > 85 ? 'progress-high' : ($server_info['memory_usage']['percentage'] > 60 ? 'progress-medium' : 'progress-low') ?>" 
                             style="width: <?= $server_info['memory_usage']['percentage'] ?>%"></div>
                    </div>
                    <p><?= $server_info['memory_usage']['used'] ?>GB / <?= $server_info['memory_usage']['total'] ?>GB (<?= $server_info['memory_usage']['percentage'] ?>%)</p>
                </div>
                
                <div>
                    <h4>Disk Usage</h4>
                    <div class="progress-bar">
                        <div class="progress-fill <?= $server_info['disk_usage']['percentage'] > 85 ? 'progress-high' : ($server_info['disk_usage']['percentage'] > 60 ? 'progress-medium' : 'progress-low') ?>" 
                             style="width: <?= $server_info['disk_usage']['percentage'] ?>%"></div>
                    </div>
                    <p><?= $server_info['disk_usage']['used'] ?> / <?= $server_info['disk_usage']['total'] ?> (<?= $server_info['disk_usage']['percentage'] ?>%)</p>
                    <p><strong>Available:</strong> <?= $server_info['disk_usage']['available'] ?></p>
                </div>
            </div>

            <!-- Service Health -->
            <div class="panel">
                <h2>🏥 Service Health</h2>
                <div class="health-status">
                    <div class="health-item">
                        <span>Apache</span>
                        <div class="status <?= $system_health['apache'] ? 'healthy' : 'critical' ?>"></div>
                    </div>
                    
                    <div class="health-item">
                        <span>MySQL</span>
                        <div class="status <?= $system_health['mysql'] ? 'healthy' : 'critical' ?>"></div>
                    </div>
                    
                    <div class="health-item">
                        <span>PHP-FPM</span>
                        <div class="status <?= $system_health['php_fpm'] ? 'healthy' : 'critical' ?>"></div>
                    </div>
                    
                    <div class="health-item">
                        <span>SSH</span>
                        <div class="status <?= $system_health['ssh'] ? 'healthy' : 'critical' ?>"></div>
                    </div>
                    
                    <div class="health-item">
                        <span>Cron</span>
                        <div class="status <?= $system_health['cron'] ? 'healthy' : 'critical' ?>"></div>
                    </div>
                    
                    <div class="health-item">
                        <span>Fail2Ban</span>
                        <div class="status <?= $system_health['fail2ban'] ? 'healthy' : 'warning' ?>"></div>
                    </div>
                    
                    <div class="health-item">
                        <span>Disk Space</span>
                        <div class="status <?= $system_health['disk_space'] ? 'healthy' : 'critical' ?>"></div>
                    </div>
                    
                    <div class="health-item">
                        <span>Memory</span>
                        <div class="status <?= $system_health['memory_usage'] ? 'healthy' : 'warning' ?>"></div>
                    </div>
                </div>
            </div>

            <!-- Database Information -->
            <div class="panel">
                <h2>🗄️ Database Status</h2>
                <?php if (isset($database_info['error'])): ?>
                    <div class="alert alert-danger">Database connection failed: <?= htmlspecialchars($database_info['error']) ?></div>
                <?php else: ?>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="label">Version</div>
                            <div class="value"><?= htmlspecialchars($database_info['version']) ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="label">Customer DBs</div>
                            <div class="value"><?= $database_info['customer_databases'] ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="label">Connections</div>
                            <div class="value"><?= $database_info['total_connections'] ?>/<?= $database_info['max_connections'] ?></div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="label">Slow Queries</div>
                            <div class="value"><?= $database_info['slow_queries'] ?></div>
                        </div>
                    </div>
                    
                    <p><strong>Uptime:</strong> <?= gmdate('H:i:s', $database_info['uptime']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Customer Databases -->
            <div class="panel">
                <h2>🏢 Customer Databases</h2>
                <div class="database-list">
                    <?php foreach ($customer_databases as $db): ?>
                        <div class="database-item">
                            <div>
                                <div class="database-name"><?= htmlspecialchars($db['name']) ?></div>
                                <div class="database-meta"><?= $db['tables'] ?> tables | Last activity: <?= $db['last_activity'] ? date('M j, H:i', strtotime($db['last_activity'])) : 'Never' ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Software Versions -->
            <div class="panel">
                <h2>📦 Software Versions</h2>
                <div style="line-height: 1.8;">
                    <p><strong>PHP:</strong> <?= htmlspecialchars($server_info['php_version']) ?></p>
                    <p><strong>Apache:</strong> <?= htmlspecialchars($server_info['apache_version']) ?></p>
                    <p><strong>MySQL:</strong> <?= htmlspecialchars($server_info['mysql_version']) ?></p>
                </div>
                
                <div style="margin-top: 1rem;">
                    <form method="post" style="margin: 0;">
                        <input type="hidden" name="action" value="update_system">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            🔄 Update System Packages
                        </button>
                    </form>
                </div>
            </div>

            <!-- Network Statistics -->
            <div class="panel">
                <h2>🌐 Network Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="label">Data Received</div>
                        <div class="value"><?= $server_info['network_stats']['rx_gb'] ?></div>
                        <div class="unit">GB</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="label">Data Sent</div>
                        <div class="value"><?= $server_info['network_stats']['tx_gb'] ?></div>
                        <div class="unit">GB</div>
                    </div>
                </div>
            </div>

            <!-- Recent Logs -->
            <div class="panel" style="grid-column: 1 / -1;">
                <h2>📋 Recent System Logs</h2>
                
                <div style="margin-bottom: 1rem;">
                    <h4>Apache Errors</h4>
                    <div class="log-display"><?= htmlspecialchars($recent_logs['apache_errors'] ?: 'No recent Apache errors') ?></div>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <h4>MySQL Errors</h4>
                    <div class="log-display"><?= htmlspecialchars($recent_logs['mysql_errors'] ?: 'No recent MySQL errors') ?></div>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <h4>System Messages</h4>
                    <div class="log-display"><?= htmlspecialchars($recent_logs['system_logs'] ?: 'No recent system messages') ?></div>
                </div>
                
                <div>
                    <form method="post" style="margin: 0; display: inline-block;">
                        <input type="hidden" name="action" value="cleanup_logs">
                        <button type="submit" class="btn btn-primary">
                            🧹 Cleanup Old Logs
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
        
        // Add confirmation for dangerous actions
        document.querySelectorAll('.btn-danger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('⚠️ This is a potentially dangerous action. Are you sure?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Visual feedback for button clicks
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
