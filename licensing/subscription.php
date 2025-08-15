<?php
require_once __DIR__ . '/config_installed.php';
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/license_manager.php';

require_login();

// Check if user has admin/owner access
$user = get_logged_in_user();
if (!in_array($user['role'], ['admin', 'owner'])) {
    header('Location: dashboard.php');
    exit;
}

$license = new LicenseManager($conn, CUSTOMER_ID);
$license_status = $license->getLicenseStatus();

// Handle plan changes
$message = '';
$message_type = '';

if ($_POST['action'] ?? '' === 'change_plan') {
    $new_plan = $_POST['plan'] ?? '';
    $valid_plans = ['trial', 'basic', 'professional', 'enterprise'];
    
    if (in_array($new_plan, $valid_plans)) {
        // In a real implementation, this would integrate with payment processing
        $message = "Plan change request submitted. You will be redirected to payment processing.";
        $message_type = 'info';
        
        // For demo purposes, we'll just show a message
        // In production, redirect to payment processor:
        // header('Location: https://checkout.stripe.com/...');
    } else {
        $message = "Invalid plan selected.";
        $message_type = 'error';
    }
}

if ($_POST['action'] ?? '' === 'toggle_module') {
    $module = $_POST['module'] ?? '';
    $enable = ($_POST['enable'] ?? '') === '1';
    
    // This would also integrate with payment processing
    $action_text = $enable ? 'enabled' : 'disabled';
    $message = "Module '{$module}' {$action_text}. Changes will take effect after payment confirmation.";
    $message_type = 'info';
}

// Plan configurations
$plans = [
    'trial' => [
        'name' => 'Free Trial',
        'price' => 0,
        'vessels' => 1,
        'users' => 3,
        'storage' => '1GB',
        'support' => 'Community',
        'features' => ['Basic logging', 'Simple reports', '30-day trial']
    ],
    'basic' => [
        'name' => 'Basic Plan',
        'price' => 49,
        'vessels' => 3,
        'users' => 10,
        'storage' => '10GB',
        'support' => 'Email',
        'features' => ['Engine logging', 'Basic reports', 'Data export', 'Mobile access']
    ],
    'professional' => [
        'name' => 'Professional',
        'price' => 149,
        'vessels' => 10,
        'users' => 50,
        'storage' => '100GB',
        'support' => 'Priority',
        'features' => ['All Basic features', 'Advanced analytics', 'Maintenance tracking', 'API access', 'Custom reports']
    ],
    'enterprise' => [
        'name' => 'Enterprise',
        'price' => 499,
        'vessels' => 'Unlimited',
        'users' => 'Unlimited',
        'storage' => '1TB',
        'support' => '24/7 Phone',
        'features' => ['All Professional features', 'White labeling', 'Custom integrations', 'Dedicated support', 'On-premise option']
    ]
];

