<?php
/**
 * Vessel Logger - Setup Wizard
 * Handles initial vessel configuration and user setup
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/includes/database.php';
require_once __DIR__ . '/app/includes/functions.php';

// Redirect if already set up
if (isVesselSetup()) {
    header('Location: /');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        $step = $_POST['step'] ?? '';
        
        if ($step === 'vessel_info') {
            // Step 1: Vessel Information
            $vessel_name = trim($_POST['vessel_name'] ?? '');
            $vessel_imo = trim($_POST['vessel_imo'] ?? '');
            $company_name = trim($_POST['company_name'] ?? '');
            $company_code = trim($_POST['company_code'] ?? '');
            
            if (empty($vessel_name)) $errors[] = 'Vessel name is required';
            if (empty($company_name)) $errors[] = 'Company name is required';
            if (empty($company_code)) $errors[] = 'Company code is required';
            
            if (!empty($vessel_imo) && !preg_match('/^\d{7}$/', $vessel_imo)) {
                $errors[] = 'IMO number must be 7 digits';
            }
            
            if (empty($errors)) {
                $_SESSION['setup_vessel'] = [
                    'name' => $vessel_name,
                    'imo' => $vessel_imo,
                    'company_name' => $company_name,
                    'company_code' => $company_code
                ];
                
                header('Location: /setup.php?step=admin_user');
                exit;
            }
            
        } elseif ($step === 'admin_user') {
            // Step 2: Admin User Creation
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            
            if (empty($username)) $errors[] = 'Username is required';
            if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters';
            if (empty($password)) $errors[] = 'Password is required';
            if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
            if ($password !== $confirm_password) $errors[] = 'Passwords do not match';
            if (empty($full_name)) $errors[] = 'Full name is required';
            
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
            
            if (empty($errors)) {
                $_SESSION['setup_admin'] = [
                    'username' => $username,
                    'password' => $password,
                    'full_name' => $full_name,
                    'email' => $email
                ];
                
                header('Location: /setup.php?step=server_config');
                exit;
            }
            
        } elseif ($step === 'server_config') {
            // Step 3: Server Configuration
            $server_url = trim($_POST['server_url'] ?? '');
            $vessel_key = trim($_POST['vessel_key'] ?? '');
            $sync_enabled = isset($_POST['sync_enabled']);
            $auto_sync_interval = (int)($_POST['auto_sync_interval'] ?? 60);
            
            if ($sync_enabled) {
                if (empty($server_url)) $errors[] = 'Server URL is required when sync is enabled';
                if (empty($vessel_key)) $errors[] = 'Vessel key is required when sync is enabled';
                
                if (!empty($server_url) && !filter_var($server_url, FILTER_VALIDATE_URL)) {
                    $errors[] = 'Invalid server URL format';
                }
                
                if ($auto_sync_interval < 5) {
                    $errors[] = 'Auto-sync interval must be at least 5 minutes';
                }
            }
            
            if (empty($errors)) {
                $_SESSION['setup_server'] = [
                    'server_url' => $server_url,
                    'vessel_key' => $vessel_key,
                    'sync_enabled' => $sync_enabled,
                    'auto_sync_interval' => $auto_sync_interval
                ];
                
                header('Location: /setup.php?step=complete');
                exit;
            }
            
        } elseif ($step === 'complete') {
            // Final step: Complete setup
            $db = getDatabase();
            
            // Start transaction
            $db->beginTransaction();
            
            try {
                // Insert vessel information
                $vessel_data = $_SESSION['setup_vessel'];
                $stmt = $db->prepare("
                    INSERT INTO vessel_config (key, value) VALUES 
                    ('vessel_name', :name),
                    ('vessel_imo', :imo),
                    ('company_name', :company_name),
                    ('company_code', :company_code),
                    ('setup_complete', '1'),
                    ('setup_date', :setup_date)
                ");
                
                $stmt->execute([
                    'name' => $vessel_data['name'],
                    'imo' => $vessel_data['imo'],
                    'company_name' => $vessel_data['company_name'],
                    'company_code' => $vessel_data['company_code'],
                    'setup_date' => date('Y-m-d H:i:s')
                ]);
                
                // Insert server configuration
                $server_data = $_SESSION['setup_server'];
                if ($server_data['sync_enabled']) {
                    $stmt = $db->prepare("
                        INSERT INTO vessel_config (key, value) VALUES 
                        ('server_url', :server_url),
                        ('vessel_key', :vessel_key),
                        ('sync_enabled', '1'),
                        ('auto_sync_interval', :interval)
                    ");
                    
                    $stmt->execute([
                        'server_url' => $server_data['server_url'],
                        'vessel_key' => $server_data['vessel_key'],
                        'interval' => $server_data['auto_sync_interval']
                    ]);
                } else {
                    $stmt = $db->prepare("INSERT INTO vessel_config (key, value) VALUES ('sync_enabled', '0')");
                    $stmt->execute();
                }
                
                // Create admin user
                $admin_data = $_SESSION['setup_admin'];
                $password_hash = password_hash($admin_data['password'], PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("
                    INSERT INTO users (username, password_hash, full_name, email, role, is_active, created_at) 
                    VALUES (:username, :password_hash, :full_name, :email, 'admin', 1, :created_at)
                ");
                
                $stmt->execute([
                    'username' => $admin_data['username'],
                    'password_hash' => $password_hash,
                    'full_name' => $admin_data['full_name'],
                    'email' => $admin_data['email'],
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Create initial log entry
                logActivity('system', 'setup_complete', 'Vessel logger setup completed', [
                    'vessel' => $vessel_data['name'],
                    'company' => $vessel_data['company_name'],
                    'admin_user' => $admin_data['username']
                ]);
                
                $db->commit();
                
                // Clear setup session data
                unset($_SESSION['setup_vessel']);
                unset($_SESSION['setup_admin']);
                unset($_SESSION['setup_server']);
                
                $success = true;
                
                // Redirect to login after 3 seconds
                header('Refresh: 3; url=/login.php');
                
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }
        
    } catch (Exception $e) {
        $errors[] = 'Setup error: ' . $e->getMessage();
        logError('Setup error: ' . $e->getMessage());
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$current_step = $_GET['step'] ?? 'vessel_info';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Logger Setup</title>
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
            padding: 20px;
        }
        
        .setup-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .setup-header {
            background: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .setup-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .setup-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .progress-bar {
            height: 4px;
            background: #34495e;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: #3498db;
            transition: width 0.3s ease;
        }
        
        .setup-content {
            padding: 40px;
        }
        
        .step-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #34495e;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="url"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group small {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
            display: block;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
        }
        
        .errors {
            background: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .errors ul {
            list-style: none;
        }
        
        .errors li {
            margin-bottom: 5px;
        }
        
        .success {
            background: #27ae60;
            color: white;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .success h3 {
            margin-bottom: 10px;
        }
        
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #bdc3c7;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
        }
        
        .step.active {
            background: #3498db;
        }
        
        .step.completed {
            background: #27ae60;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>Vessel Logger Setup</h1>
            <p>Configure your vessel logging system</p>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php 
                $progress = [
                    'vessel_info' => '25%',
                    'admin_user' => '50%', 
                    'server_config' => '75%',
                    'complete' => '100%'
                ];
                echo $progress[$current_step] ?? '25%';
            ?>"></div>
        </div>
        
        <div class="setup-content">
            <?php if (!empty($errors)): ?>
                <div class="errors">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success">
                    <h3>Setup Complete!</h3>
                    <p>Your vessel logger has been successfully configured.</p>
                    <p>You will be redirected to the login page in 3 seconds...</p>
                </div>
            <?php else: ?>
                
                <div class="step-indicator">
                    <div class="step <?php echo in_array($current_step, ['vessel_info', 'admin_user', 'server_config', 'complete']) ? 'completed' : ($current_step === 'vessel_info' ? 'active' : ''); ?>">1</div>
                    <div class="step <?php echo in_array($current_step, ['admin_user', 'server_config', 'complete']) ? 'completed' : ($current_step === 'admin_user' ? 'active' : ''); ?>">2</div>
                    <div class="step <?php echo in_array($current_step, ['server_config', 'complete']) ? 'completed' : ($current_step === 'server_config' ? 'active' : ''); ?>">3</div>
                    <div class="step <?php echo $current_step === 'complete' ? 'active' : ''; ?>">4</div>
                </div>
                
                <?php if ($current_step === 'vessel_info'): ?>
                    <h2 class="step-title">Step 1: Vessel Information</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="step" value="vessel_info">
                        
                        <div class="form-group">
                            <label for="vessel_name">Vessel Name *</label>
                            <input type="text" id="vessel_name" name="vessel_name" value="<?php echo htmlspecialchars($_POST['vessel_name'] ?? ''); ?>" required>
                            <small>Official name of the vessel</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="vessel_imo">IMO Number</label>
                            <input type="text" id="vessel_imo" name="vessel_imo" value="<?php echo htmlspecialchars($_POST['vessel_imo'] ?? ''); ?>" pattern="\d{7}" maxlength="7">
                            <small>7-digit IMO number (optional)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="company_name">Company Name *</label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" required>
                            <small>Name of the shipping company</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="company_code">Company Code *</label>
                            <input type="text" id="company_code" name="company_code" value="<?php echo htmlspecialchars($_POST['company_code'] ?? ''); ?>" required>
                            <small>Short company identifier (e.g., ABC, MAERSK)</small>
                        </div>
                        
                        <div class="button-group">
                            <div></div>
                            <button type="submit" class="btn btn-primary">Next →</button>
                        </div>
                    </form>
                    
                <?php elseif ($current_step === 'admin_user'): ?>
                    <h2 class="step-title">Step 2: Administrator Account</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="step" value="admin_user">
                        
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required minlength="3">
                            <small>Administrator username (minimum 3 characters)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required minlength="8">
                            <small>Minimum 8 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <small>Optional - for notifications and recovery</small>
                        </div>
                        
                        <div class="button-group">
                            <a href="/setup.php?step=vessel_info" class="btn btn-secondary">← Back</a>
                            <button type="submit" class="btn btn-primary">Next →</button>
                        </div>
                    </form>
                    
                <?php elseif ($current_step === 'server_config'): ?>
                    <h2 class="step-title">Step 3: Server Configuration</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="step" value="server_config">
                        
                        <div class="checkbox-group">
                            <input type="checkbox" id="sync_enabled" name="sync_enabled" <?php echo isset($_POST['sync_enabled']) ? 'checked' : ''; ?>>
                            <label for="sync_enabled">Enable automatic synchronization with main server</label>
                        </div>
                        
                        <div id="sync_options" style="<?php echo isset($_POST['sync_enabled']) ? '' : 'display: none;'; ?>">
                            <div class="form-group">
                                <label for="server_url">Server URL</label>
                                <input type="url" id="server_url" name="server_url" value="<?php echo htmlspecialchars($_POST['server_url'] ?? ''); ?>" placeholder="https://your-server.com/api">
                                <small>URL of the main server API endpoint</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="vessel_key">Vessel Authentication Key</label>
                                <input type="text" id="vessel_key" name="vessel_key" value="<?php echo htmlspecialchars($_POST['vessel_key'] ?? ''); ?>">
                                <small>Unique key provided by your company for this vessel</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="auto_sync_interval">Auto-sync Interval (minutes)</label>
                                <input type="number" id="auto_sync_interval" name="auto_sync_interval" value="<?php echo htmlspecialchars($_POST['auto_sync_interval'] ?? '60'); ?>" min="5" max="1440">
                                <small>How often to automatically sync when online (5-1440 minutes)</small>
                            </div>
                        </div>
                        
                        <div class="button-group">
                            <a href="/setup.php?step=admin_user" class="btn btn-secondary">← Back</a>
                            <button type="submit" class="btn btn-primary">Complete Setup</button>
                        </div>
                    </form>
                    
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Toggle sync options based on checkbox
        document.getElementById('sync_enabled')?.addEventListener('change', function() {
            const syncOptions = document.getElementById('sync_options');
            if (this.checked) {
                syncOptions.style.display = 'block';
                document.getElementById('server_url').required = true;
                document.getElementById('vessel_key').required = true;
            } else {
                syncOptions.style.display = 'none';
                document.getElementById('server_url').required = false;
                document.getElementById('vessel_key').required = false;
            }
        });
        
        // Password confirmation validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePassword() {
            if (password && confirmPassword) {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
        }
        
        password?.addEventListener('input', validatePassword);
        confirmPassword?.addEventListener('input', validatePassword);
    </script>
</body>
</html>
