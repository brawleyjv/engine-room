<?php
/**
 * Simple Login - No Registration Check
 */

session_start();

$error_message = '';

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Simple demo users
    $users = [
        'captain' => ['role' => 'wheelhouse', 'name' => 'Captain Smith'],
        'pilot' => ['role' => 'wheelhouse', 'name' => 'Pilot Johnson'],
        'engineer' => ['role' => 'engineer', 'name' => 'Chief Engineer Brown'],
    ];
    
    if (isset($users[$username])) {
        // Valid login (any password works for demo)
        $_SESSION['username'] = $username;
        $_SESSION['user_role'] = $users[$username]['role'];
        $_SESSION['user_name'] = $users[$username]['name'];
        
        // Redirect based on role
        if ($users[$username]['role'] === 'wheelhouse') {
            header('Location: wheelhouse_dashboard.php');
        } else {
            header('Location: engineer_dashboard.php');
        }
        exit;
    } else {
        $error_message = 'Invalid username';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Vessel Logger - Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f0f0; padding: 50px; }
        .login-box { background: white; padding: 30px; border-radius: 10px; max-width: 400px; margin: 0 auto; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; }
        button { background: #007cba; color: white; padding: 12px 20px; border: none; cursor: pointer; width: 100%; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="login-box">
        <h1>🚢 Vessel Logger</h1>
        <p>Please log in to continue</p>
        
        <?php if ($error_message): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password">
            <button type="submit">Login</button>
        </form>
        
        <h3>Demo Users:</h3>
        <ul>
            <li><strong>captain</strong> - Wheelhouse Dashboard</li>
            <li><strong>pilot</strong> - Wheelhouse Dashboard</li>
            <li><strong>engineer</strong> - Engineer Dashboard</li>
        </ul>
    </div>
</body>
</html>
