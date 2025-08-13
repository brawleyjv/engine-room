<?php
/**
 * Company Onboarding System
 * Multi-step registration process for new companies
 * Handles trial signup, database creation, and initial setup
 */

session_start();
require_once __DIR__ . '/config_saas.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            $result = handleStep1($_POST);
            if ($result['success']) {
                $_SESSION['onboarding_data'] = $result['data'];
                header('Location: signup.php?step=2');
                exit;
            } else {
                $errors = $result['errors'];
            }
            break;
            
        case 2:
            $result = handleStep2($_POST);
            if ($result['success']) {
                $_SESSION['onboarding_data'] = array_merge($_SESSION['onboarding_data'], $result['data']);
                header('Location: signup.php?step=3');
                exit;
            } else {
                $errors = $result['errors'];
            }
            break;
            
        case 3:
            $result = handleStep3($_POST);
            if ($result['success']) {
                $_SESSION['onboarding_data'] = array_merge($_SESSION['onboarding_data'], $result['data']);
                header('Location: signup.php?step=4');
                exit;
            } else {
                $errors = $result['errors'];
            }
            break;
            
        case 4:
            $result = completeRegistration($_SESSION['onboarding_data']);
            if ($result['success']) {
                // Clear onboarding data
                unset($_SESSION['onboarding_data']);
                
                // Set login session
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['company_id'] = $result['company_id'];
                $_SESSION['user_name'] = $result['user_name'];
                $_SESSION['user_email'] = $result['user_email'];
                $_SESSION['user_role'] = 'owner';
                $_SESSION['company_name'] = $result['company_name'];
                $_SESSION['company_domain'] = $result['company_domain'];
                $_SESSION['subscription_plan'] = 'trial';
                $_SESSION['subscription_status'] = 'active';
                
                header('Location: welcome.php');
                exit;
            } else {
                $errors = $result['errors'];
            }
            break;
    }
}

