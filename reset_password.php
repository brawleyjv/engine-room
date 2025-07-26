<?php
require_once 'config.php';
require_once 'auth_functions.php';

$token = $_GET['token'] ?? '';
$message = '';
$message_type = '';
$valid_token = false;

if (!empty($token)) {
    $user = verify_reset_token($conn, $token);
    if ($user) {
        $valid_token = true;
        
        if ($_POST && isset($_POST['update_password'])) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if (empty($new_password) || empty($confirm_password)) {
                $message = 'Please fill in both password fields.';
                $message_type = 'error';
            } elseif ($new_password !== $confirm_password) {
                $message = 'Passwords do not match.';
                $message_type = 'error';
            } elseif (strlen($new_password) < 6) {
                $message = 'Password must be at least 6 characters long.';
                $message_type = 'error';
            } else {
                if (reset_password($conn, $token, $new_password)) {
                    $message = 'Password updated successfully! You can now log in with your new password.';
                    $message_type = 'success';
                    $valid_token = false; // Prevent further resets
                } else {
                    $message = 'Error updating password. Please try again.';
                    $message_type = 'error';
                }
            }
        }
    } else {
        $message = 'Invalid or expired reset token.';
        $message_type = 'error';
    }
} else {
    $message = 'No reset token provided.';
    $message_type = 'error';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Engine Room Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .reset-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .reset-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .reset-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .reset-header p {
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
        .btn-update {
            width: 100%;
            background: #27ae60;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-update:hover {
            background: #229954;
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
        .user-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
            text-align: center;
            color: #1565c0;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>🔒 Reset Password</h1>
            <p>Create a new password for your account</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($valid_token): ?>
            <div class="user-info">
                Resetting password for: <strong><?= htmlspecialchars($user['Username']) ?></strong>
            </div>
            
            <form method="POST" autocomplete="on">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required autofocus 
                           autocomplete="new-password"
                           minlength="6" placeholder="At least 6 characters">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           autocomplete="new-password"
                           minlength="6" placeholder="Re-enter your new password">
                </div>
                
                <button type="submit" name="update_password" class="btn-update">
                    🔑 Update Password
                </button>
            </form>
        <?php endif; ?>
        
        <div class="back-to-login">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>
