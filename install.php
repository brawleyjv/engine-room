<?php
/**
 * Installation Protection
 * Prevents reinstallation if system is already set up
 */

// Check if already installed
if (file_exists('../config_installed.php')) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Already Installed</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; text-align: center; }
            .message { background: #e3f2fd; padding: 30px; border-radius: 10px; }
            .btn { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }
        </style>
    </head>
    <body>
        <div class="message">
            <h1>🚢 Vessel Management System</h1>
            <h2>Already Installed</h2>
            <p>This vessel management system has already been installed and configured.</p>
            <p>If you need to reinstall, please contact your system administrator or delete the configuration file.</p>
            <br>
            <a href="../index.php" class="btn">Go to Application</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Include main installation file
require_once 'index.php';
?>
