<?php
/**
 * Vessel Logger - Working Dashboard
 * Simplified dashboard without complex dependencies
 */

// Define constant before including config (only if not already defined)
if (!defined('VESSEL_LOGGER')) {
    define('VESSEL_LOGGER', true);
}

// Start session
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Sample data for demonstration
$vessel_name = $_SESSION['vessel_name'] ?? 'Demo Vessel';
$company_name = $_SESSION['company_name'] ?? 'Marine Transport Co.';
$current_user = $_SESSION['username'] ?? 'admin';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Logger Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .header-info {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .nav {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            gap: 2rem;
        }
        
        .nav a {
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav a:hover, .nav a.active {
            background: #667eea;
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        
        .card h3 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .status-item {
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .status-online {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-offline {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .action-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
            text-decoration: none;
            text-align: center;
            display: block;
        }
        
        .action-btn:hover {
            background: #5a6fd8;
        }
        
        .action-btn.secondary {
            background: #6c757d;
        }
        
        .action-btn.secondary:hover {
            background: #5a6268;
        }
        
        .log-entry {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
        }
        
        .log-time {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .metric {
            text-align: center;
            padding: 1rem;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚓ <?php echo htmlspecialchars($vessel_name); ?> - Vessel Logger</h1>
        <div class="header-info">
            <?php echo htmlspecialchars($company_name); ?> | User: <?php echo htmlspecialchars($current_user); ?> | 
            <?php echo date('Y-m-d H:i:s'); ?> | Port: 8080
        </div>
    </div>
    
    <div class="nav">
        <a href="#" class="active">Dashboard</a>
        <a href="#logs">Engine Logs</a>
        <a href="#reports">Reports</a>
        <a href="#sync">Sync</a>
        <a href="#settings">Settings</a>
        <a href="setup_simple.php">Setup</a>
    </div>
    
    <div class="container">
        <div class="dashboard-grid">
            <!-- System Status -->
            <div class="card">
                <h3>🔧 System Status</h3>
                <div class="status-grid">
                    <div class="status-item status-online">
                        <strong>Database</strong><br>
                        SQLite Online
                    </div>
                    <div class="status-item status-online">
                        <strong>USB Storage</strong><br>
                        Available
                    </div>
                    <div class="status-item status-offline">
                        <strong>Network Sync</strong><br>
                        Offline Mode
                    </div>
                    <div class="status-item status-online">
                        <strong>Logger</strong><br>
                        Active
                    </div>
                </div>
            </div>
            
            <!-- Engine Metrics -->
            <div class="card">
                <h3>🚢 Engine Metrics</h3>
                <div class="status-grid">
                    <div class="metric">
                        <div class="metric-value">87.2°C</div>
                        <div class="metric-label">Engine Temp</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value">1,245</div>
                        <div class="metric-label">RPM</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value">2.4</div>
                        <div class="metric-label">Oil Pressure</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value">Normal</div>
                        <div class="metric-label">Status</div>
                    </div>
                </div>
            </div>
            
            <!-- Data Summary -->
            <div class="card">
                <h3>📊 Data Summary</h3>
                <div class="status-grid">
                    <div class="metric">
                        <div class="metric-value">156</div>
                        <div class="metric-label">Total Logs</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value">2.3MB</div>
                        <div class="metric-label">DB Size</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value">12h</div>
                        <div class="metric-label">Runtime</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value">Yesterday</div>
                        <div class="metric-label">Last Sync</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <h3>⚡ Quick Actions</h3>
            <div class="quick-actions">
                <a href="#" class="action-btn" onclick="addLogEntry()">Add Log Entry</a>
                <a href="#" class="action-btn" onclick="startSync()">Start Sync</a>
                <a href="#" class="action-btn secondary" onclick="exportData()">Export Data</a>
                <a href="#" class="action-btn secondary" onclick="viewReports()">View Reports</a>
            </div>
        </div>
        
        <!-- Recent Log Entries -->
        <div class="card">
            <h3>📝 Recent Log Entries</h3>
            <div class="log-entry">
                <div class="log-time"><?php echo date('Y-m-d H:i:s', strtotime('-15 minutes')); ?></div>
                <strong>Engine Check:</strong> All systems nominal. Temperature within normal range.
            </div>
            <div class="log-entry">
                <div class="log-time"><?php echo date('Y-m-d H:i:s', strtotime('-45 minutes')); ?></div>
                <strong>Routine Maintenance:</strong> Oil level checked and topped off.
            </div>
            <div class="log-entry">
                <div class="log-time"><?php echo date('Y-m-d H:i:s', strtotime('-2 hours')); ?></div>
                <strong>System Start:</strong> Vessel logger initialized successfully.
            </div>
        </div>
    </div>
    
    <script>
        function addLogEntry() {
            alert('Add Log Entry feature would open a form to record new engine room observations and measurements.');
        }
        
        function startSync() {
            alert('Sync feature would connect to the main server (when network available) to upload logged data.');
        }
        
        function exportData() {
            alert('Export feature would generate CSV/PDF reports of logged data for offline analysis.');
        }
        
        function viewReports() {
            alert('Reports feature would show detailed analytics and trending data from engine logs.');
        }
        
        // Update time every second
        setInterval(() => {
            const timeElements = document.querySelectorAll('.header-info');
            const now = new Date().toLocaleString();
            timeElements.forEach(el => {
                el.innerHTML = el.innerHTML.replace(/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/, now);
            });
        }, 1000);
    </script>
</body>
</html>
