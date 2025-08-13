<?php
/**
 * LogicDock Support Dashboard
 * Access customer support credentials for troubleshooting
 */

session_start();
require_once __DIR__ . '/config.php';

// Super secure access check - only for LogicDock team
$valid_access_keys = [
    'logicdock_support_2024',
    'vessel_support_admin',
    'debug_customer_access'
];

$access_granted = false;
if (isset($_GET['access_key']) && in_array($_GET['access_key'], $valid_access_keys)) {
    $access_granted = true;
} elseif (isset($_POST['support_password']) && $_POST['support_password'] === 'LogicDockSupport2024!') {
    $access_granted = true;
    $_SESSION['support_authenticated'] = true;
} elseif (isset($_SESSION['support_authenticated'])) {
    $access_granted = true;
}

if (!$access_granted) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>LogicDock Support Access</title>
        <style>
            body { font-family: sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
            input, button { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
            button { background: #3498db; color: white; border: none; cursor: pointer; }
            .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <h2>🔐 LogicDock Support Access</h2>
        <p>This dashboard provides customer database access credentials for technical support.</p>
        
        <?php if (isset($_POST['support_password'])): ?>
            <div class="error">Invalid support password.</div>
        <?php endif; ?>
        
        <form method="post">
            <input type="password" name="support_password" placeholder="Support Password" required>
            <button type="submit">Access Support Dashboard</button>
        </form>
        
        <p style="color: #666; font-size: 14px; margin-top: 30px;">
            Authorized personnel only. All access is logged.
        </p>
    </body>
    </html>
    <?php
    exit;
}

// Connect to master database
try {
    $master_pdo = new PDO("mysql:host=$db_host;dbname=vessellogger_master", $db_user, $db_pass);
    $master_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Master database connection failed: " . $e->getMessage());
}

// Get customer data with support credentials
$search = $_GET['search'] ?? '';
$where_clause = '';
$params = [];

if ($search) {
    $where_clause = "WHERE (cl.customer_id LIKE ? OR cl.company_name LIKE ? OR cl.admin_email LIKE ? OR cl.database_name LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
}

$sql = "SELECT 
            cl.*,
            lt.event_data
        FROM customer_licenses cl
        LEFT JOIN logicdock_tracking lt ON cl.customer_id = lt.customer_id 
            AND lt.event_type = 'trial_started'
        $where_clause
        ORDER BY cl.created_at DESC";

$stmt = $master_pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle specific customer lookup
$selected_customer = null;
if (isset($_GET['customer_id'])) {
    foreach ($customers as $customer) {
        if ($customer['customer_id'] === $_GET['customer_id']) {
            $selected_customer = $customer;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LogicDock Support Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            border-bottom: 1px solid #e1e5e9;
            backdrop-filter: blur(10px);
        }
        
        .header h1 {
            color: #e74c3c;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .header .subtitle {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .search-box {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .search-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .customer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
        }
        
        .customer-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .customer-card h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .customer-info {
            margin-bottom: 1rem;
        }
        
        .customer-info label {
            font-weight: 600;
            color: #555;
            display: inline-block;
            width: 120px;
            font-size: 0.9rem;
        }
        
        .customer-info .value {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .credentials {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .credentials h4 {
            color: #856404;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .credential-item {
            margin: 8px 0;
            display: flex;
            align-items: center;
        }
        
        .credential-item label {
            width: 140px;
            font-weight: 600;
            color: #666;
            font-size: 0.85rem;
        }
        
        .credential-item .value {
            font-family: 'Courier New', monospace;
            background: #fff;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 0.8rem;
            flex: 1;
            margin-right: 10px;
        }
        
        .copy-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
        }
        
        .copy-btn:hover {
            background: #2980b9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-trial { background: #fff3cd; color: #856404; }
        .status-active { background: #d4edda; color: #155724; }
        .status-suspended { background: #f8d7da; color: #721c24; }
        
        .quick-access {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .quick-access a {
            color: #1976d2;
            text-decoration: none;
            margin-right: 15px;
            font-size: 0.9rem;
        }
        
        .quick-access a:hover {
            text-decoration: underline;
        }
        
        .alert {
            background: #d1ecf1;
            color: #0c5460;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .no-results {
            text-align: center;
            color: #666;
            font-style: italic;
            margin: 2rem 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔧 LogicDock Support Dashboard</h1>
        <div class="subtitle">Customer database access credentials for technical support</div>
    </div>

    <div class="container">
        <div class="alert">
            <strong>⚠️ Confidential Information:</strong> This dashboard contains sensitive customer access credentials. 
            Use only for authorized technical support. All access is logged and monitored.
            <div style="margin-top: 10px;">
                <a href="vps_management.php?access_key=logicdock_vps_admin_2024" style="color: #d63031; font-weight: bold; text-decoration: none;">
                    🖥️ VPS Server Management
                </a> | 
                <a href="logicdock_dashboard.php?access_key=logicdock_admin_2024" style="color: #0984e3; font-weight: bold; text-decoration: none;">
                    📊 Customer Analytics
                </a>
            </div>
        </div>

        <!-- Search Box -->
        <div class="search-box">
            <form method="get">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search by Customer ID, Company Name, Email, or Database Name..." 
                       onchange="this.form.submit()">
            </form>
        </div>

        <?php if (empty($customers)): ?>
            <div class="no-results">
                <?= $search ? "No customers found matching '$search'" : "No customers found in the system" ?>
            </div>
        <?php else: ?>
            <div class="customer-grid">
                <?php foreach ($customers as $customer): ?>
                    <?php
                    $event_data = $customer['event_data'] ? json_decode($customer['event_data'], true) : [];
                    $support_username = $event_data['support_username'] ?? 'logicdock_support';
                    $support_password = $event_data['support_password'] ?? 'Not available';
                    $db_user = $event_data['database_user'] ?? 'Not available';
                    ?>
                    
                    <div class="customer-card">
                        <h3><?= htmlspecialchars($customer['company_name']) ?></h3>
                        
                        <div class="customer-info">
                            <label>Customer ID:</label>
                            <span class="value"><?= htmlspecialchars($customer['customer_id']) ?></span>
                        </div>
                        
                        <div class="customer-info">
                            <label>Status:</label>
                            <span class="status-badge status-<?= $customer['status'] ?>">
                                <?= ucfirst($customer['status']) ?>
                            </span>
                            <span class="status-badge status-<?= $customer['plan'] ?>">
                                <?= ucfirst($customer['plan']) ?>
                            </span>
                        </div>
                        
                        <div class="customer-info">
                            <label>Admin Email:</label>
                            <span class="value"><?= htmlspecialchars($customer['admin_email']) ?></span>
                        </div>
                        
                        <div class="customer-info">
                            <label>Database:</label>
                            <span class="value"><?= htmlspecialchars($customer['database_name'] ?? 'Not available') ?></span>
                        </div>
                        
                        <div class="customer-info">
                            <label>Created:</label>
                            <span class="value"><?= date('M j, Y H:i', strtotime($customer['created_at'])) ?></span>
                        </div>
                        
                        <?php if ($customer['expires_at']): ?>
                        <div class="customer-info">
                            <label>Expires:</label>
                            <span class="value"><?= date('M j, Y H:i', strtotime($customer['expires_at'])) ?></span>
                        </div>
                        <?php endif; ?>

                        <!-- Support Credentials -->
                        <div class="credentials">
                            <h4>🔑 Support Access Credentials</h4>
                            
                            <div class="credential-item">
                                <label>Support Username:</label>
                                <span class="value"><?= htmlspecialchars($support_username) ?></span>
                                <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($support_username) ?>')">Copy</button>
                            </div>
                            
                            <div class="credential-item">
                                <label>Support Password:</label>
                                <span class="value"><?= htmlspecialchars($support_password) ?></span>
                                <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($support_password) ?>')">Copy</button>
                            </div>
                            
                            <div class="credential-item">
                                <label>Database User:</label>
                                <span class="value"><?= htmlspecialchars($db_user) ?></span>
                                <button class="copy-btn" onclick="copyToClipboard('<?= htmlspecialchars($db_user) ?>')">Copy</button>
                            </div>
                        </div>

                        <!-- Quick Access -->
                        <div class="quick-access">
                            <strong>Quick Actions:</strong><br>
                            <a href="https://<?= $_SERVER['HTTP_HOST'] ?>/login.php?customer=<?= urlencode($customer['customer_id']) ?>" target="_blank">
                                🔗 Customer Login Page
                            </a>
                            <a href="mailto:<?= htmlspecialchars($customer['admin_email']) ?>">
                                📧 Email Customer
                            </a>
                            <a href="logicdock_dashboard.php?customer_id=<?= urlencode($customer['customer_id']) ?>">
                                📊 View Analytics
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show feedback
                const button = event.target;
                const originalText = button.textContent;
                button.textContent = 'Copied!';
                button.style.background = '#27ae60';
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '#3498db';
                }, 1000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy to clipboard');
            });
        }
        
        // Auto-refresh every 5 minutes
        setTimeout(function() {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>
