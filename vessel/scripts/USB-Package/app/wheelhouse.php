<?php
require_once 'db_functions.php';

$message = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['add_nav'])) {
    $log_time = $_POST['log_time'] ? date('Y-m-d H:i:s', strtotime($_POST['log_time'])) : date('Y-m-d H:i:s');
    $position = trim($_POST['position']);
    $speed = $_POST['speed'] ? floatval($_POST['speed']) : null;
    $course = $_POST['course'] ? intval($_POST['course']) : null;
    $weather = trim($_POST['weather']);
    $notes = trim($_POST['notes']);
    
    if ($position) {
        if (addNavigationLog($position, $speed, $course, $weather, $notes, $log_time)) {
            $message = 'Navigation entry added successfully!';
        } else {
            $error = 'Failed to add navigation entry.';
        }
    } else {
        $error = 'Position is required.';
    }
}

// Get recent navigation entries
$recentNavigation = getRecentNavigationLogs(10);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Wheelhouse Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 800px; margin: 0 auto; }
        .header { background: #007cba; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .btn { background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; border: none; cursor: pointer; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
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
            <h1>👨‍✈️ Wheelhouse Dashboard</h1>
            <p>Navigation and Vessel Operations</p>
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
        
        <h2>Navigation Log Entry</h2>
        <form method="POST">
            <div class="form-group">
                <label>Date/Time:</label>
                <input type="datetime-local" name="log_time" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
            </div>
            <div class="form-group">
                <label>Position (GPS):</label>
                <input type="text" name="position" placeholder="e.g., 25.7617° N, 80.1918° W" required>
            </div>
            <div class="form-group">
                <label>Speed (knots):</label>
                <input type="number" name="speed" step="0.1" placeholder="e.g., 12.5">
            </div>
            <div class="form-group">
                <label>Course:</label>
                <input type="number" name="course" min="0" max="360" placeholder="e.g., 270">
            </div>
            <div class="form-group">
                <label>Weather:</label>
                <input type="text" name="weather" placeholder="e.g., Clear, Winds 5-10 kts">
            </div>
            <div class="form-group">
                <label>Notes:</label>
                <textarea name="notes" rows="3" placeholder="Navigation notes..."></textarea>
            </div>
            <input type="submit" name="add_nav" value="Add Navigation Entry" class="btn">
        </form>
        
        <h2>Recent Navigation Entries</h2>
        <table>
            <tr>
                <th>Time</th>
                <th>Position</th>
                <th>Speed</th>
                <th>Course</th>
                <th>Weather</th>
                <th>Sync</th>
            </tr>
            <?php if (empty($recentNavigation)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #666;">No entries yet. Add your first navigation log above.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recentNavigation as $nav): ?>
                    <tr>
                        <td><?php echo date('m/d H:i', strtotime($nav['log_time'])); ?></td>
                        <td><?php echo htmlspecialchars($nav['position_text']); ?></td>
                        <td><?php echo $nav['speed'] ? number_format($nav['speed'], 1) . ' kts' : '-'; ?></td>
                        <td><?php echo $nav['course'] ? $nav['course'] . '°' : '-'; ?></td>
                        <td><?php echo htmlspecialchars($nav['weather']); ?></td>
                        <td><?php echo $nav['synced_at'] ? '✅' : '⏳'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
