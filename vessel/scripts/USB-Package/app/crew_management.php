<?php
/**
 * Simple Crew Management - Just data entry
 */

// Define constant and start session
if (!defined('VESSEL_LOGGER')) {
    define('VESSEL_LOGGER', true);
}

session_start();

// Simple authentication check
if (!isset($_SESSION['username'])) {
    header('Location: vessel_login.php');
    exit;
}

// Database connection
$db_path = __DIR__ . '/vessel_data.sqlite';
$pdo = new PDO('sqlite:' . $db_path);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create crew table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS crew_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    date_on TEXT NOT NULL,
    date_off TEXT,
    twic_exp_date TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Handle form submission
if ($_POST) {
    if (isset($_POST['add_crew'])) {
        $name = trim($_POST['name']);
        $date_on = trim($_POST['date_on']);
        $date_off = trim($_POST['date_off']);
        $twic_exp = trim($_POST['twic_exp']);
        
        if ($name && $date_on) {
            $stmt = $pdo->prepare("INSERT INTO crew_members (name, date_on, date_off, twic_exp_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $date_on, $date_off ?: null, $twic_exp ?: null]);
            $message = "Crew member added successfully";
        } else {
            $error = "Name and Date On are required";
        }
    }
    
    if (isset($_POST['remove_crew'])) {
        $id = $_POST['crew_id'];
        $stmt = $pdo->prepare("DELETE FROM crew_members WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Crew member removed";
    }
}

// Get current crew
$stmt = $pdo->query("SELECT * FROM crew_members ORDER BY date_on DESC");
$crew_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Crew Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        label { display: block; margin-bottom: 5px; }
        input { padding: 8px; width: 200px; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background: #f5f5f5; }
        .message { background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; }
        .remove-btn { background: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Crew Management</h1>
    
    <a href="wheelhouse_dashboard.php">← Back to Dashboard</a>
    
    <?php if (isset($message)): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <h2>Add New Crew Member</h2>
    <form method="POST">
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="name" required>
        </div>
        <div class="form-group">
            <label>Date On:</label>
            <input type="date" name="date_on" required>
        </div>
        <div class="form-group">
            <label>Date Off (optional):</label>
            <input type="date" name="date_off">
        </div>
        <div class="form-group">
            <label>TWIC Expiry Date (optional):</label>
            <input type="date" name="twic_exp">
        </div>
        <button type="submit" name="add_crew">Add Crew Member</button>
    </form>
    
    <h2>Current Crew (<?php echo count($crew_members); ?>)</h2>
    <table>
        <tr>
            <th>Name</th>
            <th>Date On</th>
            <th>Date Off</th>
            <th>TWIC Expires</th>
            <th>Action</th>
        </tr>
        <?php foreach ($crew_members as $crew): ?>
        <tr>
            <td><?php echo htmlspecialchars($crew['name']); ?></td>
            <td><?php echo htmlspecialchars($crew['date_on']); ?></td>
            <td><?php echo htmlspecialchars($crew['date_off'] ?: 'On board'); ?></td>
            <td><?php echo htmlspecialchars($crew['twic_exp_date'] ?: 'Not provided'); ?></td>
            <td>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="crew_id" value="<?php echo $crew['id']; ?>">
                    <button type="submit" name="remove_crew" class="remove-btn" 
                            onclick="return confirm('Remove this crew member?')">Remove</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
</body>
</html>
