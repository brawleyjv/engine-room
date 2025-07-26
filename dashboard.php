<?php
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

// Require login first
require_login();

// Check if vessel is selected - if not, redirect to selection
if (!has_vessel_selected()) {
    header('Location: select_vessel.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$current_user = get_logged_in_user();
$current_vessel = get_current_vessel($conn);

// Function to get latest entries for each equipment type
function getLatestEntries($conn, $table, $vessel_id, $limit = 5) {
    $sql = "SELECT e.*, COALESCE(CONCAT(u.FirstName, ' ', u.LastName), 'Unknown User') as RecordedByName 
            FROM $table e 
            LEFT JOIN users u ON e.RecordedBy = u.UserID 
            WHERE e.VesselID = ? 
            ORDER BY e.EntryDate DESC, e.Timestamp DESC 
            LIMIT $limit";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $vessel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Function to get equipment counts
function getEquipmentCounts($conn, $vessel_id) {
    $counts = [];
    $tables = ['mainengines', 'generators', 'gears'];
    
    foreach ($tables as $table) {
        $sql = "SELECT COUNT(*) as total, 
                       COUNT(CASE WHEN Side = 'Port' THEN 1 END) as port,
                       COUNT(CASE WHEN Side = 'Starboard' THEN 1 END) as starboard
                FROM $table WHERE VesselID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $vessel_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $counts[$table] = $result ? $result->fetch_assoc() : ['total' => 0, 'port' => 0, 'starboard' => 0];
    }
    
    return $counts;
}

$vessel_id = $current_vessel['VesselID'];
$counts = getEquipmentCounts($conn, $vessel_id);
$latest_mainengines = getLatestEntries($conn, 'mainengines', $vessel_id, 3);
$latest_generators = getLatestEntries($conn, 'generators', $vessel_id, 3);
$latest_gears = getLatestEntries($conn, 'gears', $vessel_id, 3);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Vessel Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #1e3c72;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #1e3c72;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1em;
        }
        
        .recent-entries {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .recent-entries h3 {
            color: #1e3c72;
            margin-bottom: 15px;
            border-bottom: 2px solid #1e3c72;
            padding-bottom: 10px;
        }
        
        .recent-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .recent-item:last-child {
            border-bottom: none;
        }
        
        .side-indicator {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .side-port {
            background: #ffe6e6;
            color: #dc3545;
        }
        
        .side-starboard {
            background: #e6f7e6;
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <div>
                    <h1>⚙️ Equipment Dashboard</h1>
                    <div style="color: #666; font-size: 16px;">
                        🚢 <strong><?php echo htmlspecialchars($current_vessel['VesselName']); ?></strong> 
                        (<?php echo htmlspecialchars($current_vessel['VesselType']); ?>)
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="margin-bottom: 10px; color: #666;">
                        Welcome, <?php echo htmlspecialchars($current_user['full_name']); ?>
                    </div>
                    <div>
                        <a href="select_vessel.php" class="btn btn-secondary" style="margin-right: 10px;">Switch Vessel</a>
                        <?php if (is_admin()): ?>
                            <a href="manage_users.php" class="btn btn-warning" style="margin-right: 10px;">Manage Users</a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    </div>
                </div>
            </div>
            <p><a href="index.php" class="btn btn-info">← Back to Home</a></p>
        </header>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $counts['mainengines']['total'] ?></div>
                <div class="stat-label">Main Engine Logs</div>
                <small style="color: #999;">
                    Port: <?= $counts['mainengines']['port'] ?> | 
                    Starboard: <?= $counts['mainengines']['starboard'] ?>
                </small>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $counts['generators']['total'] ?></div>
                <div class="stat-label">Generator Logs</div>
                <small style="color: #999;">
                    Port: <?= $counts['generators']['port'] ?> | 
                    Starboard: <?= $counts['generators']['starboard'] ?>
                </small>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= $counts['gears']['total'] ?></div>
                <div class="stat-label">Gear Logs</div>
                <small style="color: #999;">
                    Port: <?= $counts['gears']['port'] ?> | 
                    Starboard: <?= $counts['gears']['starboard'] ?>
                </small>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?= array_sum(array_column($counts, 'total')) ?></div>
                <div class="stat-label">Total Entries</div>
                <small style="color: #999;">All Equipment Types</small>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
            
            <!-- Recent Main Engine Entries -->
            <div class="recent-entries">
                <h3>🔧 Recent Main Engine Logs</h3>
                <?php if (empty($latest_mainengines)): ?>
                    <p style="color: #666; text-align: center;">No entries yet</p>
                <?php else: ?>
                    <?php foreach ($latest_mainengines as $entry): ?>
                    <div class="recent-item">
                        <div>
                            <strong><?= htmlspecialchars($entry['EntryDate']) ?></strong>
                            <span class="side-indicator side-<?= strtolower($entry['Side']) ?>">
                                <?= htmlspecialchars($entry['Side']) ?>
                            </span>
                            <br>
                            <small style="color: #666;">
                                RPM: <?= $entry['RPM'] ?? 'N/A' ?> | 
                                Hrs: <?= $entry['MainHrs'] ?? 'N/A' ?> | 
                                Oil P: <?= ($entry['OilPressure'] ?? 'N/A') . ($entry['OilPressure'] ? ' PSI' : '') ?> | 
                                Water T: <?= ($entry['WaterTemp'] ?? 'N/A') . ($entry['WaterTemp'] ? '°F' : '') ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="view_logs.php?equipment=mainengines" class="btn btn-primary">View All</a>
                </div>
            </div>
            
            <!-- Recent Generator Entries -->
            <div class="recent-entries">
                <h3>⚡ Recent Generator Logs</h3>
                <?php if (empty($latest_generators)): ?>
                    <p style="color: #666; text-align: center;">No entries yet</p>
                <?php else: ?>
                    <?php foreach ($latest_generators as $entry): ?>
                    <div class="recent-item">
                        <div>
                            <strong><?= htmlspecialchars($entry['EntryDate']) ?></strong>
                            <span class="side-indicator side-<?= strtolower($entry['Side']) ?>">
                                <?= htmlspecialchars($entry['Side']) ?>
                            </span>
                            <br>
                            <small style="color: #666;">
                                Hrs: <?= $entry['GenHrs'] ?? 'N/A' ?> | 
                                Oil P: <?= ($entry['OilPress'] ?? 'N/A') . ($entry['OilPress'] ? ' PSI' : '') ?> | 
                                Fuel P: <?= ($entry['FuelPress'] ?? 'N/A') . ($entry['FuelPress'] ? ' PSI' : '') ?> | 
                                Water T: <?= ($entry['WaterTemp'] ?? 'N/A') . ($entry['WaterTemp'] ? '°F' : '') ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="view_logs.php?equipment=generators" class="btn btn-primary">View All</a>
                </div>
            </div>
            
            <!-- Recent Gear Entries -->
            <div class="recent-entries">
                <h3>⚙️ Recent Gear Logs</h3>
                <?php if (empty($latest_gears)): ?>
                    <p style="color: #666; text-align: center;">No entries yet</p>
                <?php else: ?>
                    <?php foreach ($latest_gears as $entry): ?>
                    <div class="recent-item">
                        <div>
                            <strong><?= htmlspecialchars($entry['EntryDate']) ?></strong>
                            <span class="side-indicator side-<?= strtolower($entry['Side']) ?>">
                                <?= htmlspecialchars($entry['Side']) ?>
                            </span>
                            <br>
                            <small style="color: #666;">
                                Hrs: <?= $entry['GearHrs'] ?? 'N/A' ?> | 
                                Oil P: <?= ($entry['OilPress'] ?? 'N/A') . ($entry['OilPress'] ? ' PSI' : '') ?> | 
                                Temp: <?= ($entry['Temp'] ?? 'N/A') . ($entry['Temp'] ? '°F' : '') ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="view_logs.php?equipment=gears" class="btn btn-primary">View All</a>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="add_log.php" class="btn btn-success">➕ Add New Entry</a>
            <a href="view_logs.php" class="btn btn-primary">📊 View All Logs</a>
        </div>
        
        <footer>
            <p>&copy; 2025 Vessel Data Logger | <a href="index.php">Home</a></p>
        </footer>
    </div>
</body>
</html>
