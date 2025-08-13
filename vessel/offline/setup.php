<?php
/**
 * Offline System Setup Script
 * Initializes SQLite database and offline capabilities for a specific vessel
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../auth_functions.php';
require_once __DIR__ . '/sqlite_schema.php';
require_once __DIR__ . '/sync_manager.php';

// Require admin or vessel manager access
if (!is_logged_in()) {
    header('Location: ../../login.php');
    exit;
}

$current_user = get_logged_in_user();
$message = '';
$message_type = '';

// Handle form submission
if ($_POST) {
    $vessel_id = $_POST['vessel_id'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if (empty($vessel_id)) {
        $message = 'Please select a vessel.';
        $message_type = 'error';
    } else {
        try {
            switch ($action) {
                case 'initialize':
                    $result = initializeOfflineSystem($vessel_id, $current_user['user_id']);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'error';
                    break;
                    
                case 'reset':
                    $result = resetOfflineSystem($vessel_id);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'error';
                    break;
                    
                case 'sync':
                    $result = performManualSync($vessel_id, $current_user['user_id']);
                    $message = $result['message'];
                    $message_type = $result['success'] ? 'success' : 'error';
                    break;
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get list of vessels
$vessels_query = "SELECT VesselID, VesselName FROM vessels WHERE IsActive = 1 ORDER BY VesselName";
$vessels_result = $conn->query($vessels_query);

function initializeOfflineSystem($vessel_id, $user_id) {
    global $conn;
    
    // Create data directory if it doesn't exist
    $data_dir = __DIR__ . '/data';
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    $sqlite_path = $data_dir . "/vessel_{$vessel_id}.db";
    
    // Check if database already exists
    if (file_exists($sqlite_path)) {
        return [
            'success' => false,
            'message' => 'Offline database already exists for this vessel. Use Reset to recreate.'
        ];
    }
    
    // Initialize SQLite database
    $generator = new OfflineSchemaGenerator($sqlite_path, $conn);
    $sqlite_conn = $generator->generateSchema();
    $generator->seedInitialData($sqlite_conn, $vessel_id, $user_id);
    
    return [
        'success' => true,
        'message' => 'Offline system initialized successfully for vessel ID ' . $vessel_id
    ];
}

function resetOfflineSystem($vessel_id) {
    $data_dir = __DIR__ . '/data';
    $sqlite_path = $data_dir . "/vessel_{$vessel_id}.db";
    
    if (file_exists($sqlite_path)) {
        if (unlink($sqlite_path)) {
            return [
                'success' => true,
                'message' => 'Offline database reset successfully. You can now re-initialize.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete offline database file.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'No offline database found for this vessel.'
        ];
    }
}

function performManualSync($vessel_id, $user_id) {
    global $conn;
    
    $data_dir = __DIR__ . '/data';
    $sqlite_path = $data_dir . "/vessel_{$vessel_id}.db";
    
    if (!file_exists($sqlite_path)) {
        return [
            'success' => false,
            'message' => 'No offline database found. Please initialize first.'
        ];
    }
    
    $sync_manager = new OfflineSyncManager($conn, $sqlite_path, $vessel_id, $user_id);
    $sync_result = $sync_manager->performFullSync();
    
    if ($sync_result['success']) {
        $pushed = $sync_result['pushed'] ?? 0;
        $pulled = $sync_result['pulled'] ?? 0;
        return [
            'success' => true,
            'message' => "Sync completed successfully. Pushed: {$pushed} records, Pulled: {$pulled} records."
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Sync failed: ' . $sync_result['error']
        ];
    }
}

function getOfflineStatus($vessel_id) {
    $data_dir = __DIR__ . '/data';
    $sqlite_path = $data_dir . "/vessel_{$vessel_id}.db";
    
    if (!file_exists($sqlite_path)) {
        return [
            'initialized' => false,
            'status' => 'not_initialized',
            'pending_changes' => 0,
            'last_sync' => null,
            'file_size' => 0
        ];
    }
    
    try {
        $sqlite = new SQLite3($sqlite_path);
        
        // Get metadata
        $meta_query = "SELECT * FROM offline_metadata LIMIT 1";
        $result = $sqlite->query($meta_query);
        $metadata = $result->fetchArray(SQLITE3_ASSOC);
        
        // Get pending changes count
        $pending_query = "SELECT COUNT(*) as pending FROM sync_queue WHERE Status = 'pending'";
        $result = $sqlite->query($pending_query);
        $pending = $result->fetchArray(SQLITE3_ASSOC)['pending'];
        
        $sqlite->close();
        
        return [
            'initialized' => true,
            'status' => $metadata['Status'] ?? 'unknown',
            'pending_changes' => $pending,
            'last_sync' => $metadata['LastFullSync'] ?? null,
            'file_size' => round(filesize($sqlite_path) / 1024, 2) // Size in KB
        ];
    } catch (Exception $e) {
        return [
            'initialized' => false,
            'status' => 'error',
            'error' => $e->getMessage(),
            'pending_changes' => 0,
            'last_sync' => null,
            'file_size' => 0
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline System Setup - Vessel Logger</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .status-card {
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        
        .status-initialized {
            border-left: 4px solid #4CAF50;
        }
        
        .status-not-initialized {
            border-left: 4px solid #f44336;
        }
        
        .status-pending {
            border-left: 4px solid #ff9800;
        }
        
        .vessel-actions {
            margin-top: 10px;
        }
        
        .vessel-actions button {
            margin-right: 10px;
            margin-bottom: 5px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .info-item {
            background: white;
            padding: 10px;
            border-radius: 3px;
            border: 1px solid #eee;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 0.9em;
        }
        
        .info-value {
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Offline System Setup</h1>
            <p>Initialize and manage offline capabilities for vessels</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <h2>Vessel Offline Status</h2>
            
            <?php while ($vessel = $vessels_result->fetch_assoc()): ?>
                <?php $status = getOfflineStatus($vessel['VesselID']); ?>
                
                <div class="status-card <?php echo $status['initialized'] ? 'status-initialized' : 'status-not-initialized'; ?>">
                    <h3><?php echo htmlspecialchars($vessel['VesselName']); ?> (ID: <?php echo $vessel['VesselID']; ?>)</h3>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <?php if ($status['initialized']): ?>
                                    <span style="color: #4CAF50;">✓ Initialized</span>
                                <?php else: ?>
                                    <span style="color: #f44336;">✗ Not Initialized</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($status['initialized']): ?>
                            <div class="info-item">
                                <div class="info-label">Pending Changes</div>
                                <div class="info-value">
                                    <?php if ($status['pending_changes'] > 0): ?>
                                        <span style="color: #ff9800;"><?php echo $status['pending_changes']; ?> pending</span>
                                    <?php else: ?>
                                        <span style="color: #4CAF50;">All synced</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Last Sync</div>
                                <div class="info-value">
                                    <?php echo $status['last_sync'] ? htmlspecialchars($status['last_sync']) : 'Never'; ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Database Size</div>
                                <div class="info-value"><?php echo $status['file_size']; ?> KB</div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vessel-actions">
                        <?php if (!$status['initialized']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="vessel_id" value="<?php echo $vessel['VesselID']; ?>">
                                <input type="hidden" name="action" value="initialize">
                                <button type="submit" class="btn-primary">Initialize Offline System</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="vessel_id" value="<?php echo $vessel['VesselID']; ?>">
                                <input type="hidden" name="action" value="sync">
                                <button type="submit" class="btn-secondary">Manual Sync</button>
                            </form>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure? This will delete all offline data and require re-initialization.');">
                                <input type="hidden" name="vessel_id" value="<?php echo $vessel['VesselID']; ?>">
                                <input type="hidden" name="action" value="reset">
                                <button type="submit" class="btn-danger">Reset Database</button>
                            </form>
                            
                            <a href="../engineroom/add_log_offline.php?vessel_id=<?php echo $vessel['VesselID']; ?>" class="btn-secondary">Test Offline Mode</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="form-section">
            <h2>System Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">SQLite Support</div>
                    <div class="info-value">
                        <?php if (class_exists('SQLite3')): ?>
                            <span style="color: #4CAF50;">✓ Available</span>
                        <?php else: ?>
                            <span style="color: #f44336;">✗ Not Available</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Data Directory</div>
                    <div class="info-value">
                        <?php $data_dir = __DIR__ . '/data'; ?>
                        <?php if (is_dir($data_dir) && is_writable($data_dir)): ?>
                            <span style="color: #4CAF50;">✓ Writable</span>
                        <?php else: ?>
                            <span style="color: #f44336;">✗ Not Writable</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">MariaDB Connection</div>
                    <div class="info-value">
                        <?php if ($conn && $conn->ping()): ?>
                            <span style="color: #4CAF50;">✓ Connected</span>
                        <?php else: ?>
                            <span style="color: #f44336;">✗ Disconnected</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="../../support_dashboard.php" class="btn-secondary">← Back to Support Dashboard</a>
            <a href="../engineroom/dashboard.php" class="btn-secondary">Engine Room Dashboard</a>
        </div>
    </div>
</body>
</html>
