<?php
session_start();

// Simple database connection
function getDB() {
    try {
        $db = new PDO('sqlite:data/vessel.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch(PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

$error = '';
$show_suggestions = false;
$available_vessels = [];

// Handle login attempt
if (isset($_POST['vessel_name']) && isset($_POST['company_name']) && $_POST['vessel_name'] && $_POST['company_name']) {
    $vessel_name = trim($_POST['vessel_name']);
    $company_name = trim($_POST['company_name']);
    
    try {
        $db = getDB();
        
        // First, check if there are ANY vessels in the database
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM vessels');
        $stmt->execute();
        $vessel_count = $stmt->fetch()['count'];
        
        if ($vessel_count == 0) {
            // No vessels in database - redirect to setup
            header('Location: setup/add_vessel.php');
            exit;
        }
        
        // Try to find exact match
        $stmt = $db->prepare('SELECT * FROM vessels WHERE vessel_name COLLATE NOCASE = ? AND company_name COLLATE NOCASE = ?');
        $stmt->execute([$vessel_name, $company_name]);
        $vessel = $stmt->fetch();
        
        if ($vessel) {
            // Perfect match - log them in
            $_SESSION['vessel_name'] = $vessel['vessel_name']; // Use actual DB values
            $_SESSION['company_name'] = $vessel['company_name'];
            $_SESSION['logged_in'] = true;
            header('Location: main_menu.php');
            exit;
        } else {
            // No match found - show available vessels
            $show_suggestions = true;
            $stmt = $db->prepare('SELECT vessel_name, company_name FROM vessels ORDER BY vessel_name, company_name');
            $stmt->execute();
            $available_vessels = $stmt->fetchAll();
            $error = 'No matching vessel found. Please check the available vessels below or verify your spelling:';
        }
        
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Check if database exists and has vessels on page load
$initial_check = false;
try {
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM vessels');
    $stmt->execute();
    $vessel_count = $stmt->fetch()['count'];
    
    if ($vessel_count == 0) {
        $initial_check = true;
    }
} catch (Exception $e) {
    // Database might not exist yet
    $initial_check = true;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vessel Logger - Login</title>
    <link rel="stylesheet" type="text/css" href="css/styles.css">
    <style>
        .vessel-list { background: #f8f8f8; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .vessel-item { background: white; padding: 10px; margin: 5px 0; border-radius: 3px; border-left: 4px solid #3498db; }
        .setup-notice { background: #fff3cd; padding: 15px; margin: 20px 0; border-radius: 5px; border: 1px solid #ffeaa7; }
        .action-buttons { margin-top: 20px; }
        .action-buttons a { display: inline-block; padding: 10px 20px; margin: 5px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; }
        .action-buttons a:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>⚓ Vessel Logger</h1>
            <p>Please enter your vessel information</p>
        </div>
        
        <?php if ($initial_check): ?>
            <div class="setup-notice">
                <h3>🚢 First Time Setup</h3>
                <p>No vessels found in the database. You need to add your vessel information first.</p>
                <div class="action-buttons">
                    <a href="setup/add_vessel.php">Set Up Your Vessel</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="vessel_name">Vessel Name:</label>
                    <input type="text" id="vessel_name" name="vessel_name" value="<?php echo isset($_POST['vessel_name']) ? htmlspecialchars($_POST['vessel_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="company_name">Company Name:</label>
                    <input type="text" id="company_name" name="company_name" value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>" required>
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($show_suggestions && !empty($available_vessels)): ?>
                <div class="vessel-list">
                    <h3>Available Vessels in Database:</h3>
                    <p><strong>Is your vessel one of these?</strong> If so, please double-check your spelling:</p>
                    <?php foreach ($available_vessels as $vessel): ?>
                        <div class="vessel-item">
                            <strong>Vessel:</strong> <?php echo htmlspecialchars($vessel['vessel_name']); ?><br>
                            <strong>Company:</strong> <?php echo htmlspecialchars($vessel['company_name']); ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="action-buttons">
                        <a href="setup/add_vessel.php">Add New Vessel</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
