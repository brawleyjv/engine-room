<?php
/**
 * Password Change Page Template
 * Forces users to change default password on first login
 */

// Include company configuration
require_once 'config.php';
require_once 'includes/auth.php';

// Must be logged in to access this page
if (!isLoggedIn()) {
    header('Location: index.php?error=login_required');
    exit;
}

// If password change is not required, redirect to dashboard
if (!isPasswordChangeRequired()) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

// Handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error_message = 'Security token validation failed. Please try again.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Please fill in all password fields.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match.';
        } else {
            $result = changePassword($current_password, $new_password);
            
            if ($result['success']) {
                $success_message = $result['message'];
                // Redirect to dashboard after successful password change
                header('refresh:2;url=dashboard.php');
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(COMPANY_NAME) ?> - Change Password</title>
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
        
        .password-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            backdrop-filter: blur(10px);
            max-width: 500px;
            width: 100%;
        }
        
        .password-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .password-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .password-header p {
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
        
        .btn-secondary {
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .company-info {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-header">
            <h1><i class="fas fa-key me-2"></i>Change Password</h1>
            <p>Security Required</p>
        </div>
        
        <!-- Company Information -->
        <div class="company-info">
            <strong><?= htmlspecialchars(COMPANY_NAME) ?></strong><br>
            <small class="text-muted">Welcome, <?= htmlspecialchars($current_user['full_name']) ?></small>
        </div>
        
        <!-- Required Password Change Notice -->
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Password Change Required</strong><br>
            You must change your password before accessing the system. This is for your security.
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
                <?= htmlspecialchars($success_message) ?><br>
                <small>Redirecting to dashboard...</small>
            </div>
        <?php endif; ?>
        
        <!-- Password Requirements -->
        <div class="password-requirements">
            <h6><i class="fas fa-shield-alt me-2"></i>Password Requirements</h6>
            <ul class="mb-0 small">
                <li>At least 8 characters long</li>
                <li>Contains at least one lowercase letter</li>
                <li>Contains at least one uppercase letter</li>
                <li>Contains at least one number</li>
            </ul>
        </div>
        
        <!-- Password Change Form -->
        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" 
                           class="form-control" 
                           id="current_password" 
                           name="current_password" 
                           required 
                           autocomplete="current-password"
                           placeholder="Enter your current password">
                </div>
                <small class="form-text text-muted">Your default password is: ChangePwd101</small>
            </div>
            
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input type="password" 
                           class="form-control" 
                           id="new_password" 
                           name="new_password" 
                           required 
                           autocomplete="new-password"
                           placeholder="Enter your new password">
                    <button type="button" class="btn btn-outline-secondary" id="toggleNewPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
                <small class="form-text text-muted" id="strengthText"></small>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                    <input type="password" 
                           class="form-control" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required 
                           autocomplete="new-password"
                           placeholder="Confirm your new password">
                    <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="form-text" id="matchText"></small>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" name="change_password" class="btn btn-primary" id="changePasswordBtn" disabled>
                    <i class="fas fa-save me-2"></i>Change Password
                </button>
                <a href="index.php?action=logout" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        function checkPasswordStrength(password) {
            let score = 0;
            if (password.length >= 8) score++;
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            return score;
        }
        
        // Update password strength indicator
        function updateStrengthIndicator() {
            const password = document.getElementById('new_password').value;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('strengthText');
            const score = checkPasswordStrength(password);
            
            if (password.length === 0) {
                strengthBar.className = 'password-strength';
                strengthBar.style.width = '0%';
                strengthText.textContent = '';
                return false;
            }
            
            if (score < 3) {
                strengthBar.className = 'password-strength strength-weak';
                strengthBar.style.width = '33%';
                strengthText.textContent = 'Weak password';
                strengthText.className = 'form-text text-danger';
                return false;
            } else if (score < 4) {
                strengthBar.className = 'password-strength strength-medium';
                strengthBar.style.width = '66%';
                strengthText.textContent = 'Medium strength';
                strengthText.className = 'form-text text-warning';
                return true;
            } else {
                strengthBar.className = 'password-strength strength-strong';
                strengthBar.style.width = '100%';
                strengthText.textContent = 'Strong password';
                strengthText.className = 'form-text text-success';
                return true;
            }
        }
        
        // Check password match
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('matchText');
            
            if (confirmPassword.length === 0) {
                matchText.textContent = '';
                return false;
            }
            
            if (newPassword === confirmPassword) {
                matchText.textContent = '✓ Passwords match';
                matchText.className = 'form-text text-success';
                return true;
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.className = 'form-text text-danger';
                return false;
            }
        }
        
        // Update submit button state
        function updateSubmitButton() {
            const isStrengthOk = updateStrengthIndicator();
            const isMatchOk = checkPasswordMatch();
            const currentPassword = document.getElementById('current_password').value;
            
            const submitBtn = document.getElementById('changePasswordBtn');
            submitBtn.disabled = !(isStrengthOk && isMatchOk && currentPassword.length > 0);
        }
        
        // Event listeners
        document.getElementById('new_password').addEventListener('input', updateSubmitButton);
        document.getElementById('confirm_password').addEventListener('input', updateSubmitButton);
        document.getElementById('current_password').addEventListener('input', updateSubmitButton);
        
        // Toggle password visibility
        document.getElementById('toggleNewPassword').addEventListener('click', function() {
            const password = document.getElementById('new_password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const password = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
        
        // Auto-focus current password field
        document.getElementById('current_password').focus();
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (document.getElementById('changePasswordBtn').disabled) {
                e.preventDefault();
                alert('Please ensure all requirements are met before submitting.');
                return false;
            }
        });
    </script>
</body>
</html>
