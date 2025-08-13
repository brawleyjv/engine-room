<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_functions.php';
require_once __DIR__ . '/../../vessel_functions.php';
require_once __DIR__ . '/../offline/sync_manager.php';

// Require login first
require_login();

// Check if vessel is selected - if not, redirect to selection
if (!has_vessel_selected()) {
    header('Location: select_vessel.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$current_user = get_logged_in_user();
$current_vessel = get_current_vessel($conn);

// Initialize offline system
$sqlite_path = "../offline/data/vessel_{$current_vessel['VesselID']}.db";
$is_online = checkInternetConnection();
$offline_available = file_exists($sqlite_path);

// Use offline database if available and online database is unavailable
$use_offline = false;
if (!$is_online && $offline_available) {
    $use_offline = true;
} elseif ($offline_available) {
    // Try to initialize sync manager even when online to get status
    try {
        $sync_manager = new OfflineSyncManager($conn, $sqlite_path, $current_vessel['VesselID'], $current_user['user_id']);
        $offline_status = $sync_manager->getOfflineStatus();
    } catch (Exception $e) {
        $offline_status = ['status' => 'error', 'pending_changes' => 0];
    }
}

// Function to get latest entries for each equipment type
function getLatestEntries($conn_or_sqlite, $table, $vessel_id, $limit = 5, $is_sqlite = false) {
    if ($is_sqlite) {
        $sql = "SELECT e.*, COALESCE(u.FirstName || ' ' || u.LastName, 'Unknown User') as RecordedByName 
                FROM $table e 
                LEFT JOIN users u ON e.RecordedBy = u.UserID 
                WHERE e.VesselID = ? 
                ORDER BY e.EntryDate DESC, e.Timestamp DESC 
                LIMIT $limit";
        $stmt = $conn_or_sqlite->prepare($sql);
        $stmt->bindValue(1, $vessel_id);
        $result = $stmt->execute();
        
        $entries = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $entries[] = $row;
        }
        return $entries;
    } else {
        $sql = "SELECT e.*, COALESCE(CONCAT(u.FirstName, ' ', u.LastName), 'Unknown User') as RecordedByName 
                FROM $table e 
                LEFT JOIN users u ON e.RecordedBy = u.UserID 
                WHERE e.VesselID = ? 
                ORDER BY e.EntryDate DESC, e.Timestamp DESC 
                LIMIT $limit";
        $stmt = $conn_or_sqlite->prepare($sql);
        $stmt->bind_param('i', $vessel_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

// Function to get equipment counts
function getEquipmentCounts($conn_or_sqlite, $vessel_id, $is_sqlite = false) {
    $counts = [];
    $tables = ['mainengines', 'generators', 'gears'];
    
    foreach ($tables as $table) {
        if ($is_sqlite) {
            $sql = "SELECT COUNT(*) as total, 
                           COUNT(CASE WHEN Side = 'Port' THEN 1 END) as port,
                           COUNT(CASE WHEN Side = 'Starboard' THEN 1 END) as starboard
                    FROM $table WHERE VesselID = ?";
            $stmt = $conn_or_sqlite->prepare($sql);
            $stmt->bindValue(1, $vessel_id);
            $result = $stmt->execute();
            $counts[$table] = $result->fetchArray(SQLITE3_ASSOC) ?: ['total' => 0, 'port' => 0, 'starboard' => 0];
        } else {
            $sql = "SELECT COUNT(*) as total, 
                           COUNT(CASE WHEN Side = 'Port' THEN 1 END) as port,
                           COUNT(CASE WHEN Side = 'Starboard' THEN 1 END) as starboard
                    FROM $table WHERE VesselID = ?";
            $stmt = $conn_or_sqlite->prepare($sql);
            $stmt->bind_param('i', $vessel_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $counts[$table] = $result ? $result->fetch_assoc() : ['total' => 0, 'port' => 0, 'starboard' => 0];
        }
    }
    
    return $counts;
}

// Function to get recent hours for trend analysis
function getRecentHours($conn_or_sqlite, $table, $vessel_id, $side, $days = 7, $is_sqlite = false) {
    $hrs_field = $table === 'mainengines' ? 'MainHrs' : ($table === 'generators' ? 'GenHrs' : 'GearHrs');
    
    if ($is_sqlite) {
        $sql = "SELECT EntryDate, $hrs_field as Hours 
                FROM $table 
                WHERE VesselID = ? AND Side = ? AND EntryDate >= date('now', '-$days days')
                ORDER BY EntryDate ASC";
        $stmt = $conn_or_sqlite->prepare($sql);
        $stmt->bindValue(1, $vessel_id);
        $stmt->bindValue(2, $side);
        $result = $stmt->execute();
        
        $hours = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hours[] = $row;
        }
        return $hours;
    } else {
        $sql = "SELECT EntryDate, $hrs_field as Hours 
                FROM $table 
                WHERE VesselID = ? AND Side = ? AND EntryDate >= DATE_SUB(NOW(), INTERVAL $days DAY)
                ORDER BY EntryDate ASC";
        $stmt = $conn_or_sqlite->prepare($sql);
        $stmt->bind_param('iss', $vessel_id, $side);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}

// Function to check internet connection
function checkInternetConnection() {
    $connected = @fopen("http://www.google.com:80/", "r");
    if ($connected) {
        fclose($connected);
        return true;
    }
    return false;
}

// Determine which database to use
if ($use_offline) {
    $sqlite_conn = new SQLite3($sqlite_path);
    $sqlite_conn->exec('PRAGMA foreign_keys = ON;');
    $db_conn = $sqlite_conn;
    $is_using_sqlite = true;
} else {
    $db_conn = $conn;
    $is_using_sqlite = false;
}

// Get dashboard data
$vessel_id = $current_vessel['VesselID'];
$latest_mainengines = getLatestEntries($db_conn, 'mainengines', $vessel_id, 5, $is_using_sqlite);
$latest_generators = getLatestEntries($db_conn, 'generators', $vessel_id, 5, $is_using_sqlite);
$latest_gears = getLatestEntries($db_conn, 'gears', $vessel_id, 5, $is_using_sqlite);
$equipment_counts = getEquipmentCounts($db_conn, $vessel_id, $is_using_sqlite);

// Get trend data for charts
$port_mainengine_trend = getRecentHours($db_conn, 'mainengines', $vessel_id, 'Port', 7, $is_using_sqlite);
$starboard_mainengine_trend = getRecentHours($db_conn, 'mainengines', $vessel_id, 'Starboard', 7, $is_using_sqlite);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engine Room Dashboard - <?php echo htmlspecialchars($current_vessel['VesselName']); ?></title>
    <link rel="stylesheet" href="../../style.css">
    <link rel="manifest" href="../offline/manifest.json">
    <meta name="theme-color" content="#2196F3">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }
        
        .equipment-stats {
            display: flex;
            justify-content: space-around;
            margin: 15px 0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #2196F3;
        }
        
        .stat-label {
            font-size: 0.9em;
            color: #666;
        }
        
        .recent-entries {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .entry-item {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .entry-item:last-child {
            border-bottom: none;
        }
        
        .entry-details {
            flex: 1;
        }
        
        .entry-side {
            font-weight: bold;
            color: #2196F3;
        }
        
        .entry-date {
            font-size: 0.9em;
            color: #666;
        }
        
        .entry-hours {
            font-weight: bold;
            color: #4CAF50;
        }
        
        .offline-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            z-index: 1000;
            font-size: 0.9em;
        }
        
        .online {
            background-color: #4CAF50;
            color: white;
        }
        
        .offline {
            background-color: #f44336;
            color: white;
        }
        
        .using-offline {
            background-color: #ff9800;
            color: white;
        }
        
        .pending-sync {
            background-color: #ff9800;
            color: white;
            margin-top: 5px;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8em;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .action-button {
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            transition: transform 0.2s ease;
            display: block;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }
        
        .action-button.secondary {
            background: linear-gradient(135deg, #757575, #616161);
        }
        
        .action-button.add {
            background: linear-gradient(135deg, #4CAF50, #388E3C);
        }
        
        .sync-info {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 0.9em;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
    </style>
</head>
<body>
    <!-- Connection Status Indicator -->
    <div class="offline-indicator <?php echo $is_online ? 'online' : ($use_offline ? 'using-offline' : 'offline'); ?>">
        <?php if ($is_online): ?>
            🟢 Online
            <?php if (isset($offline_status) && $offline_status['pending_changes'] > 0): ?>
                <div class="pending-sync">
                    <?php echo $offline_status['pending_changes']; ?> pending sync
                </div>
            <?php endif; ?>
        <?php elseif ($use_offline): ?>
            📱 Offline Mode
        <?php else: ?>
            🔴 Offline - No Local Data
        <?php endif; ?>
    </div>

    <div class="container">
        <div class="header">
            <h1>Engine Room Dashboard</h1>
            <p>Vessel: <strong><?php echo htmlspecialchars($current_vessel['VesselName']); ?></strong></p>
            <p>User: <strong><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></strong></p>
            
            <?php if ($use_offline): ?>
                <div class="sync-info">
                    <strong>Offline Mode:</strong> Data is being stored locally and will sync when connection is restored.
                </div>
            <?php elseif (!$offline_available && !$is_online): ?>
                <div class="sync-info" style="background: #ffe0e0; color: #d32f2f;">
                    <strong>No Offline Data:</strong> Please connect to the internet or ask an administrator to set up offline access.
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="<?php echo $offline_available ? 'add_log_offline.php' : 'add_log.php'; ?>" class="action-button add">
                ➕ Add Log Entry
            </a>
            <a href="view_logs.php" class="action-button secondary">
                📋 View All Logs
            </a>
            <a href="../../" class="action-button secondary">
                🏠 Main Dashboard
            </a>
            <?php if ($is_online && isset($sync_manager)): ?>
                <a href="?sync=now" class="action-button secondary">
                    🔄 Sync Now
                </a>
            <?php endif; ?>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Equipment Summary -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-title">Equipment Summary</div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4>Main Engines</h4>
                    <div class="equipment-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $equipment_counts['mainengines']['total']; ?></div>
                            <div class="stat-label">Total Entries</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $equipment_counts['mainengines']['port']; ?></div>
                            <div class="stat-label">Port</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $equipment_counts['mainengines']['starboard']; ?></div>
                            <div class="stat-label">Starboard</div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4>Generators</h4>
                    <div class="equipment-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $equipment_counts['generators']['total']; ?></div>
                            <div class="stat-label">Total Entries</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $equipment_counts['generators']['port']; ?></div>
                            <div class="stat-label">Port</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $equipment_counts['generators']['starboard']; ?></div>
                            <div class="stat-label">Starboard</div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4>Gears</h4>
                    <div class="equipment-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $equipment_counts['gears']['total']; ?></div>
                            <div class="stat-label">Total Entries</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $equipment_counts['gears']['port']; ?></div>
                            <div class="stat-label">Port</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $equipment_counts['gears']['starboard']; ?></div>
                            <div class="stat-label">Starboard</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Main Engine Entries -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-title">Recent Main Engine Entries</div>
                    <a href="view_logs.php?type=mainengines" class="btn-sm">View All</a>
                </div>
                <div class="recent-entries">
                    <?php if (empty($latest_mainengines)): ?>
                        <div class="no-data">No main engine entries found</div>
                    <?php else: ?>
                        <?php foreach ($latest_mainengines as $entry): ?>
                            <div class="entry-item">
                                <div class="entry-details">
                                    <div class="entry-side"><?php echo htmlspecialchars($entry['Side']); ?> Engine</div>
                                    <div class="entry-date"><?php echo htmlspecialchars($entry['EntryDate']); ?></div>
                                    <div style="font-size: 0.9em; color: #666;">
                                        By: <?php echo htmlspecialchars($entry['RecordedByName']); ?>
                                    </div>
                                </div>
                                <div class="entry-hours"><?php echo htmlspecialchars($entry['MainHrs']); ?> hrs</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Generator Entries -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-title">Recent Generator Entries</div>
                    <a href="view_logs.php?type=generators" class="btn-sm">View All</a>
                </div>
                <div class="recent-entries">
                    <?php if (empty($latest_generators)): ?>
                        <div class="no-data">No generator entries found</div>
                    <?php else: ?>
                        <?php foreach ($latest_generators as $entry): ?>
                            <div class="entry-item">
                                <div class="entry-details">
                                    <div class="entry-side"><?php echo htmlspecialchars($entry['Side']); ?> Generator</div>
                                    <div class="entry-date"><?php echo htmlspecialchars($entry['EntryDate']); ?></div>
                                    <div style="font-size: 0.9em; color: #666;">
                                        By: <?php echo htmlspecialchars($entry['RecordedByName']); ?>
                                    </div>
                                </div>
                                <div class="entry-hours"><?php echo htmlspecialchars($entry['GenHrs']); ?> hrs</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Gear Entries -->
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-title">Recent Gear Entries</div>
                    <a href="view_logs.php?type=gears" class="btn-sm">View All</a>
                </div>
                <div class="recent-entries">
                    <?php if (empty($latest_gears)): ?>
                        <div class="no-data">No gear entries found</div>
                    <?php else: ?>
                        <?php foreach ($latest_gears as $entry): ?>
                            <div class="entry-item">
                                <div class="entry-details">
                                    <div class="entry-side"><?php echo htmlspecialchars($entry['Side']); ?> Gear</div>
                                    <div class="entry-date"><?php echo htmlspecialchars($entry['EntryDate']); ?></div>
                                    <div style="font-size: 0.9em; color: #666;">
                                        By: <?php echo htmlspecialchars($entry['RecordedByName']); ?>
                                    </div>
                                </div>
                                <div class="entry-hours"><?php echo htmlspecialchars($entry['GearHrs']); ?> hrs</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="actions" style="margin-top: 30px;">
            <a href="../../" class="btn-secondary">← Back to Main Dashboard</a>
            <a href="add_log.php" class="btn-primary">Add New Entry</a>
            <a href="view_logs.php" class="btn-secondary">View All Logs</a>
        </div>
    </div>

    <script>
        // Service Worker registration for PWA support
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../offline/sw.js')
                .then(function(registration) {
                    console.log('Service Worker registered successfully');
                    
                    // Listen for updates
                    registration.addEventListener('updatefound', function() {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', function() {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // New version available
                                if (confirm('A new version is available. Reload to update?')) {
                                    window.location.reload();
                                }
                            }
                        });
                    });
                })
                .catch(function(error) {
                    console.log('Service Worker registration failed:', error);
                });
        }

        // Listen for sync messages from service worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', function(event) {
                if (event.data.type === 'SYNC_SUCCESS') {
                    // Show success notification
                    console.log('Background sync completed:', event.data.message);
                    // Optionally refresh the page or update UI
                }
            });
        }

        // Auto-refresh status every 30 seconds when online
        <?php if ($is_online): ?>
        setInterval(function() {
            // Check for pending sync status updates
            fetch('../offline/sync_endpoint.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'check_sync_status',
                    vessel_id: <?php echo $vessel_id; ?>,
                    user_id: <?php echo $current_user['user_id']; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.pending_changes > 0) {
                    // Update UI to show pending changes
                    console.log('Pending changes:', data.pending_changes);
                }
            })
            .catch(error => console.log('Status check failed:', error));
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>

<?php
// Handle manual sync request
if (isset($_GET['sync']) && $_GET['sync'] === 'now' && $is_online && isset($sync_manager)) {
    $sync_result = $sync_manager->performFullSync();
    if ($sync_result['success']) {
        echo "<script>
            alert('Sync completed successfully. Pushed: {$sync_result['pushed']}, Pulled: {$sync_result['pulled']}');
            window.location.href = 'dashboard.php';
        </script>";
    } else {
        echo "<script>
            alert('Sync failed: " . addslashes($sync_result['error']) . "');
        </script>";
    }
}

// Close SQLite connection if used
if (isset($sqlite_conn)) {
    $sqlite_conn->close();
}
?>
