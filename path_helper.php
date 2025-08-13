<?php
/**
 * Path Helper Functions
 * Makes the application work regardless of folder location
 */

// If BASE_URL is not already defined, define it
if (!defined('BASE_URL')) {
    define('APP_ROOT', dirname(__FILE__));
    define('APP_URL_PATH', str_replace($_SERVER['DOCUMENT_ROOT'], '', APP_ROOT));
    define('BASE_URL', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . APP_URL_PATH);
}

/**
 * Generate a URL relative to the application root
 */
function app_url($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

/**
 * Generate a path relative to the application root
 */
function app_path($path = '') {
    $path = ltrim($path, '/');
    return APP_ROOT . '/' . $path;
}

/**
 * Redirect to a URL within the application
 */
function app_redirect($path) {
    header('Location: ' . app_url($path));
    exit;
}

/**
 * Include a file relative to the application root
 */
function app_include($path) {
    return include app_path($path);
}

/**
 * Require a file relative to the application root
 */
function app_require($path) {
    return require app_path($path);
}

/**
 * Require once a file relative to the application root
 */
function app_require_once($path) {
    return require_once app_path($path);
}
?>
