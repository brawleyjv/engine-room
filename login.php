<?php
require_once 'config.php';
require_once 'auth_functions.php';

$error = '';
$redirect = $_GET['redirect'] ?? 'dashboard.php';

// Handle login form submission
if ($_POST && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (login_user($conn, $username, $password)) {
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

// If already logged in, redirect
if (is_logged_in()) {
    header('Location: ' . $redirect);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Engine Room Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #7f8c8d;
            margin: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }
        .btn-login {
            width: 100%;
            background: #3498db;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-login:hover {
            background: #2980b9;
        }
        .error-message {
            background: #e74c3c;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        .forgot-password a {
            color: #3498db;
            text-decoration: none;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }
        .demo-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🚢 Engine Room</h1>
            <p>Data Logger Login</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" autocomplete="on">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus 
                       autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       autocomplete="current-password">
            </div>
            
            <button type="submit" name="login" class="btn-login">
                🔑 Login
            </button>
        </form>
        
        <div class="forgot-password">
            <a href="forgot_password.php">Forgot your password?</a>
        </div>
        
        <div class="demo-info">
            <strong>Demo Login:</strong><br>
            Username: <code>admin</code><br>
            Password: <code>admin123</code><br>
            <small>(Change this password after first login!)</small>
        </div>
    </div>
</body>
</html>
