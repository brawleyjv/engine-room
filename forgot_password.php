<?php
require_once 'config.php';
require_once 'auth_functions.php';

$message = '';
$message_type = '';

if ($_POST && isset($_POST['reset_password'])) {
    $email = trim($_POST['email']);
    
    if (!empty($email)) {
        $token = generate_reset_token($conn, $email);
        if ($token) {
            // In a real application, you would send this via email
            // For demo purposes, we'll just display it
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=" . $token;
            $message = "Password reset link generated! (In a real application, this would be sent via email)<br><br><strong>Reset Link:</strong><br><a href='{$reset_link}' target='_blank'>{$reset_link}</a>";
            $message_type = 'success';
        } else {
            $message = 'Email address not found or user is inactive.';
            $message_type = 'error';
        }
    } else {
        $message = 'Please enter your email address.';
        $message_type = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Engine Room Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .forgot-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .forgot-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .forgot-header p {
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
        .btn-reset {
            width: 100%;
            background: #e67e22;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-reset:hover {
            background: #d35400;
        }
        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }
        .back-to-login a {
            color: #3498db;
            text-decoration: none;
        }
        .back-to-login a:hover {
            text-decoration: underline;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h1>🔑 Forgot Password</h1>
            <p>Enter your email to reset your password</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required autofocus 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            
            <button type="submit" name="reset_password" class="btn-reset">
                📧 Send Reset Link
            </button>
        </form>
        
        <div class="back-to-login">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>
