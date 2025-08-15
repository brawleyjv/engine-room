<?php
// Minimal test - no session, no includes, just basic output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct Test</title>
</head>
<body>
    <h1>Direct PHP Test</h1>
    <p>If you see this, PHP is working without redirects.</p>
    <p>Host: <?php echo $_SERVER['HTTP_HOST'] ?? 'unknown'; ?></p>
    <p>Request URI: <?php echo $_SERVER['REQUEST_URI'] ?? 'unknown'; ?></p>
    <p>Time: <?php echo date('Y-m-d H:i:s'); ?></p>
</body>
</html>
