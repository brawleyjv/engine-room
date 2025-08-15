<?php
/**
 * Multi-Step Company Onboarding System
 * Complete SaaS onboarding flow for new companies
 */

// Prevent automatic initialization from config_saas.php
define('SKIP_AUTO_INIT', true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config_saas.php';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success_message = '';

// Validate step progression - prevent users from skipping steps
if ($step > 1 && (!isset($_SESSION['onboarding_data']) || !is_array($_SESSION['onboarding_data']))) {
    // If trying to access step 2+ without step 1 data, redirect to step 1
    header('Location: signup.php?step=1');
    exit;
}

if ($step > 2 && !isset($_SESSION['onboarding_data']['company_name'])) {
    // If trying to access step 3+ without step 1 completion, redirect to step 1
    header('Location: signup.php?step=1');
    exit;
}

if ($step > 3 && !isset($_SESSION['onboarding_data']['admin_email'])) {
    // If trying to access step 4+ without step 2 completion, redirect to step 2
    header('Location: signup.php?step=2');
    exit;
}

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
                // Ensure onboarding_data exists before merging
                if (!isset($_SESSION['onboarding_data']) || !is_array($_SESSION['onboarding_data'])) {
                    $_SESSION['onboarding_data'] = [];
                }
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
                // Ensure onboarding_data exists before merging
                if (!isset($_SESSION['onboarding_data']) || !is_array($_SESSION['onboarding_data'])) {
                    $_SESSION['onboarding_data'] = [];
                }
                $_SESSION['onboarding_data'] = array_merge($_SESSION['onboarding_data'], $result['data']);
                
                // Create the company and database
                $creation_result = createCompanyDatabase($_SESSION['onboarding_data']);
                if ($creation_result['success']) {
                    // Store onboarding data before clearing
                    $company_data = $_SESSION['onboarding_data'];
                    
                    // Clear onboarding data
                    unset($_SESSION['onboarding_data']);
                    
                    // Set up user session for the new company (compatible with enhanced auth)
                    $_SESSION['company_domain'] = $company_data['company_domain'];
                    $_SESSION['company_name'] = $company_data['company_name'];
                    $_SESSION['company_id'] = $creation_result['company_id'];
                    $_SESSION['user_id'] = $creation_result['user_id'];
                    $_SESSION['subscription_plan'] = $company_data['subscription_plan'];
                    
                    // Enhanced auth compatible session variables
                    $_SESSION['username'] = $company_data['admin_email'];
                    $_SESSION['email'] = $company_data['admin_email'];
                    $_SESSION['role'] = 'system_admin';
                    $_SESSION['user_location'] = 'office';
                    $_SESSION['assigned_vessel_id'] = null;
                    
                    // Parse admin name into first/last name for compatibility
                    $name_parts = explode(' ', trim($company_data['admin_name']), 2);
                    $_SESSION['first_name'] = $name_parts[0];
                    $_SESSION['last_name'] = isset($name_parts[1]) ? $name_parts[1] : '';
                    
                    // Legacy compatibility
                    $_SESSION['user_name'] = $company_data['admin_name'];
                    $_SESSION['user_role'] = 'admin';
                    
                    header('Location: welcome.php?new=1&company=' . urlencode($company_data['company_name']) . 
                           '&domain=' . urlencode($company_data['company_domain']) . 
                           '&user=' . urlencode($company_data['admin_name']));
                    exit;
                } else {
                    $errors[] = $creation_result['message'];
                }
            } else {
                $errors = $result['errors'];
            }
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Vessel Logger - Professional Maritime Logging</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .signup-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .signup-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            backdrop-filter: blur(10px);
        }
        
        .signup-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .signup-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .signup-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 1rem;
            position: relative;
            font-weight: bold;
        }
        
        .step.active {
            background: #3498db;
            color: white;
        }
        
        .step.completed {
            background: #27ae60;
            color: white;
        }
        
        .step.pending {
            background: #ecf0f1;
            color: #95a5a6;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 100%;
            top: 50%;
            width: 2rem;
            height: 2px;
            background: #ecf0f1;
            transform: translateY(-50%);
        }
        
        .step.completed:not(:last-child)::after {
            background: #27ae60;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #ecf0f1;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-outline-secondary {
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .features-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .feature-item:last-child {
            margin-bottom: 0;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-card">
            <div class="signup-header">
                <h1><i class="fas fa-ship me-3"></i>Vessel Logger</h1>
                <p>Professional Maritime Logging & Fleet Management</p>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?php echo ($step == 1) ? 'active' : ($step > 1 ? 'completed' : 'pending'); ?>">1</div>
                <div class="step <?php echo ($step == 2) ? 'active' : ($step > 2 ? 'completed' : 'pending'); ?>">2</div>
                <div class="step <?php echo ($step == 3) ? 'active' : ($step > 3 ? 'completed' : 'pending'); ?>">3</div>
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

            <?php switch ($step): 
                case 1: ?>
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
                                    <label class="form-label">Contact Email *</label>
                                    <input type="email" class="form-control" name="contact_email" 
                                           value="<?php echo htmlspecialchars($_POST['contact_email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Company Identifier *</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="company_domain" 
                                       value="<?php echo htmlspecialchars($_POST['company_domain'] ?? ''); ?>" 
                                       pattern="[a-z0-9-]+" title="Only lowercase letters, numbers, and hyphens" required>
                            </div>
                            <small class="form-text text-muted">
                                A unique identifier for your company (letters, numbers, and hyphens only).<br>
                                <strong>Note:</strong> This is NOT a website domain - just an internal company ID.
                            </small>
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
                                    <input type="password" class="form-control" name="admin_password" required>
                                    <small class="form-text text-muted">Minimum 8 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" name="admin_password_confirm" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Job Title</label>
                            <input type="text" class="form-control" name="admin_title" 
                                   value="<?php echo htmlspecialchars($_POST['admin_title'] ?? ''); ?>" 
                                   placeholder="e.g., Port Captain, Operations Manager, Fleet Director">
                        </div>
                        
                        <div class="text-end">
                            <a href="signup.php?step=1" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                Continue <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </form>
                    <?php break; 
                
                case 3: ?>
                    <!-- Step 3: Plan Selection -->
                    <h4 class="mb-4">Choose Your Plan</h4>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">30-Day Free Trial</h5>
                                        <h3 class="text-primary">Free</h3>
                                        <p class="card-text">Perfect for evaluation</p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Basic logging features</li>
                                            <li><i class="fas fa-check text-success me-2"></i>1 vessel only</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Up to 5 users</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Email support</li>
                                        </ul>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="subscription_plan" 
                                                   value="trial" id="trial" checked>
                                            <label class="form-check-label" for="trial">
                                                <strong>Start Free Trial</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-primary">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Basic Package</h5>
                                        <h3 class="text-primary">$49/month</h3>
                                        <p class="card-text">Core logging features</p>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Basic Wheelhouse (position & activities)</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Basic Crew (name, TWIC, position, dates)</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Basic Engine Room (RPM, temps, pressures)</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Unlimited vessels</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Unlimited users</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Priority support</li>
                                        </ul>
                                        <small class="text-muted">+ Add-on modules $24.95/month each</small>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="radio" name="subscription_plan" 
                                                   value="professional" id="professional">
                                            <label class="form-check-label" for="professional">
                                                <strong>Start Basic Package</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="agree_terms" id="agree_terms" required>
                                <label class="form-check-label" for="agree_terms">
                                    I agree to the <a href="#" target="_blank">Terms of Service</a> and 
                                    <a href="#" target="_blank">Privacy Policy</a> *
                                </label>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <a href="signup.php?step=2" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-rocket me-2"></i>Create My Account
                            </button>
                        </div>
                    </form>
                    <?php break; 
            endswitch; ?>
            
            <!-- Features Preview -->
            <div class="features-preview">
                <h6 class="mb-3"><i class="fas fa-star text-warning me-2"></i>What You Get</h6>
                <div class="row">
                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div>
                                <strong>Engine Room Logging</strong><br>
                                <small class="text-muted">RPM, temperatures, pressures, fuel</small>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <strong>Performance Analytics</strong><br>
                                <small class="text-muted">Trends, reports, maintenance alerts</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div>
                                <strong>Offline Access</strong><br>
                                <small class="text-muted">Works without internet, syncs later</small>
                            </div>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <strong>Team Management</strong><br>
                                <small class="text-muted">User roles, permissions, audit logs</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-format company domain
        document.querySelector('input[name="company_domain"]')?.addEventListener('input', function(e) {
            this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
        });
        
        // Password strength indicator
        document.querySelector('input[name="admin_password"]')?.addEventListener('input', function(e) {
            const password = e.target.value;
            const strength = password.length >= 8 ? 'Good' : 'Too short';
            // Could add more sophisticated password checking here
        });
        
        // Form validation before submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const step = <?php echo $step; ?>;
                
                if (step === 2) {
                    const password = document.querySelector('input[name="admin_password"]').value;
                    const confirm = document.querySelector('input[name="admin_password_confirm"]').value;
                    
                    if (password !== confirm) {
                        e.preventDefault();
                        alert('Passwords do not match');
                        return;
                    }
                    
                    if (password.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters');
                        return;
                    }
                }
            });
        });
    </script>
