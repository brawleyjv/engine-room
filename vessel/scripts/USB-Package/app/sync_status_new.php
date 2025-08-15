<?php
require_once 'db_functions.php';

$message = '';
$error = '';

// Handle manual sync
if ($_POST && isset($_POST['sync_now'])) {
    try {
        // Check if vessel_sync.php exists and try to use it
        if (file_exists('vessel_sync.php')) {
            require_once 'vessel_sync.php';
            $syncManager = new VesselSyncManager();
            $result = $syncManager->syncToServer();
            
            if ($result['success']) {
                $message = 'Sync completed successfully! ' . $result['message'];
            } else {
                $error = 'Sync failed: ' . $result['error'];
            }
        } else {
            $error = 'Sync functionality not available - vessel_sync.php not found';
        }
    } catch (Exception $e) {
        $error = 'Sync error: ' . $e->getMessage();
    }
}

// Get unsynced data counts
$unsyncedData = getUnsyncedData();
$totalUnsynced = count($unsyncedData['logs']) + count($unsyncedData['navigation']) + count($unsyncedData['crew']);

// Check vessel configuration
$configFile = __DIR__ . '/vessel_config.json';
$vesselConfigured = file_exists($configFile);
$config = $vesselConfigured ? json_decode(file_get_contents($configFile), true) : null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sync Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; }
        .header { background: #17a2b8; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .btn { background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; border: none; cursor: pointer; }
        .btn-sync { background: #28a745; }
        .btn-sync:hover { background: #218838; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info-box { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .sync-stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { background: #e9ecef; padding: 15px; border-radius: 5px; text-align: center; flex: 1; }
        .stat-number { font-size: 24px; font-weight: bold; color: #007cba; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Sync Status</h1>
            <p>Data synchronization with main server</p>
        </div>
        
        <a href="index.php" class="btn">🏠 Home</a>
        <a href="wheelhouse.php" class="btn">👨‍✈️ Wheelhouse</a>
        <a href="engineer.php" class="btn">🔧 Engineer</a>
        <a href="crew.php" class="btn">👥 Crew</a>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <h2>Vessel Configuration</h2>
        <div class="info-box">
            <?php if ($vesselConfigured && $config): ?>
                <strong>✅ Vessel Configured</strong><br>
                <strong>Vessel Name:</strong> <?php echo htmlspecialchars($config['vessel_name'] ?? 'Not set'); ?><br>
                <strong>HIN:</strong> <?php echo htmlspecialchars($config['hin'] ?? 'Not set'); ?><br>
                <strong>Company:</strong> <?php echo htmlspecialchars($config['company_name'] ?? 'Not set'); ?><br>
                <strong>Server URL:</strong> <?php echo htmlspecialchars($config['server_url'] ?? 'Not set'); ?>
            <?php else: ?>
                <strong>⚠️ Vessel Not Configured</strong><br>
                Please complete vessel registration to enable sync functionality.
                <br><a href="vessel_registration.php" class="btn">Configure Vessel</a>
            <?php endif; ?>
        </div>
        
        <h2>Data Pending Sync</h2>
        <div class="sync-stats">
            <div class="stat-box">
                <div class="stat-number"><?php echo count($unsyncedData['logs']); ?></div>
                <div>Engine Logs</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo count($unsyncedData['navigation']); ?></div>
                <div>Navigation Entries</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo count($unsyncedData['crew']); ?></div>
                <div>Crew Changes</div>
            </div>
            <div class="stat-box">
                <div class="stat-number"><?php echo $totalUnsynced; ?></div>
                <div>Total Pending</div>
            </div>
        </div>
        
        <?php if ($totalUnsynced > 0): ?>
            <form method="POST" style="text-align: center; margin: 30px 0;">
                <input type="submit" name="sync_now" value="🔄 Sync Now" class="btn btn-sync" style="font-size: 18px; padding: 15px 30px;">
            </form>
        <?php else: ?>
            <div style="text-align: center; margin: 30px 0; color: #28a745;">
                <h3>✅ All data is synchronized</h3>
                <p>No pending data to sync with the main server.</p>
            </div>
        <?php endif; ?>
        
        <h3>Recent Sync Activity</h3>
        <div class="info-box">
            <strong>Last Sync Attempt:</strong> <?php echo $config['last_sync_attempt'] ?? 'Never'; ?><br>
            <strong>Last Successful Sync:</strong> <?php echo $config['last_successful_sync'] ?? 'Never'; ?><br>
            <strong>Automatic Sync:</strong> Every 30 minutes when connected to internet
        </div>
        
        <?php if (!$vesselConfigured): ?>
            <div class="warning">
                <strong>⚠️ Setup Required</strong><br>
                Sync functionality requires vessel registration. The system will store data locally until configuration is complete.
            </div>
        <?php endif; ?>
        
        <h3>Sync Details</h3>
        <div class="info-box">
            <strong>How it works:</strong><br>
            • All data is stored locally in SQLite database<br>
            • System automatically attempts sync every 30 minutes<br>
            • Manual sync can be triggered anytime<br>
            • Data remains available offline even when sync fails<br>
            • Only unsynced data is transmitted to reduce bandwidth
        </div>
    </div>
</body>
</html>
