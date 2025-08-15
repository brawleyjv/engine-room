<?php
require_once 'db_functions.php';

$message = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['add_crew'])) {
    $name = trim($_POST['name']);
    $position = trim($_POST['position']);
    $date_on = $_POST['date_on'];
    $date_off = $_POST['date_off'] ?: null;
    $twic_exp_date = $_POST['twic_exp_date'];
    
    if ($name && $date_on && $twic_exp_date) {
        if (addCrewMember($name, $position, $date_on, $date_off, $twic_exp_date)) {
            $message = 'Crew member added successfully!';
        } else {
            $error = 'Failed to add crew member.';
        }
    } else {
        $error = 'Name, Date On, and TWIC Expiration Date are required.';
    }
}

// Get current crew
$currentCrew = getCurrentCrew();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Crew Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto; }
        .header { background: #5cb85c; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .btn { background: #5cb85c; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px; display: inline-block; border: none; cursor: pointer; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f5f5f5; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .status-active { color: green; }
        .status-inactive { color: #999; }
        .twic-expiring { color: red; }
        .twic-valid { color: green; }
        .alert { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Crew Management</h1>
            <p>Manage vessel crew information and TWIC documentation</p>
        </div>
        
        <a href="index.php" class="btn">🏠 Home</a>
        <a href="wheelhouse.php" class="btn">👨‍✈️ Wheelhouse</a>
        <a href="engineer.php" class="btn">🔧 Engineer</a>
        <a href="sync_status.php" class="btn">🔄 Sync Status</a>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <h2>Add Crew Member</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="name" placeholder="e.g., John Smith" required>
                </div>
                <div class="form-group">
                    <label>Position:</label>
                    <input type="text" name="position" placeholder="e.g., Captain, Engineer, Deckhand">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Date On:</label>
                    <input type="date" name="date_on" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Date Off (if applicable):</label>
                    <input type="date" name="date_off">
                </div>
            </div>
            
            <div class="form-group">
                <label>TWIC Expiration Date:</label>
                <input type="date" name="twic_exp_date" required>
            </div>
            
            <input type="submit" name="add_crew" value="Add Crew Member" class="btn">
        </form>
        
        <h2>Current Crew</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>Position</th>
                <th>Date On</th>
                <th>Date Off</th>
                <th>TWIC Exp</th>
                <th>Status</th>
                <th>Sync</th>
            </tr>
            <?php if (empty($currentCrew)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #666;">No crew members yet. Add crew members above.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($currentCrew as $crew): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($crew['name']); ?></td>
                        <td><?php echo htmlspecialchars($crew['position']); ?></td>
                        <td><?php echo date('m/d/Y', strtotime($crew['date_on'])); ?></td>
                        <td><?php echo $crew['date_off'] ? date('m/d/Y', strtotime($crew['date_off'])) : '-'; ?></td>
                        <td><?php echo date('m/d/Y', strtotime($crew['twic_exp_date'])); ?></td>
                        <td class="<?php echo $crew['status'] == 'Active' ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $crew['status'] == 'Active' ? '✅ Active' : '❌ Inactive'; ?>
                        </td>
                        <td><?php echo $crew['synced_at'] ? '✅' : '⏳'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
        
        <?php
        // Check for TWIC expiring soon
        $expiring_soon = [];
        foreach ($currentCrew as $crew) {
            if ($crew['status'] == 'Active' && $crew['twic_status'] == 'Expiring') {
                $days_until_exp = floor((strtotime($crew['twic_exp_date']) - time()) / (60 * 60 * 24));
                $expiring_soon[] = $crew['name'] . ' (expires in ' . $days_until_exp . ' days)';
            }
        }
        ?>
        
        <?php if (!empty($expiring_soon)): ?>
            <div class="alert">
                <strong>⚠️ TWIC Alerts:</strong><br>
                <?php foreach ($expiring_soon as $alert): ?>
                    • <?php echo htmlspecialchars($alert); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