// Available modules
$modules = [
    'maintenance' => [
        'name' => 'Maintenance Management',
        'description' => 'Schedule and track vessel maintenance',
        'price' => 29
    ],
    'analytics' => [
        'name' => 'Advanced Analytics',
        'description' => 'Detailed performance insights and predictions',
        'price' => 39
    ],
    'api' => [
        'name' => 'API Access',
        'description' => 'Integrate with third-party systems',
        'price' => 19
    ],
    'mobile' => [
        'name' => 'Mobile App',
        'description' => 'Native iOS and Android applications',
        'price' => 25
    ],
    'compliance' => [
        'name' => 'Compliance Reports',
        'description' => 'Automated regulatory compliance reporting',
        'price' => 49
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Management - <?php echo htmlspecialchars(COMPANY_NAME); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .plan-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            transition: transform 0.2s;
        }
        .plan-card:hover {
            transform: translateY(-5px);
        }
        .plan-card.current {
            border: 3px solid #28a745;
            background: linear-gradient(135deg, #f8fff8 0%, #e8f5e8 100%);
        }
        .plan-card.current::before {
            content: "Current Plan";
            position: absolute;
            top: -10px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .plan-price {
            font-size: 48px;
            font-weight: bold;
            color: #2c3e50;
            margin: 20px 0;
        }
        .plan-price small {
            font-size: 16px;
            color: #666;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 20px 0;
        }
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .feature-list li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        .module-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .module-card.active {
            border-left: 5px solid #28a745;
            background: #f8fff8;
        }
        .btn {
            background: #007cba;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: bold;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #005a87;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <?php show_license_banner(); ?>
    
    <div class="container">
        <header style="background: #6c757d; color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>💳 Subscription Management</h1>
                    <p><?php echo htmlspecialchars(COMPANY_NAME); ?> - Billing & Features</p>
                </div>
                <div style="text-align: right;">
                    <a href="dashboard.php" style="color: white; text-decoration: underline;">← Back to Dashboard</a>
                </div>
            </div>
        </header>

        <?php if ($message): ?>
            <div style="background: <?php echo $message_type === 'error' ? '#f8d7da' : '#d1ecf1'; ?>; 
                        color: <?php echo $message_type === 'error' ? '#721c24' : '#0c5460'; ?>; 
                        padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Current Subscription Info -->
        <div style="background: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <h2>Current Subscription</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div>
                    <h4>Plan</h4>
                    <p style="font-size: 20px; font-weight: bold; color: #28a745;">
                        <?php echo ucfirst($license_status['plan']); ?>
                    </p>
                </div>
                <div>
                    <h4>Status</h4>
                    <p style="font-size: 20px; font-weight: bold; color: <?php echo $license_status['status'] === 'active' ? '#28a745' : '#dc3545'; ?>;">
                        <?php echo ucfirst($license_status['status']); ?>
                    </p>
                </div>
                <div>
                    <h4>Expires</h4>
                    <p style="font-size: 20px; font-weight: bold;">
                        <?php echo date('M j, Y', strtotime($license_status['expires_at'])); ?>
                    </p>
                </div>
                <div>
                    <h4>Vessels Used</h4>
                    <p style="font-size: 20px; font-weight: bold;">
                        <?php 
                        $vessel_count = count($license_status['vessels'] ?? []);
                        echo $vessel_count . '/' . $license_status['vessel_limit']; 
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Available Plans -->
        <div style="margin-bottom: 40px;">
            <h2>Available Plans</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php foreach ($plans as $plan_id => $plan): ?>
                    <div class="plan-card <?php echo $plan_id === $license_status['plan'] ? 'current' : ''; ?>">
                        <h3><?php echo $plan['name']; ?></h3>
                        <div class="plan-price">
                            $<?php echo number_format($plan['price']); ?>
                            <small>/month</small>
                        </div>
                        
                        <ul class="feature-list">
                            <li><strong><?php echo $plan['vessels']; ?></strong> vessels</li>
                            <li><strong><?php echo $plan['users']; ?></strong> users</li>
                            <li><strong><?php echo $plan['storage']; ?></strong> storage</li>
                            <li><strong><?php echo $plan['support']; ?></strong> support</li>
                        </ul>
                        
                        <div style="margin: 20px 0;">
                            <?php foreach ($plan['features'] as $feature): ?>
                                <div style="color: #666; margin: 5px 0;">• <?php echo $feature; ?></div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($plan_id === $license_status['plan']): ?>
                            <button class="btn btn-secondary" style="width: 100%;" disabled>Current Plan</button>
                        <?php else: ?>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="change_plan">
                                <input type="hidden" name="plan" value="<?php echo $plan_id; ?>">
                                <button type="submit" class="btn <?php echo $plan['price'] > $plans[$license_status['plan']]['price'] ? 'btn-success' : ''; ?>" 
                                        style="width: 100%;">
                                    <?php echo $plan['price'] > $plans[$license_status['plan']]['price'] ? 'Upgrade' : 'Downgrade'; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Add-on Modules -->
        <div>
            <h2>Add-on Modules</h2>
            <p style="color: #666; margin-bottom: 20px;">
                Enhance your vessel management platform with additional modules
            </p>
            
            <?php foreach ($modules as $module_id => $module): ?>
                <?php $is_active = in_array($module_id, $license_status['active_modules']); ?>
                <div class="module-card <?php echo $is_active ? 'active' : ''; ?>">
                    <div>
                        <h4><?php echo $module['name']; ?></h4>
                        <p style="color: #666; margin: 5px 0;"><?php echo $module['description']; ?></p>
                        <div style="font-weight: bold; color: #007cba;">
                            $<?php echo $module['price']; ?>/month
                        </div>
                    </div>
                    <div>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="action" value="toggle_module">
                            <input type="hidden" name="module" value="<?php echo $module_id; ?>">
                            <input type="hidden" name="enable" value="<?php echo $is_active ? '0' : '1'; ?>">
                            <button type="submit" class="btn <?php echo $is_active ? 'btn-secondary' : 'btn-success'; ?>">
                                <?php echo $is_active ? 'Disable' : 'Enable'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Billing Information -->
        <div style="background: white; padding: 30px; border-radius: 15px; margin-top: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <h2>Billing Information</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 20px;">
                <div>
                    <h4>Payment Method</h4>
                    <p style="color: #666;">•••• •••• •••• 1234 (Visa)</p>
                    <p style="color: #666; font-size: 14px;">Expires 12/2025</p>
                    <a href="#" class="btn" style="margin-top: 10px;">Update Payment Method</a>
                </div>
                <div>
                    <h4>Billing Address</h4>
                    <p style="color: #666;">
                        123 Harbor Street<br>
                        Marina Bay, CA 90210<br>
                        United States
                    </p>
                    <a href="#" class="btn" style="margin-top: 10px;">Update Address</a>
                </div>
            </div>
        </div>

        <!-- Support Information -->
        <div style="background: #f8f9fa; padding: 30px; border-radius: 15px; margin-top: 30px;">
            <h3>Need Help?</h3>
            <p>Our support team is here to help you choose the right plan and modules for your fleet.</p>
            <div style="margin-top: 20px;">
                <a href="mailto:support@vessellogger.com" class="btn" style="margin-right: 10px;">Contact Support</a>
                <a href="#" class="btn btn-secondary">View Documentation</a>
            </div>
        </div>
    </div>

    <script>
        // Confirmation for plan changes
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = this.querySelector('input[name="action"]').value;
                if (action === 'change_plan') {
                    const plan = this.querySelector('input[name="plan"]').value;
                    if (!confirm(`Are you sure you want to change to the ${plan} plan? You will be redirected to payment processing.`)) {
                        e.preventDefault();
                    }
                } else if (action === 'toggle_module') {
                    const module = this.querySelector('input[name="module"]').value;
                    const enable = this.querySelector('input[name="enable"]').value === '1';
                    const action_text = enable ? 'enable' : 'disable';
                    if (!confirm(`Are you sure you want to ${action_text} the ${module} module?`)) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>
