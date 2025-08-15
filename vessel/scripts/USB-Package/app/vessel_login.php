<?php
/**
 * Vessel Logger - Landing Page with Login
 * Role-based authentication for crew members
 */

// Define constant before including config (only if not already defined)
if (!defined('VESSEL_LOGGER')) {
    define('VESSEL_LOGGER', true);
}

// Include vessel sync manager
require_once 'vessel_sync.php';

// Check if vessel is registered before allowing access
if (!VesselSyncManager::isVesselRegistered()) {
    header('Location: vessel_registration.php');
    exit;
}

// Start session
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Get vessel configuration for display (no server validation needed)
try {
    $sync_manager = new VesselSyncManager();
    $vessel_config = $sync_manager->getConfig();
    $vessel_name = $vessel_config['vessel_name'] ?? 'Unknown Vessel';
} catch (Exception $e) {
    error_log("Could not load vessel config: " . $e->getMessage());
    $vessel_name = 'Vessel Logger'; // Fallback
}

$error_message = '';

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Demo users for testing (in production, this would be from database)
    $users = [
        'captain' => ['password' => 'captain123', 'role' => 'wheelhouse', 'name' => 'Captain Smith'],
        'pilot' => ['password' => 'pilot123', 'role' => 'wheelhouse', 'name' => 'Pilot Johnson'],
        'wheelman' => ['password' => 'wheel123', 'role' => 'wheelhouse', 'name' => 'Wheelman Davis'],
        'engineer' => ['password' => 'engine123', 'role' => 'engineer', 'name' => 'Chief Engineer Brown'],
        'assistant' => ['password' => 'assist123', 'role' => 'engineer', 'name' => 'Assistant Engineer Wilson']
    ];
    
    if (isset($users[$username]) && $users[$username]['password'] === $password) {
        // Valid login
        $_SESSION['username'] = $username;
        $_SESSION['user_role'] = $users[$username]['role'];
        $_SESSION['user_name'] = $users[$username]['name'];
        $_SESSION['login_time'] = time();
        
        // Redirect based on role
        if ($users[$username]['role'] === 'wheelhouse') {
            header('Location: wheelhouse_dashboard.php');
        } else {
            header('Location: engineer_dashboard.php');
        }
        exit;
    } else {
        $error_message = 'Invalid username or password';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($vessel_name); ?> - Vessel Logger</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1rem;
        }
        
        .login-form {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-1px);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fcc;
        }
        
        .demo-users {
            background: #f8f9fa;
            padding: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .demo-users h3 {
            margin-bottom: 1rem;
            color: #495057;
            font-size: 1rem;
        }
        
        .user-list {
            display: grid;
            gap: 0.5rem;
        }
        
        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            background: white;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .user-role {
            font-weight: 600;
            color: #667eea;
        }
        
        .wheelhouse {
            color: #2196F3;
        }
        
        .engineer {
            color: #FF9800;
        }
        
        .status-bar {
            background: #28a745;
            color: white;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="status-bar">
            🟢 SYSTEM ONLINE | PORT 8080 | <?php echo date('Y-m-d H:i:s'); ?>
        </div>
        
        <div class="header">
            <h1>⚓ <?php echo htmlspecialchars($vessel_name); ?></h1>
            <p>Vessel Logger System</p>
        </div>
        
        <div class="login-form">
            <?php if ($error_message): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                
                <button type="submit" class="login-btn">🚢 LOG IN</button>
            </form>
        </div>
        
        <div class="demo-users">
            <h3>Demo Users for Testing:</h3>
            <div class="user-list">
                <div class="user-item">
                    <span><strong>captain</strong> / captain123</span>
                    <span class="user-role wheelhouse">WHEELHOUSE</span>
                </div>
                <div class="user-item">
                    <span><strong>pilot</strong> / pilot123</span>
                    <span class="user-role wheelhouse">WHEELHOUSE</span>
                </div>
                <div class="user-item">
                    <span><strong>wheelman</strong> / wheel123</span>
                    <span class="user-role wheelhouse">WHEELHOUSE</span>
                </div>
                <div class="user-item">
                    <span><strong>engineer</strong> / engine123</span>
                    <span class="user-role engineer">ENGINEER</span>
                </div>
                <div class="user-item">
                    <span><strong>assistant</strong> / assist123</span>
                    <span class="user-role engineer">ENGINEER</span>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Update time every second
        setInterval(() => {
            const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
            document.querySelector('.status-bar').innerHTML = 
                `🟢 SYSTEM ONLINE | PORT 8080 | ${now}`;
        }, 1000);
    </script>
</body>
</html>