// Redirect to step 1 if no onboarding data exists (except for step 1)
if ($step > 1 && !isset($_SESSION['onboarding_data'])) {
    header('Location: signup.php?step=1');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Your Free Trial - Vessel Logger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .onboarding-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .onboarding-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
        }
        .step-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .step-body {
            padding: 40px;
        }
        .progress-bar-custom {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step-item {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .step-item::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 60%;
            right: -40%;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        .step-item:last-child::after {
            display: none;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            position: relative;
            z-index: 2;
        }
        .step-item.active .step-number {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .step-item.completed .step-number {
            background: #28a745;
            color: white;
        }
        .plan-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .plan-card:hover, .plan-card.selected {
            border-color: #667eea;
            background: #f8f9fa;
            transform: translateY(-5px);
        }
        .plan-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea10, #764ba210);
        }
        .feature-check {
            color: #28a745;
            margin-right: 8px;
        }
        .btn-next {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 500;
        }
        .btn-next:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="onboarding-container">
        <div class="onboarding-card">
            <div class="step-header">
                <h2><i class="fas fa-ship me-2"></i>Start Your Free Trial</h2>
                <p class="mb-0">Set up your vessel logging system in 4 easy steps</p>
            </div>
            
            <div class="step-body">
                <!-- Progress Indicator -->
                <div class="step-indicator">
                    <div class="step-item <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'active') : ''; ?>">
                        <div class="step-number">
                            <?php echo $step > 1 ? '<i class="fas fa-check"></i>' : '1'; ?>
                        </div>
                        <small>Company Info</small>
                    </div>
                    <div class="step-item <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'active') : ''; ?>">
                        <div class="step-number">
                            <?php echo $step > 2 ? '<i class="fas fa-check"></i>' : '2'; ?>
                        </div>
                        <small>Admin Account</small>
                    </div>
                    <div class="step-item <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'active') : ''; ?>">
                        <div class="step-number">
                            <?php echo $step > 3 ? '<i class="fas fa-check"></i>' : '3'; ?>
                        </div>
                        <small>Choose Plan</small>
                    </div>
                    <div class="step-item <?php echo $step >= 4 ? 'active' : ''; ?>">
                        <div class="step-number">4</div>
                        <small>Setup Complete</small>
                    </div>
                </div>

                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: <?php echo ($step / 4) * 100; ?>%"></div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Step 1: Company Information -->
                <h4 class="mb-4">Company Information</h4>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Company Name *</label>
                                <input type="text" class="form-control" name="company_name" 
                                       value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Company Domain *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="company_domain" 
                                           value="<?php echo htmlspecialchars($_POST['company_domain'] ?? ''); ?>" 
                                           placeholder="acme-marine" required pattern="[a-z0-9-]+">
                                    <span class="input-group-text">.vessellogger.com</span>
                                </div>
                                <small class="form-text text-muted">Only lowercase letters, numbers, and hyphens</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Company Type</label>
                                <select class="form-control" name="company_type">
                                    <option value="towboat" <?php echo ($_POST['company_type'] ?? '') === 'towboat' ? 'selected' : ''; ?>>Towboat/Barge Operations</option>
                                    <option value="fishing" <?php echo ($_POST['company_type'] ?? '') === 'fishing' ? 'selected' : ''; ?>>Commercial Fishing</option>
                                    <option value="workboat" <?php echo ($_POST['company_type'] ?? '') === 'workboat' ? 'selected' : ''; ?>>Workboat Services</option>
                                    <option value="tug" <?php echo ($_POST['company_type'] ?? '') === 'tug' ? 'selected' : ''; ?>>Tugboat Services</option>
                                    <option value="supply" <?php echo ($_POST['company_type'] ?? '') === 'supply' ? 'selected' : ''; ?>>Supply Vessel</option>
                                    <option value="passenger" <?php echo ($_POST['company_type'] ?? '') === 'passenger' ? 'selected' : ''; ?>>Passenger Vessel</option>
                                    <option value="other" <?php echo ($_POST['company_type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Fleet Size (Estimate)</label>
                                <select class="form-control" name="fleet_size">
                                    <option value="1" <?php echo ($_POST['fleet_size'] ?? '') === '1' ? 'selected' : ''; ?>>1 vessel</option>
                                    <option value="2-5" <?php echo ($_POST['fleet_size'] ?? '') === '2-5' ? 'selected' : ''; ?>>2-5 vessels</option>
                                    <option value="6-10" <?php echo ($_POST['fleet_size'] ?? '') === '6-10' ? 'selected' : ''; ?>>6-10 vessels</option>
                                    <option value="11-25" <?php echo ($_POST['fleet_size'] ?? '') === '11-25' ? 'selected' : ''; ?>>11-25 vessels</option>
                                    <option value="25+" <?php echo ($_POST['fleet_size'] ?? '') === '25+' ? 'selected' : ''; ?>>25+ vessels</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contact Phone</label>
                        <input type="tel" class="form-control" name="contact_phone" 
                               value="<?php echo htmlspecialchars($_POST['contact_phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary btn-next">
                            Continue <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
                <?php break; 
                
                case 2: ?>
                
                <!-- Step 2: Admin Account -->
                <h4 class="mb-4">Create Your Admin Account</h4>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="admin_name" 
                                       value="<?php echo htmlspecialchars($_POST['admin_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control" name="admin_email" 
                                       value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" class="form-control" name="password" 
                                       minlength="8" required>
                                <small class="form-text text-muted">Minimum 8 characters</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" name="password_confirm" 
                                       minlength="8" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Job Title</label>
                        <input type="text" class="form-control" name="job_title" 
                               value="<?php echo htmlspecialchars($_POST['job_title'] ?? ''); ?>" 
                               placeholder="Captain, Port Engineer, Fleet Manager, etc.">
                    </div>
                    
                    <div class="text-end">
                        <a href="signup.php?step=1" class="btn btn-outline-secondary me-2">Back</a>
                        <button type="submit" class="btn btn-primary btn-next">
                            Continue <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
                <?php break; case 3: ?>
                
                <!-- Step 3: Choose Plan -->
                <h4 class="mb-4">Choose Your Plan</h4>
                <form method="POST" id="planForm">
                    <div class="row">
                        <!-- Trial Plan -->
                        <div class="col-lg-4">
                            <div class="plan-card" data-plan="trial">
                                <h5 class="text-primary">Free Trial</h5>
                                <div class="h2 mb-3">$0 <small class="text-muted">/ 30 days</small></div>
                                <ul class="list-unstyled text-start">
                                    <li><i class="fas fa-check feature-check"></i>1 Vessel</li>
                                    <li><i class="fas fa-check feature-check"></i>3 Users</li>
                                    <li><i class="fas fa-check feature-check"></i>Basic Logging</li>
                                    <li><i class="fas fa-check feature-check"></i>Offline Sync</li>
                                    <li><i class="fas fa-check feature-check"></i>Email Support</li>
                                </ul>
                                <button type="button" class="btn btn-outline-primary w-100 select-plan">
                                    Start Free Trial
                                </button>
                            </div>
                        </div>
                        
                        <!-- Basic Plan -->
                        <div class="col-lg-4">
                            <div class="plan-card" data-plan="basic">
                                <h5 class="text-success">Basic Plan</h5>
                                <div class="h2 mb-3">$49.99 <small class="text-muted">/ month</small></div>
                                <ul class="list-unstyled text-start">
                                    <li><i class="fas fa-check feature-check"></i>2 Vessels</li>
                                    <li><i class="fas fa-check feature-check"></i>10 Users</li>
                                    <li><i class="fas fa-check feature-check"></i>All Basic Features</li>
                                    <li><i class="fas fa-check feature-check"></i>Crew Management</li>
                                    <li><i class="fas fa-check feature-check"></i>Basic Reports</li>
                                </ul>
                                <button type="button" class="btn btn-outline-success w-100 select-plan">
                                    Choose Basic
                                </button>
                            </div>
                        </div>
                        
                        <!-- Professional Plan -->
                        <div class="col-lg-4">
                            <div class="plan-card" data-plan="professional">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="text-warning">Professional</h5>
                                    <span class="badge bg-warning text-dark">Most Popular</span>
                                </div>
                                <div class="h2 mb-3">$149.99 <small class="text-muted">/ month</small></div>
                                <ul class="list-unstyled text-start">
                                    <li><i class="fas fa-check feature-check"></i>10 Vessels</li>
                                    <li><i class="fas fa-check feature-check"></i>50 Users</li>
                                    <li><i class="fas fa-check feature-check"></i>All Basic Features</li>
                                    <li><i class="fas fa-check feature-check"></i>Advanced Reports</li>
                                    <li><i class="fas fa-check feature-check"></i>API Access</li>
                                    <li><i class="fas fa-check feature-check"></i>Maintenance Tracking</li>
                                </ul>
                                <button type="button" class="btn btn-warning w-100 select-plan text-dark">
                                    Choose Professional
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="selected_plan" id="selectedPlan" value="trial">
                    
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Start with a free trial!</strong> You can upgrade or downgrade your plan at any time.
                        All plans include our 30-day money-back guarantee.
                    </div>
                    
                    <div class="text-end">
                        <a href="signup.php?step=2" class="btn btn-outline-secondary me-2">Back</a>
                        <button type="submit" class="btn btn-primary btn-next">
                            Continue <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
                <?php break; case 4: ?>
                
                <!-- Step 4: Final Setup -->
                <h4 class="mb-4">Complete Your Setup</h4>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Company Information</h6>
                        <ul class="list-unstyled">
                            <li><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['onboarding_data']['company_name']); ?></li>
                            <li><strong>Domain:</strong> <?php echo htmlspecialchars($_SESSION['onboarding_data']['company_domain']); ?></li>
                            <li><strong>Type:</strong> <?php echo htmlspecialchars($_SESSION['onboarding_data']['company_type']); ?></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Admin Account</h6>
                        <ul class="list-unstyled">
                            <li><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['onboarding_data']['admin_name']); ?></li>
                            <li><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['onboarding_data']['admin_email']); ?></li>
                            <li><strong>Plan:</strong> <?php echo ucfirst($_SESSION['onboarding_data']['selected_plan']); ?></li>
                        </ul>
                    </div>
                </div>
                
                <div class="alert alert-success">
                    <h6><i class="fas fa-rocket me-2"></i>Ready to Launch!</h6>
                    <p class="mb-0">
                        We'll create your dedicated database, set up your admin account, and configure your vessel logging system.
                        This process takes just a few seconds.
                    </p>
                </div>
                
                <form method="POST">
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" name="agree_terms" required>
                            <label class="form-check-label" for="agreeTerms">
                                I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and 
                                <a href="privacy.php" target="_blank">Privacy Policy</a>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agreeEmails" name="agree_emails">
                            <label class="form-check-label" for="agreeEmails">
                                Send me product updates and maritime industry news (optional)
                            </label>
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <a href="signup.php?step=3" class="btn btn-outline-secondary me-2">Back</a>
                        <button type="submit" class="btn btn-success btn-next" id="completeBtn">
                            <i class="fas fa-check me-2"></i>Complete Setup
                        </button>
                    </div>
                </form>
                <?php break; endswitch; ?>

                <!-- Login Link -->
                <div class="text-center mt-4 pt-4 border-top">
                    <p class="text-muted">Already have an account? 
                        <a href="login_enhanced.php" class="text-decoration-none">Sign in here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Plan selection
        document.addEventListener('DOMContentLoaded', function() {
            const planCards = document.querySelectorAll('.plan-card');
            const selectedPlanInput = document.getElementById('selectedPlan');
            
            planCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove selected class from all cards
                    planCards.forEach(c => c.classList.remove('selected'));
                    
                    // Add selected class to clicked card
                    this.classList.add('selected');
                    
                    // Update hidden input
                    selectedPlanInput.value = this.dataset.plan;
                });
            });
            
            // Select trial by default
            if (selectedPlanInput) {
                const trialCard = document.querySelector('[data-plan="trial"]');
                if (trialCard) {
                    trialCard.classList.add('selected');
                }
            }
        });

        // Complete setup with loading state
        const completeBtn = document.getElementById('completeBtn');
        if (completeBtn) {
            completeBtn.addEventListener('click', function() {
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Your Account...';
                this.disabled = true;
            });
        }

        // Company domain validation
        const domainInput = document.querySelector('input[name="company_domain"]');
        if (domainInput) {
            domainInput.addEventListener('input', function() {
                this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
            });
        }
    </script>
