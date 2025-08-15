<?php
require_once __DIR__ . '/config_installed.php';
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/payment_processor.php';

// Check if we have a valid session and payment confirmation
$session_id = $_GET['session_id'] ?? '';
$payment_status = $_GET['status'] ?? 'success';

// Initialize payment processor
$payment_config = [
    'stripe_secret_key' => 'sk_test_your_key_here',
    'paypal_client_id' => 'your_paypal_client_id',
    'paypal_client_secret' => 'your_paypal_secret',
    'environment' => 'sandbox'
];

$payment_processor = new PaymentProcessor($payment_config);

// In production, verify the payment with the payment provider
$payment_verified = true; // For demo purposes

if (!$payment_verified) {
    header('Location: subscription.php?error=payment_verification_failed');
    exit;
}

// Get user information
$user = get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - <?php echo htmlspecialchars(COMPANY_NAME); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .success-animation {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            background: #28a745;
            border-radius: 50%;
            position: relative;
            animation: successPulse 2s ease-in-out infinite;
        }
        .success-animation::after {
            content: "✓";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 48px;
            font-weight: bold;
        }
        @keyframes successPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .success-card {
            background: white;
            border-radius: 15px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center;">
    <div class="container" style="max-width: 800px;">
        <div class="success-card">
            <div class="success-animation"></div>
            
            <h1 style="color: #28a745; margin-bottom: 20px;">Payment Successful!</h1>
            <p style="font-size: 18px; color: #666; margin-bottom: 30px;">
                Your subscription has been activated successfully.
            </p>
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 30px 0;">
                <h3>What's Next?</h3>
                <div style="text-align: left; margin-top: 20px;">
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <span style="background: #28a745; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">1</span>
                        <span>Your account has been upgraded and all features are now available</span>
                    </div>
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <span style="background: #28a745; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">2</span>
                        <span>You'll receive a confirmation email with your receipt</span>
                    </div>
                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                        <span style="background: #28a745; color: white; border-radius: 50%; width: 25px; height: 25px; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold;">3</span>
                        <span>You can now add more vessels and users to your account</span>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                <a href="dashboard.php" style="background: #007cba; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    Go to Dashboard
                </a>
                <a href="subscription.php" style="background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;">
                    View Subscription
                </a>
            </div>
            
            <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">
                <p style="color: #999; font-size: 14px;">
                    Transaction ID: <?php echo htmlspecialchars($session_id ?: 'DEMO-' . uniqid()); ?><br>
                    Date: <?php echo date('F j, Y g:i A'); ?>
                </p>
            </div>
        </div>
        
        <!-- Additional Information -->
        <div style="background: rgba(255,255,255,0.9); border-radius: 10px; padding: 20px; margin-top: 20px; text-align: center;">
            <h4>Need Help?</h4>
            <p>If you have any questions about your subscription or need assistance, our support team is here to help.</p>
            <a href="mailto:support@vessellogger.com" style="color: #007cba; text-decoration: none; font-weight: bold;">
                Contact Support
            </a>
        </div>
    </div>

    <script>
        // Redirect to dashboard after 10 seconds
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, 10000);
        
        // Show countdown
        let countdown = 10;
        const countdownElement = document.createElement('div');
        countdownElement.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: rgba(0,0,0,0.8); color: white; padding: 10px 15px; border-radius: 5px; font-size: 14px;';
        document.body.appendChild(countdownElement);
        
        const updateCountdown = () => {
            countdownElement.textContent = `Redirecting to dashboard in ${countdown}s`;
            countdown--;
            if (countdown < 0) {
                countdownElement.remove();
            }
        };
        
        updateCountdown();
        const countdownInterval = setInterval(updateCountdown, 1000);
        
        // Clear countdown if user navigates away
        window.addEventListener('beforeunload', () => {
            clearInterval(countdownInterval);
        });
    </script>
</body>
</html>
