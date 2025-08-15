<?php
/**
 * Authentication Functions Template
 * This file gets copied to each company's includes folder
 */

/**
 * Authenticate user with username and password
 */
function authenticateUser($username, $password) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        return false;
    }
    
    $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ? AND active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Update last login
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            $update_stmt->execute();
            
            // Store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            $conn->close();
            return true;
        }
    }
    
    $conn->close();
    return false;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'];
}

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