</body>
</html>

<?php
/**
 * Step Handler Functions
 */

function handleStep1($data) {
    $errors = [];
    
    // Validate required fields
    if (empty($data['company_name'])) {
        $errors[] = 'Company name is required.';
    }
    
    if (empty($data['company_domain'])) {
        $errors[] = 'Company domain is required.';
    } else {
        // Validate domain format
        if (!preg_match('/^[a-z0-9-]+$/', $data['company_domain'])) {
            $errors[] = 'Company domain can only contain lowercase letters, numbers, and hyphens.';
        }
        
        // Check if domain is available
        if (isDomainTaken($data['company_domain'])) {
            $errors[] = 'This company domain is already taken. Please choose another one.';
        }
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    return [
        'success' => true,
        'data' => [
            'company_name' => trim($data['company_name']),
            'company_domain' => trim(strtolower($data['company_domain'])),
            'company_type' => $data['company_type'] ?? 'other',
            'fleet_size' => $data['fleet_size'] ?? '1',
            'contact_phone' => trim($data['contact_phone'] ?? '')
        ]
    ];
}

function handleStep2($data) {
    $errors = [];
    
    // Validate required fields
    if (empty($data['admin_name'])) {
        $errors[] = 'Full name is required.';
    }
    
    if (empty($data['admin_email'])) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($data['password'])) {
        $errors[] = 'Password is required.';
    } elseif (strlen($data['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    
    if ($data['password'] !== $data['password_confirm']) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    return [
        'success' => true,
        'data' => [
            'admin_name' => trim($data['admin_name']),
            'admin_email' => trim(strtolower($data['admin_email'])),
            'password' => $data['password'],
            'job_title' => trim($data['job_title'] ?? '')
        ]
    ];
}

function handleStep3($data) {
    $valid_plans = ['trial', 'basic', 'professional', 'enterprise'];
    $selected_plan = $data['selected_plan'] ?? 'trial';
    
    if (!in_array($selected_plan, $valid_plans)) {
        return ['success' => false, 'errors' => ['Invalid plan selected.']];
    }
    
    return [
        'success' => true,
        'data' => ['selected_plan' => $selected_plan]
    ];
}

function completeRegistration($data) {
    try {
        $saas_config = getSaaSConfig();
        
        // Connect to license database
        $license_pdo = new PDO(
            "mysql:host={$saas_config['license_host']};dbname={$saas_config['license_database']}", 
            $saas_config['license_username'], 
            $saas_config['license_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $license_pdo->beginTransaction();
        
        // Generate database credentials
        $db_name = $data['company_domain'] . '_vessels';
        $db_username = $data['company_domain'] . '_user';
        $db_password = generatePassword(16);
        
        // Get plan details
        $stmt = $license_pdo->prepare("SELECT * FROM subscription_plans WHERE plan_code = ?");
        $stmt->execute([$data['selected_plan']]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Create company record
        $stmt = $license_pdo->prepare("
            INSERT INTO companies (
                company_name, company_domain, contact_email, contact_phone,
                database_host, database_name, database_username, database_password,
                subscription_plan, subscription_status, trial_start_date, trial_end_date,
                max_vessels, max_users, max_storage_mb, enabled_features,
                monthly_price, annual_price, company_type, fleet_size_estimate,
                setup_completed, onboarding_step
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $trial_end = $data['selected_plan'] === 'trial' ? date('Y-m-d', strtotime('+30 days')) : null;
        
        $stmt->execute([
            $data['company_name'],
            $data['company_domain'],
            $data['admin_email'],
            $data['contact_phone'],
            'localhost',
            $db_name,
            $db_username,
            base64_encode($db_password),
            $data['selected_plan'],
            'active',
            date('Y-m-d'),
            $trial_end,
            $plan['max_vessels'],
            $plan['max_users'],
            $plan['max_storage_mb'],
            $plan['included_features'],
            $plan['monthly_price'],
            $plan['annual_price'],
            $data['company_type'],
            (int)$data['fleet_size'],
            true,
            'completed'
        ]);
        
        $company_id = $license_pdo->lastInsertId();
        
        // Create company database
        $result = createCompanyDatabase($db_name, $db_username, $db_password, $data);
        
        if (!$result['success']) {
            throw new Exception($result['message']);
        }
        
        $user_id = $result['user_id'];
        
        // Add primary contact
        $stmt = $license_pdo->prepare("
            INSERT INTO company_contacts (company_id, contact_name, contact_email, contact_role, is_primary)
            VALUES (?, ?, ?, 'owner', 1)
        ");
        $stmt->execute([$company_id, $data['admin_name'], $data['admin_email']]);
        
        // Log the registration event
        $stmt = $license_pdo->prepare("
            INSERT INTO license_events (company_id, event_type, new_value, triggered_by)
            VALUES (?, 'trial_started', ?, 'system')
        ");
        $stmt->execute([$company_id, $data['selected_plan']]);
        
        $license_pdo->commit();
        
        return [
            'success' => true,
            'company_id' => $company_id,
            'user_id' => $user_id,
            'user_name' => $data['admin_name'],
            'user_email' => $data['admin_email'],
            'company_name' => $data['company_name'],
            'company_domain' => $data['company_domain']
        ];
        
    } catch (Exception $e) {
        if (isset($license_pdo)) {
            $license_pdo->rollBack();
        }
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['Registration failed. Please try again or contact support.']];
    }
}

function isDomainTaken($domain) {
    try {
        $saas_config = getSaaSConfig();
        $license_pdo = new PDO(
            "mysql:host={$saas_config['license_host']};dbname={$saas_config['license_database']}", 
            $saas_config['license_username'], 
            $saas_config['license_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $license_pdo->prepare("SELECT id FROM companies WHERE company_domain = ?");
        $stmt->execute([$domain]);
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        return true; // Assume taken if we can't check
    }
}

function createCompanyDatabase($db_name, $db_username, $db_password, $company_data) {
    try {
        $mysql_root_pdo = new PDO("mysql:host=localhost", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Create database
        $mysql_root_pdo->exec("CREATE DATABASE `$db_name`");
        
        // Create user and grant privileges
        $mysql_root_pdo->exec("CREATE USER '$db_username'@'localhost' IDENTIFIED BY '$db_password'");
        $mysql_root_pdo->exec("GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_username'@'localhost'");
        $mysql_root_pdo->exec("FLUSH PRIVILEGES");
        
        // Connect to new database and create tables
        $company_pdo = new PDO("mysql:host=localhost;dbname=$db_name", $db_username, $db_password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Create basic tables structure
        $sql = file_get_contents('vessel_logger_structure.sql');
        if ($sql) {
            $company_pdo->exec($sql);
        }
        
        // Create admin user
        $stmt = $company_pdo->prepare("
            INSERT INTO users (name, email, password, role, is_active, created_at)
            VALUES (?, ?, ?, 'owner', 1, NOW())
        ");
        $stmt->execute([
            $company_data['admin_name'],
            $company_data['admin_email'],
            password_hash($company_data['password'], PASSWORD_DEFAULT)
        ]);
        
        $user_id = $company_pdo->lastInsertId();
        
        // Create support user
        $stmt = $company_pdo->prepare("
            INSERT INTO users (name, email, password, role, is_active, created_at)
            VALUES ('LogicDock Support', 'support@logicdock.com', ?, 'support', 1, NOW())
        ");
        $stmt->execute([password_hash(generatePassword(12), PASSWORD_DEFAULT)]);
        
        return ['success' => true, 'user_id' => $user_id];
        
    } catch (Exception $e) {
        error_log("Database creation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create company database: ' . $e->getMessage()];
    }
}

function generatePassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}
?>
