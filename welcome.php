<?php
/**
 * Welcome Page
 * Shown to new users after successful registration OR existing logged-in users
 */

require_once __DIR__ . '/config_saas.php';
session_start();

// Check if this is a new signup (no session yet)
$is_new_signup = isset($_GET['new']) && $_GET['new'] == '1';

if ($is_new_signup) {
    // New signup - show welcome message and login instructions
    // Don't require session data yet
    $user_name = $_GET['user'] ?? 'New User';
    $company_name = $_GET['company'] ?? 'Your Company';
    $company_domain = $_GET['domain'] ?? '';
} else {
    // Existing user accessing welcome page - require login
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
        header('Location: ' . BASE_URL . '/login_enhanced.php');
        exit;
    }
    
    $user_name = $_SESSION['user_name'];
    $company_name = $_SESSION['company_name'];
    $company_domain = $_SESSION['company_domain'];
    $subscription_plan = $_SESSION['subscription_plan'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to LogicDock!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .welcome-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .welcome-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 1000px;
        }
        .welcome-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .welcome-body {
            padding: 40px;
        }
        .step-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        .step-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            float: left;
            margin-right: 20px;
        }
        .btn-action {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            color: white;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: all 0.3s;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .feature-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .feature-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        .trial-info {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="welcome-card">
            <div class="welcome-header">
                <i class="fas fa-party-horn fa-3x mb-3"></i>
                <h1>Welcome aboard, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <h3><?php echo htmlspecialchars($company_name); ?></h3>
                <p class="mb-0">Your vessel logging system is ready to go</p>
            </div>
            
            <div class="welcome-body">
                <?php if ($is_new_signup): ?>
                    <!-- New Signup Welcome -->
                    <div class="alert alert-success text-center mb-4">
                        <h4><i class="fas fa-check-circle me-2"></i>Account Created Successfully!</h4>
                        <p class="mb-0">Your LogicDock account has been set up and your database is ready.</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-8 mx-auto">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="card-title text-center mb-4">
                                        <i class="fas fa-key me-2 text-primary"></i>
                                        Ready to Log In
                                    </h5>
                                    
                                    <div class="text-center mb-4">
                                        <p class="mb-3">Your account is ready! You can now log in with:</p>
                                        <div class="bg-light p-3 rounded">
                                            <strong>Company:</strong> <?php echo htmlspecialchars($company_name); ?><br>
                                            <strong>Domain:</strong> <?php echo htmlspecialchars($company_domain); ?><br>
                                            <strong>Your login credentials</strong> (as provided during signup)
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="login_enhanced.php?company=<?php echo urlencode($company_domain); ?>" 
                                           class="btn btn-primary btn-lg">
                                            <i class="fas fa-sign-in-alt me-2"></i>
                                            Log In to Your Account
                                        </a>
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-home me-2"></i>
                                            Back to Home Page
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Existing logged-in user content -->
                <?php if (isset($subscription_plan) && $subscription_plan === 'trial'): ?>
                    <div class="trial-info">
                        <h5><i class="fas fa-clock me-2"></i>30-Day Free Trial Active</h5>
                        <p class="mb-0">Explore all features with no restrictions. Upgrade anytime to continue after your trial.</p>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <h4 class="mb-4">Get Started in 3 Easy Steps</h4>
                        
                        <div class="step-card">
                            <div class="step-number">1</div>
                            <h6>Add Your First Vessel</h6>
                            <p class="mb-3">Configure your vessel specifications, engine details, and operational parameters.</p>
                            <a href="<?php echo BASE_URL; ?>/manage_vessels.php?action=add" class="btn btn-action btn-sm">
                                <i class="fas fa-ship me-2"></i>Add Vessel
                            </a>
                        </div>
                        
                        <div class="step-card">
                            <div class="step-number">2</div>
                            <h6>Invite Your Crew</h6>
                            <p class="mb-3">Add crew members and assign appropriate access levels for collaborative logging.</p>
                            <a href="<?php echo BASE_URL; ?>/company_admin.php#users" class="btn btn-action btn-sm">
                                <i class="fas fa-user-plus me-2"></i>Add Users
                            </a>
                        </div>
                        
                        <div class="step-card">
                            <div class="step-number">3</div>
                            <h6>Start Logging</h6>
                            <p class="mb-3">Begin professional engine room logging and operational documentation.</p>
                            <a href="<?php echo BASE_URL; ?>/vessel/engineroom/dashboard.php" class="btn btn-action btn-sm">
                                <i class="fas fa-cogs me-2"></i>Engine Room
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h4 class="mb-4">Your Account Details</h4>
                        
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Company Information</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Company:</strong> <?php echo htmlspecialchars($company_name); ?></li>
                                    <li><strong>Domain:</strong> <?php echo htmlspecialchars($company_domain); ?>.vessellogger.com</li>
                                    <li><strong>Plan:</strong> <?php echo ucfirst($subscription_plan); ?> Plan</li>
                                    <li><strong>Admin:</strong> <?php echo htmlspecialchars($user_name); ?></li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-body">
                                <h6 class="card-title">Login Information</h6>
                                <p class="mb-2">Your team can log in at:</p>
                                <div class="alert alert-info">
                                    <strong>URL:</strong> <?php echo BASE_URL; ?>/login_enhanced.php<br>
                                    <strong>Company Domain:</strong> <?php echo htmlspecialchars($company_domain); ?>
                                </div>
                                <small class="text-muted">Bookmark this URL for easy access</small>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-5">

                <h4 class="text-center mb-4">What You Can Do Now</h4>
                
                <div class="feature-grid">
                    <div class="feature-item">
                        <i class="fas fa-cogs feature-icon"></i>
                        <h6>Engine Room Logging</h6>
                        <p>Track RPM, temperatures, pressures, fuel consumption, and maintenance schedules</p>
                        <a href="<?php echo BASE_URL; ?>/vessel/engineroom/dashboard.php" class="btn btn-action btn-sm">
                            Start Logging
                        </a>
                    </div>
                    
                    <div class="feature-item">
                        <i class="fas fa-ship feature-icon"></i>
                        <h6>Vessel Management</h6>
                        <p>Configure multiple vessels, specifications, and operational parameters</p>
                        <a href="<?php echo BASE_URL; ?>/manage_vessels.php" class="btn btn-action btn-sm">
                            Manage Vessels
                        </a>
                    </div>
                    
                    <div class="feature-item">
                        <i class="fas fa-users feature-icon"></i>
                        <h6>User Management</h6>
                        <p>Add crew members, assign roles, and manage access permissions</p>
                        <a href="<?php echo BASE_URL; ?>/company_admin.php" class="btn btn-action btn-sm">
                            Manage Users
                        </a>
                    </div>
                    
                    <div class="feature-item">
                        <i class="fas fa-chart-line feature-icon"></i>
                        <h6>Reports & Analytics</h6>
                        <p>Generate operational reports, performance analytics, and compliance documentation</p>
                        <a href="vessel/engineroom/reports.php" class="btn btn-action btn-sm">
                            View Reports
                        </a>
                    </div>
                    
                    <div class="feature-item">
                        <i class="fas fa-mobile-alt feature-icon"></i>
                        <h6>Offline Access</h6>
                        <p>Continue logging even without internet connection - data syncs automatically</p>
                        <a href="vessel/engineroom/dashboard.php?offline=true" class="btn btn-action btn-sm">
                            Try Offline
                        </a>
                    </div>
                    
                    <div class="feature-item">
                        <i class="fas fa-headset feature-icon"></i>
                        <h6>Support & Training</h6>
                        <p>Access help documentation, video tutorials, and professional support</p>
                        <a href="support_dashboard.php" class="btn btn-action btn-sm">
                            Get Help
                        </a>
                    </div>
                </div>

                <div class="text-center mt-5">
                    <h5>Ready to Start?</h5>
                    <p class="text-muted mb-4">Choose your next step to begin professional vessel logging</p>
                    
                    <div class="d-flex justify-content-center flex-wrap">
                        <a href="office/index.php" class="btn btn-primary btn-lg me-3 mb-2">
                            <i class="fas fa-building me-2"></i>Office Dashboard
                        </a>
                        <a href="vessel/engineroom/dashboard.php" class="btn btn-action btn-lg me-3 mb-2">
                            <i class="fas fa-cogs me-2"></i>Go to Engine Room
                        </a>
                        <a href="company_admin.php" class="btn btn-outline-primary btn-lg me-3 mb-2">
                            <i class="fas fa-user-cog me-2"></i>Company Admin
                        </a>
                        <a href="manage_vessels.php" class="btn btn-outline-success btn-lg mb-2">
                            <i class="fas fa-ship me-2"></i>Add First Vessel
                        </a>
                    </div>
                    
                    <div class="mt-4">
                        <small class="text-muted">
                            Need help getting started? <a href="support_dashboard.php">Contact Support</a> or 
                            <a href="mailto:support@vessellogger.com">Email Us</a>
                        </small>
                    </div>
                </div>

                <?php if ($subscription_plan === 'trial'): ?>
                    <div class="alert alert-warning mt-4">
                        <h6><i class="fas fa-star me-2"></i>Loving Vessel Logger?</h6>
                        <p class="mb-2">Upgrade to a paid plan anytime to ensure uninterrupted service and unlock additional features.</p>
                        <a href="company_admin.php#subscription" class="btn btn-warning btn-sm">
                            <i class="fas fa-arrow-up me-1"></i>View Plans
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php endif; // End of is_new_signup check ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate step cards on scroll
            const stepCards = document.querySelectorAll('.step-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            stepCards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });

            // Auto-dismiss trial info after 10 seconds
            const trialInfo = document.querySelector('.trial-info');
            if (trialInfo) {
                setTimeout(() => {
                    trialInfo.style.transition = 'opacity 0.5s ease';
                    trialInfo.style.opacity = '0.8';
                }, 10000);
            }
        });
    </script>
</body>
</html>
