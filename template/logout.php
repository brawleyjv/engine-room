<?php
/**
 * Logout Page Template
 * Secure logout with session cleanup
 */

// Include company configuration and authentication
require_once 'config.php';
require_once 'includes/auth.php';

// Perform logout
logout();

// Redirect to login page with logout message
header('Location: index.php?action=logout');
exit;
?>
