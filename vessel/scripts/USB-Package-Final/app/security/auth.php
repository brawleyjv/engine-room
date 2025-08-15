<?php
/**
 * Vessel Authentication System
 * Handles user authentication, session management, and access control
 */

// Prevent direct access
if (!defined('VESSEL_LOGGER')) {
    die('Direct access not permitted');
}

/**
 * Authenticate user
 */
function authenticateUser($username, $password) {
    $db = getDatabase();
    
    // Check for rate limiting
    if (isLoginRateLimited($username)) {
        logMessage("Login rate limited for user: $username", 'WARNING');
        return ['success' => false, 'error' => 'Too many login attempts. Please try again later.'];
    }
    
    // Get user from database
    $stmt = $db->prepare("
        SELECT id, username, password_hash, full_name, role, active, last_login
        FROM users 
        WHERE username = ? AND active = 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        recordLoginAttempt($username, false, 'User not found');
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    // Verify password
    if (!verifyPassword($password, $user['password_hash'])) {
        recordLoginAttempt($username, false, 'Invalid password');
        return ['success' => false, 'error' => 'Invalid username or password'];
    }
    
    // Update last login
    $stmt = $db->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Create session
    createUserSession($user);
    
    recordLoginAttempt($username, true, 'Successful login');
    logMessage("User logged in: $username", 'INFO');
    
    return ['success' => true, 'user' => $user];
}

/**
 * Create user session
 */
function createUserSession($user) {
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    $_SESSION['session_token'] = generateSecureToken();
    $_SESSION['vessel_id'] = getVesselConfig('vessel_id');
    $_SESSION['vessel_name'] = getVesselConfig('vessel_name');
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > VESSEL_SESSION_TIMEOUT) {
            logoutUser();
            return false;
        }
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Get current logged-in user
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDatabase();
    $stmt = $db->prepare("
        SELECT id, username, full_name, role, email, created_at, last_login
        FROM users 
        WHERE id = ? AND active = 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    
    return $stmt->fetch();
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        // If AJAX request, return JSON error
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
        
        // Regular request - redirect to login
        header('Location: login.php');
        exit;
    }
}

/**
 * Check if user has specific role
 */
function hasRole($required_role) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_role = $_SESSION['role'] ?? 'crew';
    
    $role_hierarchy = [
        'crew' => 1,
        'engineer' => 2,
        'captain' => 3,
        'admin' => 4
    ];
    
    return ($role_hierarchy[$user_role] ?? 0) >= ($role_hierarchy[$required_role] ?? 0);
}

/**
 * Require specific role
 */
function requireRole($required_role) {
    requireAuth();
    
    if (!hasRole($required_role)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient privileges']);
            exit;
        }
        
        header('Location: dashboard.php?error=insufficient_privileges');
        exit;
    }
}

/**
 * Logout user
 */
function logoutUser() {
    $username = $_SESSION['username'] ?? 'unknown';
    
    // Clear session data
    session_unset();
    session_destroy();
    
    // Start new session
    session_start();
    
    logMessage("User logged out: $username", 'INFO');
}

/**
 * Check if login is rate limited
 */
function isLoginRateLimited($username) {
    $db = getDatabase();
    
    // Check failed attempts in the last 15 minutes
    $stmt = $db->prepare("
        SELECT COUNT(*) as attempt_count
        FROM login_attempts 
        WHERE username = ? 
        AND success = 0 
        AND attempt_time > datetime('now', '-15 minutes')
    ");
    $stmt->execute([$username]);
    $result = $stmt->fetch();
    
    return ($result['attempt_count'] ?? 0) >= VESSEL_MAX_LOGIN_ATTEMPTS;
}

/**
 * Record login attempt
 */
function recordLoginAttempt($username, $success, $notes = '') {
    $db = getDatabase();
    
    // Create login_attempts table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL,
            ip_address TEXT,
            success BOOLEAN NOT NULL,
            attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            notes TEXT
        )
    ");
    
    $stmt = $db->prepare("
        INSERT INTO login_attempts (username, ip_address, success, notes) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$username, getClientIP(), $success ? 1 : 0, $notes]);
    
    // Clean old attempts (older than 24 hours)
    $db->exec("
        DELETE FROM login_attempts 
        WHERE attempt_time < datetime('now', '-24 hours')
    ");
}

/**
 * Create new user
 */
function createUser($username, $password, $full_name, $role = 'crew', $email = '') {
    $db = getDatabase();
    
    // Check if username already exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'error' => 'Username already exists'];
    }
    
    // Validate password strength
    if (strlen($password) < 6) {
        return ['success' => false, 'error' => 'Password must be at least 6 characters'];
    }
    
    // Hash password
    $password_hash = hashPassword($password);
    
    // Insert user
    $stmt = $db->prepare("
        INSERT INTO users (username, password_hash, full_name, role, email) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$username, $password_hash, $full_name, $role, $email])) {
        $user_id = $db->lastInsertId();
        logMessage("User created: $username (ID: $user_id)", 'INFO');
        return ['success' => true, 'user_id' => $user_id];
    } else {
        return ['success' => false, 'error' => 'Failed to create user'];
    }
}

/**
 * Update user password
 */
function updateUserPassword($user_id, $new_password) {
    $db = getDatabase();
    
    // Validate password strength
    if (strlen($new_password) < 6) {
        return ['success' => false, 'error' => 'Password must be at least 6 characters'];
    }
    
    $password_hash = hashPassword($new_password);
    
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    
    if ($stmt->execute([$password_hash, $user_id])) {
        logMessage("Password updated for user ID: $user_id", 'INFO');
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Failed to update password'];
    }
}

/**
 * Get all users
 */
function getAllUsers() {
    $db = getDatabase();
    
    $stmt = $db->prepare("
        SELECT id, username, full_name, role, email, created_at, last_login, active
        FROM users 
        ORDER BY full_name
    ");
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Update user status
 */
function updateUserStatus($user_id, $active) {
    $db = getDatabase();
    
    $stmt = $db->prepare("UPDATE users SET active = ? WHERE id = ?");
    
    if ($stmt->execute([$active ? 1 : 0, $user_id])) {
        $status = $active ? 'activated' : 'deactivated';
        logMessage("User $status: ID $user_id", 'INFO');
        return ['success' => true];
    } else {
        return ['success' => false, 'error' => 'Failed to update user status'];
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateSecureToken();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Security headers
 */
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Only set HSTS if using HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/**
 * Initialize default admin user if no users exist
 */
function initializeDefaultUser() {
    $db = getDatabase();
    
    // Check if any users exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $user_count = $stmt->fetchColumn();
    
    if ($user_count == 0) {
        // Create default admin user
        $default_password = generateSecureToken(8);
        $result = createUser('admin', $default_password, 'Administrator', 'admin');
        
        if ($result['success']) {
            // Save password to file for initial setup
            $password_file = VESSEL_ROOT . '/data/default_admin_password.txt';
            file_put_contents($password_file, "Default admin password: $default_password\n");
            chmod($password_file, 0600); // Restrict access
            
            logMessage("Default admin user created with password saved to: $password_file", 'INFO');
            return $default_password;
        }
    }
    
    return null;
}
?>
