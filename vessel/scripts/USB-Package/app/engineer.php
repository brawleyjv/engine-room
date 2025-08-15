<?php
require_once 'db_functions.php';

$message = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['add_engine'])) {
    $log_time = $_POST['log_time'] ? date('Y-m-d H:i:s', strtotime($_POST['log_time'])) : date('Y-m-d H:i:s');
    $engine_id = trim($_POST['engine_id']);
    $rpm = $_POST['rpm'] ? intval($_POST['rpm']) : null;
    $temperature = $_POST['temperature'] ? floatval($_POST['temperature']) : null;
    $oil_pressure = $_POST['oil_pressure'] ? floatval($_POST['oil_pressure']) : null;
    $notes = trim($_POST['notes']);
    
    if ($engine_id) {
        if (addEngineLog($engine_id, $rpm, $temperature, $oil_pressure, $notes, $log_time)) {
            $message = 'Engine log entry added successfully!';
        } else {
            $error = 'Failed to add engine log entry.';
        }
    } else {
        $error = 'Engine selection is required.';
    }
}

// Get recent engine entries
$recentLogs = getRecentEngineLogs(10);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Engineer Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; }
        .header { background: #d9534f; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .btn { background: #d9534f; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; border: none; cursor: pointer; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f5f5f5; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Engineer Dashboard</h1>
            <p>Engine Room Operations and Maintenance</p>
        </div>
        
        <a href="index.php" class="btn">🏠 Home</a>
        <a href="crew.php" class="btn">👥 Crew Management</a>
        <a href="sync_status.php" class="btn">🔄 Sync Status</a>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <h2>Engine Log Entry</h2>
        <form method="POST">
            <div class="form-group">
                <label>Date/Time:</label>
                <input type="datetime-local" name="log_time" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
            </div>
            <div class="form-group">
                <label>Engine:</label>
                <select name="engine_id" required>
                    <option value="">Select Engine</option>
                    <option value="main_port">Main Engine - Port</option>
                    <option value="main_stbd">Main Engine - Starboard</option>
                    <option value="generator_1">Generator #1</option>
                    <option value="generator_2">Generator #2</option>
                </select>
            </div>
            <div class="form-group">
                <label>RPM:</label>
                <input type="number" name="rpm" placeholder="e.g., 1800">
            </div>
            <div class="form-group">
                <label>Temperature (°F):</label>
                <input type="number" name="temperature" placeholder="e.g., 180">
            </div>
            <div class="form-group">
                <label>Oil Pressure (PSI):</label>
                <input type="number" name="oil_pressure" placeholder="e.g., 45">
            </div>
            <div class="form-group">
                <label>Notes:</label>
                <textarea name="notes" rows="3" placeholder="Engine notes and observations..."></textarea>
            </div>
            <input type="submit" name="add_engine" value="Add Engine Entry" class="btn">
        </form>
        
        <h2>Recent Engine Entries</h2>
        <table>
            <tr>
                <th>Time</th>
                <th>Engine</th>
                <th>RPM</th>
                <th>Temp</th>
                <th>Oil PSI</th>
                <th>Notes</th>
                <th>Sync</th>
            </tr>
            <?php if (empty($recentLogs)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #666;">No entries yet. Add your first engine log above.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                    <tr>
                        <td><?php echo date('m/d H:i', strtotime($log['log_time'])); ?></td>
                        <td><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($log['engine_id'], '_'))); ?></td>
                        <td><?php echo $log['rpm'] ?: '-'; ?></td>
                        <td><?php echo $log['temperature'] ? $log['temperature'] . '°F' : '-'; ?></td>
                        <td><?php echo $log['oil_pressure'] ? $log['oil_pressure'] . ' PSI' : '-'; ?></td>
                        <td><?php echo htmlspecialchars($log['notes']); ?></td>
                        <td><?php echo $log['synced_at'] ? '✅' : '⏳'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
