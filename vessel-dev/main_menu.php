<?php
session_start();

// Check if logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vessel Logger - Main Menu</title>
    <link rel="stylesheet" type="text/css" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="menu-container">
        <div class="menu-grid">
            <div class="menu-item">
                <h2>Wheelhouse</h2>
                <a href="wheelhouse/index.php">Enter Wheelhouse</a>
            </div>
            
            <div class="menu-item">
                <h2>Engine Room</h2>
                <a href="engine_room/index.php">Enter Engine Room</a>
            </div>
        </div>
    </div>
</body>
</html>
