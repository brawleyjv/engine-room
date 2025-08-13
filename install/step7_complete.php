<?php
// Step 7: Complete Installation
if (!isset($_SESSION['install_data'])) {
    header('Location: ?step=1');
    exit;
}

$install_data = $_SESSION['install_data'];

// Generate the configuration file
$config_content = "<?php
/**
 * Vessel Management System Configuration
 * Generated on: " . date('Y-m-d H:i:s') . "
 * Customer ID: {$install_data['customer_id']}
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', '{$install_data['db_name']}');
define('DB_USER', '{$install_data['db_user']}');
define('DB_PASS', '{$install_data['db_pass']}');
define('DB_CHARSET', 'utf8mb4');

// Customer Information
define('CUSTOMER_ID', '{$install_data['customer_id']}');
define('COMPANY_NAME', '{$install_data['company_name']}');
define('COMPANY_TYPE', '{$install_data['company_type']}');

// License Information
define('TRIAL_END_DATE', '{$install_data['trial_end']}');
define('VESSEL_LIMIT', 1);
define('SUBSCRIPTION_STATUS', 'trial');

// Security
define('AUTH_SALT', '" . bin2hex(random_bytes(32)) . "');
define('SECURE_AUTH_SALT', '" . bin2hex(random_bytes(32)) . "');
define('LOGGED_IN_SALT', '" . bin2hex(random_bytes(32)) . "');

// API Configuration for sync
define('SYNC_API_ENDPOINT', 'https://your-server.com/api/sync');
define('SYNC_API_KEY', '" . bin2hex(random_bytes(16)) . "');

// Module Configuration
\$enabled_modules = [";

// Add selected modules
$selected_modules = $install_data['selected_modules'] ?? ['engine_basic', 'wheelhouse_basic'];
foreach ($selected_modules as $module) {
    $config_content .= "
    '$module' => true,";
}

$config_content .= "
];

// Database Connection
try {
    \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    \$conn->set_charset(DB_CHARSET);
    
    if (\$conn->connect_error) {
        die('Database connection failed: ' . \$conn->connect_error);
    }
} catch (Exception \$e) {
    die('Database connection error: ' . \$e->getMessage());
}

// Installation completed
define('VMS_INSTALLED', true);
define('VMS_VERSION', '1.0.0');
?>";

// Write configuration file
$config_written = file_put_contents('../config_installed.php', $config_content);

if (!$config_written) {
    $error = "Could not write configuration file. Please check permissions.";
}
?>

<h2>🎉 Installation Complete!</h2>

<?php if (isset($error)): ?>
    <div class="error"><?php echo $error; ?></div>
<?php else: ?>
    <div class="success">
        <h3>Welcome to Vessel Management System!</h3>
        <p>Your system has been successfully installed and configured.</p>
    </div>

    <div style="background: #e3f2fd; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>📋 Installation Summary</h3>
        <ul>
            <li><strong>Company:</strong> <?php echo htmlspecialchars($install_data['company_name']); ?></li>
            <li><strong>Customer ID:</strong> <?php echo htmlspecialchars($install_data['customer_id']); ?></li>
            <li><strong>Database:</strong> <?php echo htmlspecialchars($install_data['db_name']); ?></li>
            <li><strong>Admin User:</strong> <?php echo htmlspecialchars($install_data['admin_email']); ?></li>
            <li><strong>Primary Vessel:</strong> <?php echo htmlspecialchars($install_data['vessel_name']); ?></li>
            <li><strong>Trial Period:</strong> 30 days (until <?php echo date('M j, Y', strtotime($install_data['trial_end'])); ?>)</li>
        </ul>
    </div>

    <div style="background: #fff3e0; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>🚀 What's Next?</h3>
        <ol>
            <li><strong>Start logging:</strong> Begin entering engine room and wheelhouse data</li>
            <li><strong>Invite crew:</strong> Add additional users with appropriate permissions</li>
            <li><strong>Explore features:</strong> Familiarize yourself with dashboards and reports</li>
            <li><strong>Trial period:</strong> You have 30 days to evaluate all features</li>
            <li><strong>Subscription:</strong> Choose your plan before trial expires</li>
        </ol>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="../welcome.php" 
               style="display: inline-block; background: #2ecc71; color: white; padding: 15px 30px; 
                      text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; 
                      box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3); transition: all 0.2s;"
               onmouseover="this.style.background='#27ae60'; this.style.transform='translateY(-2px)'"
               onmouseout="this.style.background='#2ecc71'; this.style.transform='translateY(0)'">
                🎉 Get Started with Your System
            </a>
        </div>
        
        <div style="text-align: center; margin-top: 15px;">
            <a href="../login.php" 
               style="display: inline-block; background: #3498db; color: white; padding: 10px 20px; 
                      text-decoration: none; border-radius: 5px; font-weight: bold; margin-right: 10px;">
                🔑 Login Now
            </a>
            <a href="../vessel/engineroom/dashboard.php" 
               style="display: inline-block; background: #9b59b6; color: white; padding: 10px 20px; 
                      text-decoration: none; border-radius: 5px; font-weight: bold;">
                ⚙️ Go to Vessel Dashboard
            </a>
        </div>
    </div>

    <div style="background: #f3e5f5; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <h3>💡 Support Information</h3>
        <p><strong>Customer ID:</strong> <?php echo htmlspecialchars($install_data['customer_id']); ?> 
           <span style="font-size: 12px; color: #666;">(Keep this for support requests)</span></p>
        <p><strong>Database Name:</strong> <?php echo htmlspecialchars($install_data['db_name']); ?></p>
        <p><strong>Support Email:</strong> support@logicdock.org</p>
        <p><strong>Documentation:</strong> <a href="/docs" target="_blank">Online User Guide</a></p>
        
        <div style="background: #fff3e0; padding: 15px; border-radius: 5px; margin-top: 15px;">
            <h4>🔧 For LogicDock Support Team:</h4>
            <p style="font-family: monospace; font-size: 14px;">
                <strong>Support Username:</strong> <?php echo htmlspecialchars($install_data['support_username'] ?? 'logicdock_support'); ?><br>
                <strong>Support Password:</strong> <?php echo htmlspecialchars($install_data['support_password'] ?? 'Not generated'); ?><br>
                <strong>Database Access:</strong> <?php echo htmlspecialchars($install_data['db_user']); ?> / [Generated password]
            </p>
            <p style="font-size: 12px; color: #666;">
                This information has been securely logged for technical support purposes.
            </p>
        </div>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="../index.php" class="btn" style="font-size: 18px; padding: 15px 30px;">
            🚢 Launch Vessel Management System
        </a>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <p style="color: #666; font-size: 14px;">
            Thank you for choosing Vessel Management System!<br>
            <a href="mailto:support@yourvesselmanagement.com">Contact Support</a> if you need assistance.
        </p>
    </div>
<?php endif; ?>

<script>
// Clear installation session data
<?php if (!isset($error)): ?>
    // Auto-cleanup installation data after successful completion
    setTimeout(function() {
        fetch('cleanup.php', {method: 'POST'});
    }, 5000);
<?php endif; ?>
</script>
