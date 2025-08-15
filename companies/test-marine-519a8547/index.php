<?php
/**
 * Company Login Page Template
 * Secure login with rate limiting and forced password changes
 */

// Include company configuration
require_once 'config.php';
require_once 'includes/auth.php';

$error_message = '';
$success_message = '';
$info_message = '';

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    $info_message = 'You have been logged out successfully.';
}

// Check if already logged in
if (isLoggedIn()) {
    // Check if password change is required
    if (isPasswordChangeRequired()) {
        header('Location: change_password.php');
        exit;
    } else {
        header('Location: dashboard.php');
        exit;
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Security token validation failed. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error_message = 'Please enter both username and password.';
        } else {
            $auth_result = authenticateUser($username, $password);
            
            if ($auth_result['success']) {
                if ($auth_result['password_change_required']) {
                    header('Location: change_password.php');
                    exit;
                } else {
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error_message = $auth_result['message'];
            }
        }
    }
}

// Handle error messages from URL parameters
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'login_required':
            $error_message = 'Please log in to access this page.';
            break;
        case 'session_expired':
            $error_message = 'Your session has expired. Please log in again.';
            break;
        case 'access_denied':
            $error_message = 'Access denied. Insufficient privileges.';
            break;
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(COMPANY_NAME) ?> - Vessel Logger Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            backdrop-filter: blur(10px);
            max-width: 450px;
            width: 100%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #ecf0f1;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .security-notice {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
            text-align: center;
        }
        
        .company-info {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-ship me-2"></i>Vessel Logger</h1>
            <p>Maritime Management System</p>
        </div>
        
        <!-- Company Information -->
        <div class="company-info">
            <strong><?= htmlspecialchars(COMPANY_NAME) ?></strong><br>
            <small class="text-muted">Company ID: <?= htmlspecialchars(COMPANY_DOMAIN) ?></small>
        </div>
        
        <!-- Error/Success Messages -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($info_message): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <?= htmlspecialchars($info_message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Login Form -->
        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                           required 
                           autocomplete="username">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           required 
                           autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" name="login" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </button>
        </form>
        
        <!-- Security Notice -->
        <div class="security-notice">
            <small class="text-muted">
                <i class="fas fa-shield-alt me-1"></i>
                <strong>First-time login:</strong><br>
                Username: <code>admin</code><br>
                Password: <code>ChangePwd101</code><br>
                You will be required to change your password after first login.
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
        
        // Auto-focus username field
        document.getElementById('username').focus();
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please enter both username and password.');
                return false;
            }
        });
    </script>
</body>
</html>
