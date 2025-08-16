<?php
session_start();

function getDB() {
    $db_path = __DIR__ . '/../data/vessel.db';
    if (!file_exists($db_path)) {
        die("Database file does not exist: $db_path");
    }
    try {
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}

$success = '';
$error = '';

// Handle new vessel setup
if (isset($_POST['vessel_name']) && isset($_POST['company_name']) && $_POST['vessel_name'] && $_POST['company_name']) {
    $vessel_name = trim($_POST['vessel_name']);
    $company_name = trim($_POST['company_name']);
    
    try {
        $db = getDB();
        
        // Check if this vessel/company combination already exists
        $stmt = $db->prepare('SELECT * FROM vessels WHERE vessel_name COLLATE NOCASE = ? AND company_name COLLATE NOCASE = ?');
        $stmt->execute([$vessel_name, $company_name]);
        if ($stmt->fetch()) {
            $error = 'This vessel and company combination already exists in the database.';
        } else {
            // Insert new vessel
            $stmt = $db->prepare('INSERT INTO vessels (vessel_name, company_name, created_at) VALUES (?, ?, datetime("now"))');
            $stmt->execute([$vessel_name, $company_name]);
            
            $success = 'Vessel "' . htmlspecialchars($vessel_name) . '" for "' . htmlspecialchars($company_name) . '" has been successfully added to the database.';
        }
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Get current vessels for display
try {
    $db = getDB();
    $stmt = $db->prepare('SELECT vessel_name, company_name, created_at FROM vessels ORDER BY created_at DESC');
    $stmt->execute();
    $existing_vessels = $stmt->fetchAll();
} catch (Exception $e) {
    $existing_vessels = [];
    $error = 'Could not retrieve existing vessels: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vessel Setup - Vessel Logger</title>
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
    <style>
        .vessel-list { background: #f8f8f8; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .vessel-item { background: white; padding: 10px; margin: 5px 0; border-radius: 3px; border-left: 4px solid #3498db; }
    </style>
</head>
<body>
    <div class="content">
        <div class="nav">
            <a href="../index.php">← Back to Login</a>
        </div>
        
        <h1>Vessel Setup</h1>
        
        <?php if ($success): ?>
            <div style="color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <strong>Success!</strong> <?php echo $success; ?>
                <br><br>
                <a href="../index.php" style="color: #155724; font-weight: bold;">← Go to Login Page</a>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error" style="background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($existing_vessels)): ?>
            <div class="vessel-list">
                <h3>Existing Vessels in Database:</h3>
                <?php foreach ($existing_vessels as $vessel): ?>
                    <div class="vessel-item">
                        <strong>Vessel:</strong> <?php echo htmlspecialchars($vessel['vessel_name']); ?><br>
                        <strong>Company:</strong> <?php echo htmlspecialchars($vessel['company_name']); ?><br>
                        <small>Added: <?php echo htmlspecialchars($vessel['created_at']); ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <h2>Add New Vessel</h2>
        <form method="POST">
            <div class="form-group">
                <label for="vessel_name">Vessel Name:</label>
                <input type="text" id="vessel_name" name="vessel_name" value="<?php echo isset($_POST['vessel_name']) ? htmlspecialchars($_POST['vessel_name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="company_name">Company Name:</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>" required>
            </div>
            
            <button type="submit" class="login-btn">Add Vessel</button>
        </form>
    </div>
</body>
</html>
