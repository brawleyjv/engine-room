<?php
/**
 * Wheelhouse Dashboard - Captain, Pilot, Wheelman
 * Bridge operations, navigation, and crew management
 */

// Define constant and start session (only if not already defined)
if (!defined('VESSEL_LOGGER')) {
    define('VESSEL_LOGGER', true);
}

session_start();

// Check authentication and role
if (!isset($_SESSION['username']) || $_SESSION['user_role'] !== 'wheelhouse') {
    header('Location: simple_login.php');
    exit;
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

$user_name = $_SESSION['user_name'] ?? 'Wheelhouse User';
$username = $_SESSION['username'] ?? 'user';
$vessel_name = 'MV Ocean Explorer';

// Handle navigation data updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_navigation'])) {
    $destination = trim($_POST['destination'] ?? '');
    $eta = trim($_POST['eta'] ?? '');
    
    if ($destination && $eta) {
        try {
            $db_path = __DIR__ . '/vessel_data.sqlite';
            $pdo = new PDO('sqlite:' . $db_path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Insert new navigation data
            $stmt = $pdo->prepare("INSERT INTO navigation_data (destination, eta, updated_by) VALUES (?, ?, ?)");
            $stmt->execute([$destination, $eta, $user_name]);
            
            $navigation_updated = true;
        } catch (Exception $e) {
            error_log("Navigation update error: " . $e->getMessage());
            $navigation_error = "Failed to update navigation data";
        }
    }
}

// Load current navigation data
$current_navigation = ['destination' => '', 'eta' => ''];
try {
    $db_path = __DIR__ . '/vessel_data.sqlite';
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT destination, eta FROM navigation_data ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $nav_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($nav_data) {
        $current_navigation = $nav_data;
    }
} catch (Exception $e) {
    error_log("Navigation load error: " . $e->getMessage());
}

// Load current crew on board
$current_crew = [];
try {
    $db_path = __DIR__ . '/vessel_data.sqlite';
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT name, date_on, date_off, twic_exp_date FROM crew_members ORDER BY name");
    $current_crew = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Crew load error: " . $e->getMessage());
}

$fuel_data = [
    'main_fuel' => ['current' => 85.4, 'capacity' => 1000, 'consumption' => 2.3],
    'lube_oil' => ['current' => 92.1, 'capacity' => 50, 'consumption' => 0.1],
    'hydraulic_oil' => ['current' => 78.5, 'capacity' => 30, 'consumption' => 0.05],
    'gear_oil' => ['current' => 88.9, 'capacity' => 25, 'consumption' => 0.02]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wheelhouse Dashboard - <?php echo htmlspecialchars($role_display); ?></title>
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
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
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
            color: #1e3c72;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav a:hover, .nav a.active {
            background: #1e3c72;
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
            color: #1e3c72;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .vessel-status {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .status-item {
            padding: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
            border-left: 4px solid #1e3c72;
        }
        
        .status-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1e3c72;
        }
        
        .status-label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .fuel-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .fuel-item {
            text-align: center;
        }
        
        .fuel-gauge {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 0.5rem;
            position: relative;
            background: conic-gradient(#1e3c72 0deg, #1e3c72 var(--percentage), #e9ecef var(--percentage), #e9ecef 360deg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .fuel-gauge::before {
            content: '';
            width: 60px;
            height: 60px;
            background: white;
            border-radius: 50%;
            position: absolute;
        }
        
        .fuel-percentage {
            position: relative;
            z-index: 1;
            font-weight: bold;
            color: #1e3c72;
        }
        
        .fuel-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .fuel-consumption {
            font-size: 0.8rem;
            color: #28a745;
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
            background: #1e3c72;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2a5298;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .recent-logs {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .log-entry {
            background: #f8f9fa;
            border-left: 4px solid #1e3c72;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
        }
        
        .log-time {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .log-author {
            font-size: 0.8rem;
            color: #1e3c72;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>🚢 Wheelhouse Dashboard</h1>
            <div class="header-info">
                Bridge Operations | Live Status | <?php echo date('Y-m-d H:i:s'); ?>
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
        <a href="#navigation">Navigation</a>
        <a href="#crew">Crew Management</a>
        <a href="#logs">Vessel Logs</a>
        <a href="#weather">Weather</a>
        <a href="#communications">Comms</a>
    </div>
    
    <div class="container">
        <div class="dashboard-grid">
            <div>
                <!-- Vessel Status & Navigation -->
                <div class="card">
                    <h3>🧭 Vessel Status & Navigation</h3>
                    
                    <?php if (isset($navigation_updated)): ?>
                        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                            Navigation data updated successfully!
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($navigation_error)): ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                            <?php echo htmlspecialchars($navigation_error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" style="margin-bottom: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">Destination:</label>
                                <input type="text" name="destination" 
                                       value="<?php echo htmlspecialchars($current_navigation['destination']); ?>"
                                       placeholder="e.g., Port of Houston"
                                       style="width: 100%; padding: 8px; border: 2px solid #e1e5e9; border-radius: 5px;">
                            </div>
                            <div>
                                <label style="display: block; font-weight: 600; margin-bottom: 5px;">ETA:</label>
                                <input type="datetime-local" name="eta" 
                                       value="<?php echo $current_navigation['eta'] ? date('Y-m-d\TH:i', strtotime($current_navigation['eta'])) : ''; ?>"
                                       style="width: 100%; padding: 8px; border: 2px solid #e1e5e9; border-radius: 5px;">
                            </div>
                        </div>
                        <button type="submit" name="update_navigation" 
                                style="background: #2196F3; color: white; border: none; padding: 10px 20px; border-radius: 5px; margin-top: 10px; cursor: pointer;">
                            Update Navigation
                        </button>
                    </form>
                    
                    <div class="vessel-status">
                        <div class="status-item">
                            <div class="status-value"><?php echo $current_navigation['destination'] ?: 'Not Set'; ?></div>
                            <div class="status-label">Current Destination</div>
                        </div>
                        <div class="status-item">
                            <div class="status-value">
                                <?php 
                                if ($current_navigation['eta']) {
                                    echo date('M j, Y H:i', strtotime($current_navigation['eta']));
                                } else {
                                    echo 'Not Set';
                                }
                                ?>
                            </div>
                            <div class="status-label">Estimated Time of Arrival</div>
                        </div>
                    </div>
                </div>
                
                <!-- Log Entry Form -->
                <div class="card">
                    <h3>📝 Create Vessel Log Entry</h3>
                    <form class="log-entry-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Log Type:</label>
                                <select>
                                    <option>Navigation Entry</option>
                                    <option>Weather Observation</option>
                                    <option>Crew Change</option>
                                    <option>Port Operations</option>
                                    <option>Safety Incident</option>
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
                                <label>Position (Optional):</label>
                                <input type="text" placeholder="e.g., 29.7604, -95.3698 or GPS coordinates">
                            </div>
                            <div class="form-group">
                                <label>Reference:</label>
                                <input type="text" placeholder="e.g., 5 miles SE of Houston Ship Channel">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Log Entry:</label>
                            <textarea placeholder="Enter detailed log entry..."></textarea>
                        </div>
                        
                        <div class="form-row">
                            <button type="submit" class="btn">💾 Save Log Entry</button>
                            <button type="button" class="btn btn-secondary">📋 Templates</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div>
                <!-- Fuel & Fluids Monitor -->
                <div class="card">
                    <h3>⛽ Fuel & Fluids Status</h3>
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">
                        <em>Data from Engine Room - Read Only</em>
                    </p>
                    <div class="fuel-grid">
                        <?php foreach ($fuel_data as $fuel_type => $data): ?>
                        <div class="fuel-item">
                            <div class="fuel-gauge" style="--percentage: <?php echo ($data['current'] / $data['capacity'] * 100 * 3.6); ?>deg">
                                <div class="fuel-percentage"><?php echo round($data['current'] / $data['capacity'] * 100); ?>%</div>
                            </div>
                            <div class="fuel-label"><?php echo ucfirst(str_replace('_', ' ', $fuel_type)); ?></div>
                            <div class="fuel-consumption"><?php echo $data['consumption']; ?> gph</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <h3>⚡ Quick Actions</h3>
                    <div class="quick-actions">
                        <button class="btn" onclick="emergencyAlert()">🚨 Emergency</button>
                        <button class="btn" onclick="weatherUpdate()">🌤️ Weather</button>
                        <button class="btn btn-secondary" onclick="crewManagement()">👥 Crew</button>
                        <button class="btn btn-secondary" onclick="navigationPlot()">🗺️ Navigation</button>
                    </div>
                </div>
                
                <!-- Current Crew On Board -->
                <div class="card">
                    <h3>👥 Current Crew On Board (<?php echo count($current_crew); ?>)</h3>
                    <?php if (empty($current_crew)): ?>
                        <p>No crew members on board</p>
                    <?php else: ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <tr style="background: #f5f5f5;">
                                <th style="padding: 8px; border: 1px solid #ddd;">Name</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Date On</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">Expected Off</th>
                                <th style="padding: 8px; border: 1px solid #ddd;">TWIC Expires</th>
                            </tr>
                            <?php foreach ($current_crew as $crew): ?>
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($crew['name']); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($crew['date_on']); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;"><?php echo htmlspecialchars($crew['date_off'] ?: 'On board'); ?></td>
                                <td style="padding: 8px; border: 1px solid #ddd;"><?php echo htmlspecialchars($crew['twic_exp_date'] ?: 'Not provided'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                    <div style="margin-top: 15px; text-align: center;">
                        <a href="crew_management.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                            👥 Manage Crew
                        </a>
                    </div>
                </div>
                
                <!-- Recent Vessel Logs -->
                <div class="card">
                    <h3>📖 Recent Vessel Logs</h3>
                    <div class="recent-logs">
                        <div class="log-entry">
                            <div class="log-time"><?php echo date('Y-m-d H:i:s', strtotime('-30 minutes')); ?></div>
                            <strong>Navigation:</strong> Course change to 045°. Weather conditions favorable.
                            <div class="log-author">Captain Smith</div>
                        </div>
                        <div class="log-entry">
                            <div class="log-time"><?php echo date('Y-m-d H:i:s', strtotime('-2 hours')); ?></div>
                            <strong>Crew Change:</strong> Watch change completed. No incidents to report.
                            <div class="log-author">Pilot Johnson</div>
                        </div>
                        <div class="log-entry">
                            <div class="log-time"><?php echo date('Y-m-d H:i:s', strtotime('-4 hours')); ?></div>
                            <strong>Weather:</strong> Wind shift to SW 15 knots. Sea state 2-3.
                            <div class="log-author">Wheelman Davis</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function emergencyAlert() {
            alert('Emergency Alert System would activate emergency protocols and notifications.');
        }
        
        function weatherUpdate() {
            alert('Weather Update would pull latest meteorological data and forecasts.');
        }
        
        function crewManagement() {
            window.location.href = 'crew_management.php';
        }
        
        function navigationPlot() {
            alert('Navigation Plot would open detailed chart plotting and course management tools.');
        }
        
        // Update time and status every 30 seconds
        setInterval(() => {
            const now = new Date().toLocaleString();
            document.querySelector('.header-info').innerHTML = 
                'Bridge Operations | Live Status | ' + now;
        }, 30000);
    </script>
</body>
</html>
