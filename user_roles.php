<?php
/**
 * User Role Management System
 * Handles different user types and permissions across vessel and shore systems
 */

// Define user roles and their permissions
$user_roles = [
    // Vessel-based roles
    'engineer' => [
        'name' => 'Engineer',
        'location' => 'vessel',
        'permissions' => [
            'add_engine_logs' => true,
            'view_engine_logs' => true,
            'edit_own_logs' => true,
            'delete_own_logs' => false,
            'view_all_logs' => true,
            'export_data' => false,
            'manage_users' => false,
            'system_admin' => false
        ]
    ],
    
    'wheelman' => [
        'name' => 'Wheelman/Navigator', 
        'location' => 'vessel',
        'permissions' => [
            'add_engine_logs' => true,
            'view_engine_logs' => true,
            'edit_own_logs' => true,
            'delete_own_logs' => false,
            'view_all_logs' => true,
            'export_data' => false,
            'manage_users' => false,
            'system_admin' => false
        ]
    ],
    
    'deck_crew' => [
        'name' => 'Deck Crew',
        'location' => 'vessel', 
        'permissions' => [
            'add_engine_logs' => true,
            'view_engine_logs' => true,
            'edit_own_logs' => true,
            'delete_own_logs' => false,
            'view_all_logs' => false, // Only own logs
            'export_data' => false,
            'manage_users' => false,
            'system_admin' => false
        ]
    ],
    
    'chief_engineer' => [
        'name' => 'Chief Engineer',
        'location' => 'vessel',
        'permissions' => [
            'add_engine_logs' => true,
            'view_engine_logs' => true,
            'edit_own_logs' => true,
            'delete_own_logs' => true,
            'view_all_logs' => true,
            'export_data' => true,
            'manage_users' => true,
            'system_admin' => false
        ]
    ],
    
    // Shore-based roles
    'fleet_manager' => [
        'name' => 'Fleet Manager',
        'location' => 'shore',
        'permissions' => [
            'add_engine_logs' => false,
            'view_engine_logs' => true,
            'edit_own_logs' => false,
            'delete_own_logs' => false,
            'view_all_logs' => true,
            'export_data' => true,
            'manage_users' => true,
            'system_admin' => false,
            'view_all_vessels' => true,
            'manage_vessels' => true
        ]
    ],
    
    'port_engineer' => [
        'name' => 'Port Engineer',
        'location' => 'shore',
        'permissions' => [
            'add_engine_logs' => false,
            'view_engine_logs' => true,
            'edit_own_logs' => false,
            'delete_own_logs' => false,
            'view_all_logs' => true,
            'export_data' => true,
            'manage_users' => false,
            'system_admin' => false,
            'view_all_vessels' => true,
            'manage_vessels' => false
        ]
    ],
    
    'office_admin' => [
        'name' => 'Office Administrator',
        'location' => 'shore',
        'permissions' => [
            'add_engine_logs' => false,
            'view_engine_logs' => true,
            'edit_own_logs' => false,
            'delete_own_logs' => false,
            'view_all_logs' => true,
            'export_data' => true,
            'manage_users' => true,
            'system_admin' => true,
            'view_all_vessels' => true,
            'manage_vessels' => true
        ]
    ],
    
    'support_tech' => [
        'name' => 'Support Technician',
        'location' => 'shore',
        'permissions' => [
            'add_engine_logs' => false,
            'view_engine_logs' => true,
            'edit_own_logs' => false,
            'delete_own_logs' => false,
            'view_all_logs' => true,
            'export_data' => false,
            'manage_users' => false,
            'system_admin' => true, // For troubleshooting
            'view_all_vessels' => true,
            'manage_vessels' => false
        ]
    ]
];

/**
 * Check if current user has a specific permission
 */
function userHasPermission($permission) {
    $current_user = get_logged_in_user();
    if (!$current_user || !isset($current_user['role'])) {
        return false;
    }
    
    global $user_roles;
    $role = $current_user['role'];
    
    return isset($user_roles[$role]['permissions'][$permission]) && 
           $user_roles[$role]['permissions'][$permission] === true;
}

/**
 * Get user role information
 */
function getUserRole($role_key) {
    global $user_roles;
    return $user_roles[$role_key] ?? null;
}

/**
 * Get all available roles for a specific location
 */
function getRolesForLocation($location) {
    global $user_roles;
    return array_filter($user_roles, function($role) use ($location) {
        return $role['location'] === $location;
    });
}

/**
 * Check if user can access a specific vessel
 */
function userCanAccessVessel($vessel_id) {
    $current_user = get_logged_in_user();
    if (!$current_user) {
        return false;
    }
    
    // Shore users with appropriate permissions can access all vessels
    if (userHasPermission('view_all_vessels')) {
        return true;
    }
    
    // Vessel users can only access their assigned vessel
    if (isset($current_user['assigned_vessel_id'])) {
        return $current_user['assigned_vessel_id'] == $vessel_id;
    }
    
    return false;
}

/**
 * Require specific permission (redirect if not authorized)
 */
function requirePermission($permission, $redirect_url = '/unauthorized.php') {
    if (!userHasPermission($permission)) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Get user's display name with role
 */
function getUserDisplayName($user = null) {
    if (!$user) {
        $user = get_logged_in_user();
    }
    
    if (!$user) {
        return 'Unknown User';
    }
    
    $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
    $role_info = getUserRole($user['role'] ?? '');
    $role_name = $role_info ? $role_info['name'] : 'User';
    
    return "$name ($role_name)";
}

/**
 * Check if current session is valid for the current environment
 */
function validateSessionEnvironment() {
    $current_user = get_logged_in_user();
    if (!$current_user) {
        return false;
    }
    
    $user_role_info = getUserRole($current_user['role'] ?? '');
    if (!$user_role_info) {
        return false;
    }
    
    $is_shore = isShoreSystem();
    $user_location = $user_role_info['location'];
    
    // Shore users should be on shore system, vessel users on vessel system
    return ($is_shore && $user_location === 'shore') || 
           (!$is_shore && $user_location === 'vessel');
}

/**
 * Get navigation menu items based on user permissions
 */
function getNavigationMenu() {
    $menu = [];
    
    // Always available
    $menu[] = ['title' => 'Dashboard', 'url' => '/dashboard.php', 'icon' => '🏠'];
    
    if (userHasPermission('add_engine_logs')) {
        $menu[] = ['title' => 'Add Log Entry', 'url' => '/vessel/engineroom/add_log_offline.php', 'icon' => '➕'];
    }
    
    if (userHasPermission('view_engine_logs')) {
        $menu[] = ['title' => 'View Logs', 'url' => '/vessel/engineroom/view_logs.php', 'icon' => '📋'];
    }
    
    if (userHasPermission('export_data')) {
        $menu[] = ['title' => 'Export Data', 'url' => '/export.php', 'icon' => '📊'];
    }
    
    if (userHasPermission('manage_vessels')) {
        $menu[] = ['title' => 'Manage Vessels', 'url' => '/manage_vessels.php', 'icon' => '🚢'];
    }
    
    if (userHasPermission('manage_users')) {
        $menu[] = ['title' => 'Manage Users', 'url' => '/manage_users.php', 'icon' => '👥'];
    }
    
    if (userHasPermission('system_admin')) {
        $menu[] = ['title' => 'System Admin', 'url' => '/admin/', 'icon' => '⚙️'];
        $menu[] = ['title' => 'Support Dashboard', 'url' => '/support_dashboard.php', 'icon' => '🔧'];
    }
    
    return $menu;
}
?>
