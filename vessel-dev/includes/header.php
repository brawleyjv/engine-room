<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: index.php');
    exit;
}
?>
<div class="header">
    <h1><?php echo htmlspecialchars($_SESSION['company_name']); ?></h1>
    <p style="font-size: 12px; margin: 5px 0 0 0; opacity: 0.7;">Powered by LogicDock</p>
</div>

<div class="vessel-info">
    <strong>Vessel:</strong> <?php echo htmlspecialchars($_SESSION['vessel_name']); ?>
</div>
