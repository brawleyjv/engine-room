<?php
/**
 * Vessel Logger - Login Page
 * Handles user authentication for the vessel logging system
 */

// Define constant before including config
define('VESSEL_LOGGER', true);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/security/auth.php';

// Redirect if not set up
if (!isVesselSetup()) {
    header('Location: /setup.php');
    exit;
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$errors = [];
$login_attempts = getLoginAttempts($_SERVER['REMOTE_ADDR']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check rate limiting
        if ($login_attempts >= 5) {
            throw new Exception('Too many login attempts. Please try again in 15 minutes.');
        }
        
        // Validate CSRF token
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        if (empty($username) || empty($password)) {
            throw new Exception('Username and password are required');
        }
        
        // Attempt login
        $user = authenticateUser($username, $password);
        
        if ($user) {
            // Create session
            createUserSession($user['id'], $remember_me);
            
            // Log successful login
            logActivity('auth', 'login_success', 'User logged in', [
                'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            // Reset login attempts
            resetLoginAttempts($_SERVER['REMOTE_ADDR']);
            
            // Redirect to dashboard
            header('Location: /dashboard.php');
            exit;
        } else {
            // Record failed attempt
            recordLoginAttempt($_SERVER['REMOTE_ADDR'], $username);
            
            logActivity('auth', 'login_failed', 'Login attempt failed', [
                'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ]);
            
            throw new Exception('Invalid username or password');
        }
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get vessel information for display
$vessel_config = getVesselConfig();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Logger - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 300;
        }
        
        .vessel-info {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .vessel-info h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .vessel-info p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-form {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .checkbox-group label {
            margin-bottom: 0;
            font-weight: normal;
            cursor: pointer;
        }
        
        .errors {
            background: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .login-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .login-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            border-top: 1px solid #e1e8ed;
        }
        
        .system-status {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-online {
            background: #27ae60;
        }
        
        .status-offline {
            background: #e74c3c;
        }
        
        .rate-limit-warning {
            background: #f39c12;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255,255,255,0.9);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-form {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Vessel Logger</h1>
            
            <?php if (!empty($vessel_config['vessel_name'])): ?>
                <div class="vessel-info">
                    <h3><?php echo htmlspecialchars($vessel_config['vessel_name']); ?></h3>
                    <p><?php echo htmlspecialchars($vessel_config['company_name'] ?? 'Unknown Company'); ?></p>
                    <?php if (!empty($vessel_config['vessel_imo'])): ?>
                        <p>IMO: <?php echo htmlspecialchars($vessel_config['vessel_imo']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="login-form">
            <?php if ($login_attempts >= 3): ?>
                <div class="rate-limit-warning">
                    <strong>Warning:</strong> Multiple failed login attempts detected.<br>
                    Account will be locked after <?php echo 5 - $login_attempts; ?> more failed attempts.
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <?php foreach ($errors as $error): ?>
                        <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required autocomplete="username" <?php echo $login_attempts >= 5 ? 'disabled' : ''; ?>>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" <?php echo $login_attempts >= 5 ? 'disabled' : ''; ?>>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="remember_me" name="remember_me" <?php echo $login_attempts >= 5 ? 'disabled' : ''; ?>>
                    <label for="remember_me">Remember me for 7 days</label>
                </div>
                
                <button type="submit" class="login-btn" <?php echo $login_attempts >= 5 ? 'disabled' : ''; ?>>
                    <?php echo $login_attempts >= 5 ? 'Account Locked' : 'Sign In'; ?>
                </button>
            </form>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Signing in...</p>
            </div>
        </div>
        
        <div class="login-footer">
            <div class="system-status">
                <div class="status-indicator">
                    <div class="status-dot <?php echo isOnline() ? 'status-online' : 'status-offline'; ?>"></div>
                    <span><?php echo isOnline() ? 'Online' : 'Offline'; ?></span>
                </div>
                <div>
                    <span>v<?php echo VESSEL_VERSION; ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Form submission handling
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loading = document.getElementById('loading');
            const form = this;
            
            // Show loading state
            loading.style.display = 'block';
            
            // Disable form elements
            const inputs = form.querySelectorAll('input, button');
            inputs.forEach(input => input.disabled = true);
            
            // Allow form to submit naturally
            // Loading state will be cleared on page reload/redirect
        });
        
        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            if (usernameField && !usernameField.disabled && !usernameField.value) {
                usernameField.focus();
            }
        });
        
        // Clear any stored password on page load for security
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.getElementById('password').value = '';
            }
        });
        
        // Prevent multiple form submissions
        let formSubmitted = false;
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (formSubmitted) {
                e.preventDefault();
                return false;
            }
            formSubmitted = true;
        });
    </script>
</body>
</html>
