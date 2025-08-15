<?php
/**
 * Engineer Dashboard - Chief Engineer, Assistant Engineer
 * Engine room operations, maintenance, and system monitoring
 */

// Define constant and start session (only if not already defined)
if (!defined('VESSEL_LOGGER')) {
    define('VESSEL_LOGGER', true);
}

session_start();

// Check authentication and role
if (!isset($_SESSION['username']) || $_SESSION['user_role'] !== 'engineer') {
    header('Location: simple_login.php');
    exit;
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

$user_name = $_SESSION['full_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'crew';
$role_display = ucfirst(str_replace('_', ' ', $user_role));

// Sample engine data (would come from database)
$engine_status = [
    'main_engine' => [
        'rpm' => 1250,
        'load' => 78.5,
        'temp' => 185,
        'oil_pressure' => 65,
        'status' => 'running'
    ],
    'generator_1' => [
        'rpm' => 1800,
        'load' => 45.2,
        'temp' => 165,
        'oil_pressure' => 58,
        'status' => 'running'
    ],
    'generator_2' => [
        'rpm' => 1800,
        'load' => 52.8,
        'temp' => 172,
        'oil_pressure' => 61,
        'status' => 'running'
    ]
];

$maintenance_items = [
    ['task' => 'Main Engine Oil Change', 'due' => '2024-01-15', 'priority' => 'high'],
    ['task' => 'Generator Filter Replacement', 'due' => '2024-01-10', 'priority' => 'medium'],
    ['task' => 'Cooling System Inspection', 'due' => '2024-01-20', 'priority' => 'low'],
    ['task' => 'Fuel System Check', 'due' => '2024-01-12', 'priority' => 'medium']
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engineer Dashboard - <?php echo htmlspecialchars($role_display); ?></title>
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
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .header-info {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .header-right {
            text-align: right;
        }
        
        .user-info {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
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
            color: #dc3545;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav a:hover, .nav a.active {
            background: #dc3545;
            color: white;
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
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
            color: #dc3545;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .engine-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .engine-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #dc3545;
        }
        
        .engine-title {
            font-size: 1rem;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .engine-status {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
        }
        
        .status-value {
            font-weight: bold;
            color: #333;
        }
        
        .status-running {
            color: #28a745;
        }
        
        .status-warning {
            color: #ffc107;
        }
        
        .status-critical {
            color: #dc3545;
        }
        
        .maintenance-list {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .maintenance-item {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .maintenance-item.high {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        
        .maintenance-item.medium {
            border-left-color: #ffc107;
            background: #fffef5;
        }
        
        .maintenance-item.low {
            border-left-color: #28a745;
            background: #f8fff8;
        }
        
        .maintenance-task {
            font-weight: 600;
        }
        
        .maintenance-due {
            font-size: 0.9rem;
            color: #666;
        }
        
        .priority-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .priority-high {
            background: #dc3545;
            color: white;
        }
        
        .priority-medium {
            background: #ffc107;
            color: #333;
        }
        
        .priority-low {
            background: #28a745;
            color: white;
        }
        
        .log-entry-form {
            display: grid;
            gap: 1rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .system-readings {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .reading-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .reading-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #dc3545;
        }
        
        .reading-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>⚙️ Engineer Dashboard</h1>
            <div class="header-info">
                Engine Room Operations | Live Monitoring | <?php echo date('Y-m-d H:i:s'); ?>
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <strong><?php echo htmlspecialchars($user_name); ?></strong><br>
                <span style="opacity: 0.8;"><?php echo htmlspecialchars($role_display); ?></span>
            </div>
            <a href="logout.php" class="logout-btn">🔓 Logout</a>
        </div>
    </div>
    
    <div class="nav">
        <a href="#" class="active">Dashboard</a>
        <a href="#engines">Engines</a>
        <a href="#maintenance">Maintenance</a>
        <a href="#fuel">Fuel Systems</a>
        <a href="#alarms">Alarms</a>
        <a href="#logs">Engine Logs</a>
    </div>
    
    <div class="container">
        <!-- Engine Status Cards -->
        <div class="engine-grid">
            <?php foreach ($engine_status as $engine_name => $data): ?>
            <div class="engine-card">
                <div class="engine-title"><?php echo ucfirst(str_replace('_', ' ', $engine_name)); ?></div>
                <div class="engine-status">
                    <div class="status-item">
                        <span>RPM:</span>
                        <span class="status-value"><?php echo $data['rpm']; ?></span>
                    </div>
                    <div class="status-item">
                        <span>Load:</span>
                        <span class="status-value"><?php echo $data['load']; ?>%</span>
                    </div>
                    <div class="status-item">
                        <span>Temp:</span>
                        <span class="status-value"><?php echo $data['temp']; ?>°F</span>
                    </div>
                    <div class="status-item">
                        <span>Oil Pressure:</span>
                        <span class="status-value"><?php echo $data['oil_pressure']; ?> PSI</span>
                    </div>
                    <div class="status-item" style="grid-column: span 2; text-align: center; margin-top: 0.5rem;">
                        <span class="status-<?php echo $data['status']; ?>">
                            <?php echo strtoupper($data['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="dashboard-grid">
            <div>
                <!-- System Alerts -->
                <div class="alert alert-warning">
                    <strong>⚠️ Maintenance Due:</strong> Main engine oil change scheduled for tomorrow
                </div>
                
                <!-- Engine Log Entry Form -->
                <div class="card">
                    <h3>📝 Engine Room Log Entry</h3>
                    <form class="log-entry-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Log Type:</label>
                                <select>
                                    <option>Engine Operation</option>
                                    <option>Maintenance Performed</option>
                                    <option>Fuel System</option>
                                    <option>Generator Operation</option>
                                    <option>Alarm/Fault</option>
                                    <option>Temperature Reading</option>
                                    <option>General Entry</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Time:</label>
                                <input type="datetime-local" value="<?php echo date('Y-m-d\TH:i'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Equipment:</label>
                                <select>
                                    <option>Main Engine</option>
                                    <option>Generator 1</option>
                                    <option>Generator 2</option>
                                    <option>Fuel System</option>
                                    <option>Cooling System</option>
                                    <option>Hydraulic System</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Priority:</label>
                                <select>
                                    <option>Normal</option>
                                    <option>Important</option>
                                    <option>Critical</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- System Readings -->
                        <div class="system-readings">
                            <div class="reading-item">
                                <div class="reading-value"><?php echo $engine_status['main_engine']['rpm']; ?></div>
                                <div class="reading-label">Main RPM</div>
                            </div>
                            <div class="reading-item">
                                <div class="reading-value"><?php echo $engine_status['main_engine']['temp']; ?>°</div>
                                <div class="reading-label">Engine Temp</div>
                            </div>
                            <div class="reading-item">
                                <div class="reading-value"><?php echo $engine_status['main_engine']['oil_pressure']; ?></div>
                                <div class="reading-label">Oil Pressure</div>
                            </div>
                            <div class="reading-item">
                                <div class="reading-value"><?php echo $engine_status['main_engine']['load']; ?>%</div>
                                <div class="reading-label">Load</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Log Entry Details:</label>
                            <textarea placeholder="Enter detailed engine room log entry... Include readings, maintenance performed, observations, etc."></textarea>
                        </div>
                        
                        <div class="form-row">
                            <button type="submit" class="btn">💾 Save Engine Log</button>
                            <button type="button" class="btn btn-secondary">📋 Templates</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div>
                <!-- Quick Actions -->
                <div class="card">
                    <h3>⚡ Quick Actions</h3>
                    <div class="quick-actions">
                        <button class="btn" onclick="emergencyShutdown()">🛑 Emergency Stop</button>
                        <button class="btn btn-secondary" onclick="alarmAck()">🔔 Ack Alarms</button>
                        <button class="btn btn-success" onclick="startEngine()">▶️ Start Engine</button>
                        <button class="btn btn-secondary" onclick="stopEngine()">⏹️ Stop Engine</button>
                    </div>
                    <div class="quick-actions">
                        <button class="btn btn-secondary" onclick="fuelTransfer()">⛽ Fuel Transfer</button>
                        <button class="btn btn-secondary" onclick="maintenanceMode()">🔧 Maintenance</button>
                    </div>
                </div>
                
                <!-- Maintenance Schedule -->
                <div class="card">
                    <h3>🔧 Maintenance Schedule</h3>
                    <div class="maintenance-list">
                        <?php foreach ($maintenance_items as $item): ?>
                        <div class="maintenance-item <?php echo $item['priority']; ?>">
                            <div>
                                <div class="maintenance-task"><?php echo $item['task']; ?></div>
                                <div class="maintenance-due">Due: <?php echo $item['due']; ?></div>
                            </div>
                            <span class="priority-badge priority-<?php echo $item['priority']; ?>">
                                <?php echo $item['priority']; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Fuel Status -->
                <div class="card">
                    <h3>⛽ Fuel & Fluid Levels</h3>
                    <div class="fuel-status">
                        <div class="status-item">
                            <span>Main Fuel Tank:</span>
                            <span class="status-value">85.4%</span>
                        </div>
                        <div class="status-item">
                            <span>Day Tank:</span>
                            <span class="status-value">92.1%</span>
                        </div>
                        <div class="status-item">
                            <span>Lube Oil:</span>
                            <span class="status-value">78.5%</span>
                        </div>
                        <div class="status-item">
                            <span>Hydraulic Oil:</span>
                            <span class="status-value">88.9%</span>
                        </div>
                        <div class="status-item">
                            <span>Coolant:</span>
                            <span class="status-value">95.2%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function emergencyShutdown() {
            if (confirm('Are you sure you want to initiate emergency shutdown? This will stop all engines immediately.')) {
                alert('Emergency shutdown protocol initiated. All engines stopping.');
            }
        }
        
        function alarmAck() {
            alert('All active alarms acknowledged.');
        }
        
        function startEngine() {
            alert('Engine start sequence would begin with pre-start checks.');
        }
        
        function stopEngine() {
            if (confirm('Confirm engine shutdown?')) {
                alert('Engine shutdown sequence initiated.');
            }
        }
        
        function fuelTransfer() {
            alert('Fuel transfer controls would open transfer pump management.');
        }
        
        function maintenanceMode() {
            alert('Maintenance mode would enable lockout/tagout procedures and maintenance logging.');
        }
        
        // Update time and engine readings every 15 seconds
        setInterval(() => {
            const now = new Date().toLocaleString();
            document.querySelector('.header-info').innerHTML = 
                'Engine Room Operations | Live Monitoring | ' + now;
            
            // Simulate slight variations in readings
            const readings = document.querySelectorAll('.reading-value');
            readings.forEach(reading => {
                if (reading.textContent.includes('°')) {
                    const temp = parseInt(reading.textContent);
                    reading.textContent = (temp + Math.random() * 2 - 1).toFixed(0) + '°';
                }
            });
        }, 15000);
    </script>
</body>
</html>
