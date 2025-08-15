<?php
/**
 * Vessel Sync Status Page
 * Shows registration status and sync information
 */

// Define constant before including config (only if not already defined)
if (!defined('VESSEL_LOGGER')) {
    define('VESSEL_LOGGER', true);
}

// Include vessel sync manager
require_once 'vessel_sync.php';

// Check vessel registration first
requireVesselRegistration();

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: vessel_login.php');
    exit;
}

$sync_manager = new VesselSyncManager();
$vessel_config = $sync_manager->getConfig();

// Get validation status without forcing server check (unless manually requested)
$force_validation = isset($_GET['check_now']);
$validation = $sync_manager->validateRegistration($force_validation);

// Handle sync request
if (isset($_POST['sync_now'])) {
    $sync_result = $sync_manager->syncToServer();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Status - <?php echo htmlspecialchars($vessel_config['vessel_name']); ?></title>
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
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .nav {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav a {
            text-decoration: none;
            color: #28a745;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .nav a:hover {
            background: #28a745;
            color: white;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
            color: #28a745;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-label {
            font-weight: 600;
        }
        
        .status-value {
            color: #6c757d;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-left: 10px;
        }
        
        .status-online {
            background-color: #28a745;
        }
        
        .status-offline {
            background-color: #dc3545;
        }
        
        .status-warning {
            background-color: #ffc107;
        }
        
        .btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s;
            margin-top: 1rem;
        }
        
        .btn:hover {
            background: #218838;
        }
        
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .sync-result {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .sync-error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📡 Sync Status</h1>
        <p><?php echo htmlspecialchars($vessel_config['vessel_name']); ?> - Registration & Sync Information</p>
    </div>
    
    <div class="nav">
        <a href="vessel_login.php">← Back to Login</a>
        <?php if ($_SESSION['user_role'] == 'wheelhouse'): ?>
            <a href="wheelhouse_dashboard.php">Wheelhouse Dashboard</a>
        <?php else: ?>
            <a href="engineer_dashboard.php">Engineer Dashboard</a>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <?php if (isset($sync_result)): ?>
            <?php if ($sync_result['success']): ?>
                <div class="sync-result">
                    <strong>Sync Completed Successfully!</strong><br>
                    Logs synced: <?php echo $sync_result['results']['logs_synced']; ?><br>
                    Fuel levels synced: <?php echo $sync_result['results']['fuel_levels_synced']; ?><br>
                    Maintenance records synced: <?php echo $sync_result['results']['maintenance_synced']; ?><br>
                    Navigation updates synced: <?php echo $sync_result['results']['navigation_synced'] ?? 0; ?>
                </div>
            <?php else: ?>
                <div class="sync-error">
                    <strong>Sync Failed:</strong><br>
                    <?php echo htmlspecialchars($sync_result['error'] ?? 'Unknown error'); ?>
                    <?php if (!empty($sync_result['results']['errors'])): ?>
                        <br>Errors: <?php echo implode(', ', $sync_result['results']['errors']); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="status-grid">
            <!-- Registration Status -->
            <div class="card">
                <h3>🚢 Vessel Registration</h3>
                <div class="status-item">
                    <div class="status-label">Vessel Name:</div>
                    <div class="status-value"><?php echo htmlspecialchars($vessel_config['vessel_name']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">HIN:</div>
                    <div class="status-value"><?php echo htmlspecialchars($vessel_config['hin']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">Registration Status:</div>
                    <div class="status-value">
                        <?php if ($validation['valid']): ?>
                            Valid <span class="status-indicator status-online"></span>
                        <?php else: ?>
                            Invalid <span class="status-indicator status-offline"></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">Subscription Expires:</div>
                    <div class="status-value"><?php echo htmlspecialchars($vessel_config['subscription_expires'] ?? 'Unknown'); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">Last Server Check:</div>
                    <div class="status-value"><?php echo htmlspecialchars($vessel_config['last_validated'] ?? 'Never'); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">Next Check Due:</div>
                    <div class="status-value">
                        <?php 
                        if (isset($vessel_config['last_validated'])) {
                            $next_check = strtotime($vessel_config['last_validated']) + (30 * 60); // 30 minutes
                            echo date('Y-m-d H:i:s', $next_check);
                        } else {
                            echo 'On next sync';
                        }
                        ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">Validation Mode:</div>
                    <div class="status-value">
                        <?php 
                        if (isset($validation['cached'])) {
                            echo 'Cached (Valid)';
                        } elseif (isset($validation['offline_mode'])) {
                            echo 'Offline Mode';
                        } else {
                            echo 'Server Validated';
                        }
                        ?>
                    </div>
                </div>
                <?php if (isset($validation['offline_mode']) && $validation['offline_mode']): ?>
                    <div class="status-item">
                        <div class="status-label">Mode:</div>
                        <div class="status-value">
                            Offline Mode <span class="status-indicator status-warning"></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Sync Status -->
            <div class="card">
                <h3>🔄 Sync Status</h3>
                <div class="status-item">
                    <div class="status-label">Office Server:</div>
                    <div class="status-value"><?php echo htmlspecialchars($vessel_config['office_server_url']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">Connection Status:</div>
                    <div class="status-value">
                        <?php if ($validation['valid'] && !isset($validation['offline_mode'])): ?>
                            Online <span class="status-indicator status-online"></span>
                        <?php else: ?>
                            Offline <span class="status-indicator status-offline"></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">Features Enabled:</div>
                    <div class="status-value">
                        <?php 
                        $features = $vessel_config['features_enabled'] ?? [];
                        echo !empty($features) ? implode(', ', $features) : 'None';
                        ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">Vessel ID:</div>
                    <div class="status-value"><?php echo htmlspecialchars($vessel_config['vessel_id'] ?? 'Unknown'); ?></div>
                </div>
                
                <form method="POST" style="display: inline-block; margin-right: 10px;">
                    <button type="submit" name="sync_now" class="btn" 
                            <?php echo (!$validation['valid']) ? 'disabled' : ''; ?>>
                        🔄 Sync Now
                    </button>
                </form>
                <a href="?check_now=1" class="btn" style="display: inline-block; text-decoration: none; background: #007bff;">
                    🔍 Check Registration
                </a>
            </div>
        </div>
        
        <?php if (!$validation['valid']): ?>
            <div class="card">
                <h3>⚠️ Registration Issue</h3>
                <p><strong>Error:</strong> <?php echo htmlspecialchars($validation['error']); ?></p>
                <p>Please contact your office administrator or run the registration setup again.</p>
                <a href="vessel_registration.php" class="btn">Re-register Vessel</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