</body>
</html>

<?php
/**
 * Step Processing Functions
 */

function handleStep1($data) {
    $errors = [];
    
    // Validate required fields
    if (empty($data['company_name'])) {
        $errors[] = 'Company name is required';
    }
    
    if (empty($data['contact_email']) || !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid contact email is required';
    }
    
    if (empty($data['company_domain']) || !preg_match('/^[a-z0-9-]+$/', $data['company_domain'])) {
        $errors[] = 'Company identifier must contain only lowercase letters, numbers, and hyphens';
    }
    
    // Check if company identifier is already taken
    if (!empty($data['company_domain'])) {
        $existing = checkDomainAvailability($data['company_domain']);
        if (!$existing) {
            $errors[] = 'This company identifier is already taken. Please choose another.';
        }
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    return [
        'success' => true,
        'data' => [
            'company_name' => $data['company_name'],
            'contact_email' => $data['contact_email'],
            'company_domain' => $data['company_domain'],
            'company_type' => $data['company_type'] ?? 'other',
            'fleet_size' => $data['fleet_size'] ?? '1',
            'contact_phone' => $data['contact_phone'] ?? ''
        ]
    ];
}

function handleStep2($data) {
    $errors = [];
    
    // Validate required fields
    if (empty($data['admin_name'])) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($data['admin_email']) || !filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email address is required';
    }
    
    if (empty($data['admin_password']) || strlen($data['admin_password']) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }
    
    if ($data['admin_password'] !== $data['admin_password_confirm']) {
        $errors[] = 'Passwords do not match';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    return [
        'success' => true,
        'data' => [
            'admin_name' => $data['admin_name'],
            'admin_email' => $data['admin_email'],
            'admin_password' => $data['admin_password'],
            'admin_title' => $data['admin_title'] ?? ''
        ]
    ];
}

function handleStep3($data) {
    $errors = [];
    
    if (empty($data['subscription_plan'])) {
        $errors[] = 'Please select a subscription plan';
    }
    
    if (empty($data['agree_terms'])) {
        $errors[] = 'You must agree to the Terms of Service and Privacy Policy';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    return [
        'success' => true,
        'data' => [
            'subscription_plan' => $data['subscription_plan']
        ]
    ];
}

function checkDomainAvailability($domain) {
    global $license_db_config;
    
    try {
        $license_conn = new mysqli(
            $license_db_config['host'],
            $license_db_config['username'],
            $license_db_config['password'],
            $license_db_config['database']
        );
        
        if ($license_conn->connect_error) {
            error_log("License DB connection failed: " . $license_conn->connect_error);
            throw new Exception("License database connection failed: " . $license_conn->connect_error);
        }
        
        $stmt = $license_conn->prepare("SELECT id FROM companies WHERE company_domain = ?");
        if (!$stmt) {
            error_log("SQL prepare failed: " . $license_conn->error);
            throw new Exception("Database query failed");
        }
        
        $stmt->bind_param("s", $domain);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $is_available = $result->num_rows === 0;
        error_log("Domain check for '$domain': " . ($is_available ? "AVAILABLE" : "TAKEN") . " (found {$result->num_rows} matches)");
        
        $stmt->close();
        $license_conn->close();
        
        return $is_available; // Available if no rows found
        
    } catch (Exception $e) {
        error_log("Domain check error for '$domain': " . $e->getMessage());
        return false; // Assume not available on error
    }
}

function createCompanyDatabase($data) {
    // Include the company installer
    require_once __DIR__ . '/company_installer.php';
    
    try {
        $installer = new CompanyInstaller();
        
        // Prepare company data for installation
        $company_data = [
            'company_name' => $data['company_name'],
            'company_domain' => $data['company_domain'],
            'admin_username' => 'admin',
            'admin_password' => $data['admin_password'],
            'admin_email' => $data['admin_email'],
            'admin_name' => $data['admin_name'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'] ?? '',
            'company_type' => $data['company_type'] ?? 'other',
            'fleet_size' => $data['fleet_size'] ?? '1',
            'subscription_plan' => $data['subscription_plan'] ?? 'trial'
        ];
        
        // Install the company
        $result = $installer->installCompany($company_data);
        
        if ($result['success']) {
            return [
                'success' => true,
                'company_id' => $company_data['company_id'] ?? 1,
                'user_id' => 1,
                'company_url' => $result['company_url'],
                'message' => 'Company installation completed successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => $result['error']
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Installation failed: ' . $e->getMessage()
        ];
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
