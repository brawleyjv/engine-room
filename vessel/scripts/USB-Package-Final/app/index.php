<?php
// Vessel Logger - Main Entry Point

// Define constant before including config
define('VESSEL_LOGGER', true);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Start session
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Check if setup is complete
if (!isSetupComplete()) {
    header('Location: setup.php');
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Redirect to dashboard if everything is ready
header('Location: dashboard.php');
exit;
?>
