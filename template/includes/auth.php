<?php
/**
 * Secure Authentication System Template
 * This file gets copied to each company's includes folder
 * Includes login rate limiting, password change enforcement, and session security
 */

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    session_start();
}

/**
 * Authenticate user with username and password
 * Includes rate limiting and account lockout protection
 */
function authenticateUser($username, $password) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        return ['success' => false, 'message' => 'System error. Please try again.'];
    }
    
    // Check if account is locked
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role, password_must_change, failed_login_attempts, locked_until FROM users WHERE username = ? AND is_active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$user = $result->fetch_assoc()) {
        $conn->close();
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }
    
    // Check if account is temporarily locked
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $conn->close();
        return ['success' => false, 'message' => 'Account temporarily locked due to multiple failed attempts. Try again later.'];
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Increment failed attempts
        $failed_attempts = $user['failed_login_attempts'] + 1;
        $locked_until = null;
        
        // Lock account after 5 failed attempts for 15 minutes
        if ($failed_attempts >= 5) {
            $locked_until = date('Y-m-d H:i:s', time() + (15 * 60));
        }
        
        $stmt = $conn->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?");
        $stmt->bind_param("isi", $failed_attempts, $locked_until, $user['id']);
        $stmt->execute();
        
        $conn->close();
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }
    
    // Successful login - reset failed attempts and update last login
    $session_token = bin2hex(random_bytes(32));
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW(), failed_login_attempts = 0, locked_until = NULL, session_token = ? WHERE id = ?");
    $stmt->bind_param("si", $session_token, $user['id']);
    $stmt->execute();
    
    // Store user info in session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['user_logged_in'] = true;
    $_SESSION['session_token'] = $session_token;
    $_SESSION['login_time'] = time();
    $_SESSION['company_domain'] = COMPANY_DOMAIN;
    
    $conn->close();
    
    // Check if password change is required
    if ($user['password_must_change']) {
        return ['success' => true, 'password_change_required' => true, 'message' => 'Login successful. You must change your password.'];
    }
    
    return ['success' => true, 'password_change_required' => false, 'message' => 'Login successful.'];
}

/**
 * Check if user is logged in and session is valid
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
        return false;
    }
    
    // Check session timeout (2 hours)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 7200)) {
        logout();
        return false;
    }
    
    // Validate session token
    if (isset($_SESSION['user_id']) && isset($_SESSION['session_token'])) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("SELECT session_token FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                if ($user['session_token'] !== $_SESSION['session_token']) {
                    $conn->close();
                    logout();
                    return false;
                }
            }
            $conn->close();
        }
    }
    
    return true;
}

/**
 * Check if password change is required
 */
function isPasswordChangeRequired() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        return false;
    }
    
    $stmt = $conn->prepare("SELECT password_must_change FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $conn->close();
        return (bool)$user['password_must_change'];
    }
    
    $conn->close();
    return false;
}

/**
 * Change user password
 */
function changePassword($current_password, $new_password) {
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'You must be logged in to change password.'];
    }
    
    // Validate new password strength
    if (strlen($new_password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
    }
    
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
        return ['success' => false, 'message' => 'Password must contain at least one lowercase letter, one uppercase letter, and one number.'];
    }
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'System error. Please try again.'];
    }
    
    // Get current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$user = $result->fetch_assoc()) {
        $conn->close();
        return ['success' => false, 'message' => 'User not found.'];
    }
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $conn->close();
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }
    
    // Update password
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ?, password_must_change = 0, password_changed_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $new_hash, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $conn->close();
        return ['success' => true, 'message' => 'Password changed successfully.'];
    } else {
        $conn->close();
        return ['success' => false, 'message' => 'Failed to update password.'];
    }
}

/**
 * Logout user and clear session
 */
function logout() {
    // Clear session token in database
    if (isset($_SESSION['user_id'])) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("UPDATE users SET session_token = NULL WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $conn->close();
        }
    }
    
    // Clear session
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Require authentication for protected pages
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: index.php?error=login_required');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireAuth();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: dashboard.php?error=access_denied');
        exit;
    }
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['user_role'],
        'full_name' => $_SESSION['full_name']
    ];
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>

/**
 * Check if user has specific role
 */
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'] ?? 'crew';
    
    $role_hierarchy = [
        'crew' => 1,
        'engineer' => 2,
        'captain' => 3,
        'admin' => 4
    ];
    
    return ($role_hierarchy[$user_role] ?? 0) >= ($role_hierarchy[$required_role] ?? 0);
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Require specific role - redirect if insufficient privileges
 */
function requireRole($required_role) {
    requireLogin();
    
    if (!hasRole($required_role)) {
        header('Location: dashboard.php?error=insufficient_privileges');
        exit;
    }
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
