<?php
/**
 * Vessel Logger - Landing Page with Login
 * Role-based authentication for vessel crew
 */

// Define constant before including config
define('VESSEL_LOGGER', true);

// Start session
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Redirect to appropriate dashboard based on role
    switch ($_SESSION['role']) {
        case 'captain':
        case 'pilot':
        case 'wheelman':
            header('Location: wheelhouse_dashboard.php');
            break;
        case 'engineer':
        case 'chief_engineer':
            header('Location: engineer_dashboard.php');
            break;
        case 'admin':
            header('Location: admin_dashboard.php');
            break;
        default:
            header('Location: crew_dashboard.php');
    }
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Demo users - in production this would check against database
    $users = [
        'captain' => ['password' => 'captain123', 'role' => 'captain', 'name' => 'Captain Smith'],
        'pilot' => ['password' => 'pilot123', 'role' => 'pilot', 'name' => 'Pilot Johnson'],
        'wheelman' => ['password' => 'wheel123', 'role' => 'wheelman', 'name' => 'Wheelman Davis'],
        'engineer' => ['password' => 'eng123', 'role' => 'engineer', 'name' => 'Engineer Wilson'],
        'chief_eng' => ['password' => 'chief123', 'role' => 'chief_engineer', 'name' => 'Chief Engineer Brown'],
        'admin' => ['password' => 'admin123', 'role' => 'admin', 'name' => 'Administrator']
    ];
    
    if (isset($users[$username]) && $users[$username]['password'] === $password) {
        // Successful login
        $_SESSION['user_id'] = $username;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $users[$username]['role'];
        $_SESSION['full_name'] = $users[$username]['name'];
        $_SESSION['login_time'] = time();
        
        // Redirect to appropriate dashboard
        switch ($users[$username]['role']) {
            case 'captain':
            case 'pilot':
            case 'wheelman':
                header('Location: wheelhouse_dashboard.php');
                break;
            case 'engineer':
            case 'chief_engineer':
                header('Location: engineer_dashboard.php');
                break;
            case 'admin':
                header('Location: admin_dashboard.php');
                break;
            default:
                header('Location: crew_dashboard.php');
        }
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Logger - Login</title>
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
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #1e3c72;
        }
        
        .title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
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
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #1e3c72;
        }
        
        .login-btn {
            width: 100%;
            background: #1e3c72;
            color: white;
            border: none;
            padding: 1rem;
            font-size: 1.1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
            font-weight: 600;
        }
        
        .login-btn:hover {
            background: #2a5298;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }
        
        .demo-users {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: left;
        }
        
        .demo-users h4 {
            color: #1e3c72;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .user-role {
            margin-bottom: 1rem;
            padding: 0.8rem;
            background: white;
            border-radius: 6px;
            border-left: 4px solid #1e3c72;
        }
        
        .user-role strong {
            color: #1e3c72;
        }
        
        .role-badge {
            display: inline-block;
            background: #e3f2fd;
            color: #1565c0;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }
        
        .system-status {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">⚓</div>
        <div class="title">Vessel Logger</div>
        <div class="subtitle">Crew Authentication Portal</div>
        
        <div class="system-status">
            <strong>🟢 System Online</strong><br>
            Port 8080 | SQLite Ready | <?php echo date('Y-m-d H:i:s'); ?>
        </div>
        
        <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       placeholder="Enter your username">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>
            
            <button type="submit" class="login-btn">🔓 Login to Dashboard</button>
        </form>
        
        <div class="demo-users">
            <h4>🎯 Demo User Accounts</h4>
            
            <div class="user-role">
                <strong>Wheelhouse Crew:</strong>
                <span class="role-badge">Bridge Operations</span><br>
                <code>captain / captain123</code><br>
                <code>pilot / pilot123</code><br>
                <code>wheelman / wheel123</code>
            </div>
            
            <div class="user-role">
                <strong>Engine Room:</strong>
                <span class="role-badge">Engineering</span><br>
                <code>engineer / eng123</code><br>
                <code>chief_eng / chief123</code>
            </div>
            
            <div class="user-role">
                <strong>Administration:</strong>
                <span class="role-badge">System Admin</span><br>
                <code>admin / admin123</code>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-focus username field
        document.getElementById('username').focus();
        
        // Update time every second
        setInterval(() => {
            const now = new Date().toLocaleString();
            document.querySelector('.system-status').innerHTML = 
                '<strong>🟢 System Online</strong><br>Port 8080 | SQLite Ready | ' + now;
        }, 1000);
    </script>
</body>
</html>
