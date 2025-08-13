<?php
require_once __DIR__ . '/config_installed.php';
require_once __DIR__ . '/license_manager.php';
require_once __DIR__ . '/auth_functions.php';

require_login();

$license = new LicenseManager($conn, CUSTOMER_ID);
$status = $license->getLicenseStatus();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Expired - Vessel Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div style="text-align: center; padding: 50px 20px;">
            <h1>⚠️ Subscription Required</h1>
            
            <?php if ($status['subscription_status'] === 'trial'): ?>
                <div style="background: #ff9800; color: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                    <h2>Your Free Trial Has Expired</h2>
                    <p>Your 30-day free trial ended on <?php echo date('F j, Y', strtotime($status['trial_end_date'])); ?></p>
                </div>
            <?php else: ?>
                <div style="background: #f44336; color: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                    <h2>Subscription Suspended</h2>
                    <p>Your subscription is currently suspended. Please contact billing to reactivate.</p>
                </div>
            <?php endif; ?>

            <div style="background: #f9f9f9; padding: 30px; border-radius: 10px; margin: 30px 0;">
                <h3>Account Information</h3>
                <p><strong>Company:</strong> <?php echo htmlspecialchars(COMPANY_NAME); ?></p>
                <p><strong>Customer ID:</strong> <?php echo htmlspecialchars(CUSTOMER_ID); ?></p>
                <p><strong>Vessels:</strong> <?php echo $status['current_vessels']; ?> / <?php echo $status['vessel_limit']; ?></p>
            </div>

            <div style="background: #e3f2fd; padding: 30px; border-radius: 10px; margin: 30px 0;">
                <h3>💳 Subscription Options</h3>
                
                <div style="display: flex; gap: 20px; justify-content: center; margin: 20px 0;">
                    <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #007cba; flex: 1; max-width: 300px;">
                        <h4>Basic Plan</h4>
                        <div style="font-size: 24px; color: #007cba; margin: 10px 0;"><strong>$99/month</strong></div>
                        <ul style="text-align: left; margin: 15px 0;">
                            <li>1 Vessel included</li>
                            <li>Engine Room (Basic)</li>
                            <li>Wheelhouse (Basic)</li>
                            <li>Data sync & backup</li>
                            <li>Email support</li>
                        </ul>
                        <button style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                            Subscribe Now
                        </button>
                    </div>
                </div>

                <p style="margin-top: 20px;">
                    <strong>Need more vessels or additional modules?</strong><br>
                    Contact us for custom pricing: <a href="mailto:billing@yourvesselmanagement.com">billing@yourvesselmanagement.com</a>
                </p>
            </div>

            <div style="margin-top: 30px;">
                <a href="mailto:support@yourvesselmanagement.com?subject=Customer%20ID:%20<?php echo CUSTOMER_ID; ?>" 
                   style="background: #666; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">
                    Contact Support
                </a>
                <a href="logout.php" 
                   style="background: #ccc; color: #333; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                    Logout
                </a>
            </div>
        </div>
    </div>
</body>
</html>
