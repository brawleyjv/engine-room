<?php
/**
 * Enhanced Authentication Functions with Multi-Tier Role Support
 * Handles user login, session management, and role-based permissions
 */

require_once __DIR__ . '/config_saas.php';
require_once __DIR__ . '/user_roles.php';

// Start session if not already started
function ensure_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
function is_logged_in() {
    ensure_session();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Check if user is admin (legacy function - now uses role system)
function is_admin() {
    return userHasPermission('system_admin');
}

// Get current user info with role information
function get_logged_in_user() {
    ensure_session();
    if (!is_logged_in()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'first_name' => $_SESSION['first_name'],
        'last_name' => $_SESSION['last_name'],
        'full_name' => $_SESSION['first_name'] . ' ' . $_SESSION['last_name'],
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'deck_crew',
        'assigned_vessel_id' => $_SESSION['assigned_vessel_id'] ?? null,
        'is_admin' => userHasPermission('system_admin'), // Dynamic based on role
        'location' => $_SESSION['user_location'] ?? 'vessel'
    ];
}

// Enhanced login function with role support
function login_user($conn, $username, $password) {
    $sql = "SELECT UserID, Username, Email, PasswordHash, FirstName, LastName, Role, AssignedVesselID, IsActive 
            FROM users 
            WHERE (Username = ? OR Email = ?) AND IsActive = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['PasswordHash'])) {
            ensure_session();
            
            // Get role information
            $role_info = getUserRole($user['Role'] ?? 'deck_crew');
            
            // Set session variables
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['first_name'] = $user['FirstName'];
            $_SESSION['last_name'] = $user['LastName'];
            $_SESSION['email'] = $user['Email'];
            $_SESSION['role'] = $user['Role'] ?? 'deck_crew';
            $_SESSION['assigned_vessel_id'] = $user['AssignedVesselID'];
            $_SESSION['user_location'] = $role_info['location'] ?? 'vessel';
            
            // Set system mode based on user role location
            if ($role_info && $role_info['location'] === 'shore') {
                setShoreMode();
            } else {
                // For vessel users, set vessel mode if they have an assigned vessel
                if ($user['AssignedVesselID']) {
                    setVesselMode($user['AssignedVesselID']);
                }
            }
            
            // Update last login time
            $update_sql = "UPDATE users SET LastLogin = NOW() WHERE UserID = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('i', $user['UserID']);
            $update_stmt->execute();
            
            return [
                'success' => true,
                'user' => get_logged_in_user(),
                'redirect' => determineLoginRedirect($role_info)
            ];
        } else {
            return ['success' => false, 'error' => 'Invalid password'];
        }
    } else {
        return ['success' => false, 'error' => 'User not found or inactive'];
    }
}

// Determine where to redirect user after login based on their role
function determineLoginRedirect($role_info) {
    if (!$role_info) {
        return '/dashboard.php';
    }
    
    switch ($role_info['location']) {
        case 'shore':
            return '/office_dashboard.php';
        case 'vessel':
            return '/vessel/engineroom/dashboard_offline.php';
        default:
            return '/dashboard.php';
    }
}

// Create new user with role assignment
function create_user($conn, $user_data) {
    $required_fields = ['username', 'email', 'password', 'first_name', 'last_name', 'role'];
    
    foreach ($required_fields as $field) {
        if (empty($user_data[$field])) {
            return ['success' => false, 'error' => "Missing required field: $field"];
        }
    }
    
    // Validate role
    $role_info = getUserRole($user_data['role']);
    if (!$role_info) {
        return ['success' => false, 'error' => 'Invalid role specified'];
    }
    
    // Check if username/email already exists
    $check_sql = "SELECT COUNT(*) FROM users WHERE Username = ? OR Email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('ss', $user_data['username'], $user_data['email']);
    $check_stmt->execute();
    $count = $check_stmt->get_result()->fetch_row()[0];
    
    if ($count > 0) {
        return ['success' => false, 'error' => 'Username or email already exists'];
    }
    
    // Hash password
    $password_hash = password_hash($user_data['password'], PASSWORD_DEFAULT);
    
    // Insert user
    $sql = "INSERT INTO users (Username, Email, PasswordHash, FirstName, LastName, Role, AssignedVesselID, IsActive) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssi', 
        $user_data['username'],
        $user_data['email'], 
        $password_hash,
        $user_data['first_name'],
        $user_data['last_name'],
        $user_data['role'],
        $user_data['assigned_vessel_id'] ?? null
    );
    
    if ($stmt->execute()) {
        return [
            'success' => true, 
            'user_id' => $conn->insert_id,
            'message' => 'User created successfully'
        ];
    } else {
        return ['success' => false, 'error' => 'Failed to create user: ' . $stmt->error];
    }
}

// Logout user
function logout_user() {
    ensure_session();
    session_destroy();
    return true;
}

// Require login (redirect to login page if not logged in)
function require_login($redirect_url = null) {
    if ($redirect_url === null) {
        $redirect_url = BASE_URL . '/login_enhanced.php';
    }
    
    if (!is_logged_in()) {
        header("Location: $redirect_url");
        exit;
    }
    
    // Validate that user session matches current environment
    if (!validateSessionEnvironment()) {
        logout_user();
        header("Location: $redirect_url?error=invalid_environment");
        exit;
    }
}

// Require admin access
function require_admin($redirect_url = null) {
    if ($redirect_url === null) {
        $redirect_url = BASE_URL . '/unauthorized.php';
    }
    require_login();
    requirePermission('system_admin', $redirect_url);
}

// Check if user has vessel selected (for vessel operations)
function has_vessel_selected() {
    ensure_session();
    return isset($_SESSION['active_vessel_id']) && !empty($_SESSION['active_vessel_id']);
}

// Require vessel selection
function require_vessel_selection($redirect_url = '/select_vessel.php') {
    require_login();
    
    // Shore users with appropriate permissions don't need vessel selection
    if (userHasPermission('view_all_vessels')) {
        return;
    }
    
    if (!has_vessel_selected()) {
        header("Location: $redirect_url");
        exit;
    }
}

// Set active vessel for current session
function set_active_vessel($vessel_id) {
    ensure_session();
    
    // Check if user can access this vessel
    if (userCanAccessVessel($vessel_id)) {
        $_SESSION['active_vessel_id'] = $vessel_id;
        return true;
    }
    
    return false;
}

// Get current active vessel
function get_current_vessel($conn) {
    if (!has_vessel_selected()) {
        return null;
    }
    
    $vessel_id = $_SESSION['active_vessel_id'];
    
    $sql = "SELECT * FROM vessels WHERE VesselID = ? AND IsActive = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $vessel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Check password strength
function validate_password($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
}

// Get user's accessible vessels
function getUserVessels($conn) {
    $current_user = get_logged_in_user();
    if (!$current_user) {
        return [];
    }
    
    if (userHasPermission('view_all_vessels')) {
        // Shore users can see all vessels
        $sql = "SELECT * FROM vessels WHERE IsActive = 1 ORDER BY VesselName";
        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // Vessel users can only see their assigned vessel
        if ($current_user['assigned_vessel_id']) {
            $sql = "SELECT * FROM vessels WHERE VesselID = ? AND IsActive = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $current_user['assigned_vessel_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }
    
    return [];
}
?>
