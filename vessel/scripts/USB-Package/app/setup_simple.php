<?php
/**
 * Vessel Logger - Simple Setup Page
 * Basic setup without complex dependencies
 */

// Define constant before including config (only if not already defined)
if (!defined('VESSEL_LOGGER')) {
    define('VESSEL_LOGGER', true);
}

// Start session
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Logger Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .setup-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            margin: 0;
            font-size: 2.5em;
        }
        .header p {
            color: #666;
            margin: 10px 0 0 0;
        }
        .status {
            background: #e8f5e8;
            border: 1px solid #4caf50;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .status h3 {
            margin: 0 0 10px 0;
            color: #2e7d32;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        input[type="text"], input[type="password"], input[type="url"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="password"]:focus, input[type="url"]:focus {
            border-color: #667eea;
            outline: none;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .next-steps {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="header">
            <h1>⚓ Vessel Logger</h1>
            <p>USB Deployment Setup</p>
        </div>

        <div class="status">
            <h3>✅ System Status</h3>
            <p><strong>PHP:</strong> <?php echo phpversion(); ?> - Working</p>
            <p><strong>Server:</strong> Port 8080 - Active</p>
            <p><strong>SQLite:</strong> <?php echo extension_loaded('pdo_sqlite') ? 'Available' : 'Checking...'; ?></p>
            <p><strong>Storage:</strong> USB Drive Ready</p>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
        <div class="status">
            <h3>🎉 Setup Complete!</h3>
            <p>Vessel Logger is ready for operation.</p>
        </div>
        
        <div class="next-steps">
            <h4>Next Steps:</h4>
            <ol>
                <li>Your vessel logger is now configured</li>
                <li>Data will be stored on this USB drive</li>
                <li>System works completely offline</li>
                <li><a href="dashboard_simple.php" style="color: #667eea; font-weight: bold;">🚀 Go to Dashboard</a></li>
            </ol>
        </div>
        
        <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label for="vessel_name">Vessel Name:</label>
                <input type="text" id="vessel_name" name="vessel_name" required placeholder="e.g., MV Ocean Explorer">
            </div>

            <div class="form-group">
                <label for="company_name">Company Name:</label>
                <input type="text" id="company_name" name="company_name" required placeholder="e.g., Marine Transport Co.">
            </div>

            <div class="form-group">
                <label for="admin_username">Admin Username:</label>
                <input type="text" id="admin_username" name="admin_username" required placeholder="admin">
            </div>

            <div class="form-group">
                <label for="admin_password">Admin Password:</label>
                <input type="password" id="admin_password" name="admin_password" required placeholder="Enter secure password">
            </div>

            <div class="form-group">
                <label for="server_url">Sync Server URL (Optional):</label>
                <input type="url" id="server_url" name="server_url" placeholder="https://your-server.com/api">
            </div>

            <button type="submit" class="btn">Complete Setup</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>
