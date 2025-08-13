<?php
/**
 * Enhanced Multi-Tenant Login System
 * Handles authentication for the SaaS vessel management platform
 * Supports company domain-based routing and user role management
 */

session_start();
require_once __DIR__ . '/config_saas.php';

$error_message = '';
$success_message = '';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    $success_message = 'You have been logged out successfully.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $company_domain = trim($_POST['company_domain'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } else {
        $login_result = authenticateUser($email, $password, $company_domain);
        
        if ($login_result['success']) {
            // Set session variables
            $_SESSION['user_id'] = $login_result['user']['id'];
            $_SESSION['user_name'] = $login_result['user']['name'];
            $_SESSION['user_email'] = $login_result['user']['email'];
            $_SESSION['user_role'] = $login_result['user']['role'];
            $_SESSION['company_id'] = $login_result['company']['id'];
            $_SESSION['company_name'] = $login_result['company']['company_name'];
            $_SESSION['company_domain'] = $login_result['company']['company_domain'];
            $_SESSION['subscription_plan'] = $login_result['company']['subscription_plan'];
            $_SESSION['subscription_status'] = $login_result['company']['subscription_status'];
            
            // Update last login time
            updateLastLogin($login_result['company']['id'], $login_result['user']['id']);
            
            // Redirect based on role and subscription status
            if ($login_result['company']['subscription_status'] === 'expired') {
                header('Location: subscription_expired.php');
            } elseif (!$login_result['company']['setup_completed']) {
                header('Location: company_admin.php');
            } elseif (in_array($_SESSION['user_role'], ['owner', 'admin'])) {
                header('Location: company_admin.php');
            } else {
                header('Location: vessel/engineroom/dashboard.php');
            }
            exit;
        } else {
            $error_message = $login_result['message'];
        }
    }
}

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['company_id'])) {
    // Redirect to appropriate dashboard
    if ($_SESSION['subscription_status'] === 'expired') {
        header('Location: subscription_expired.php');
    } elseif (in_array($_SESSION['user_role'], ['owner', 'admin'])) {
        header('Location: company_admin.php');
    } else {
        header('Location: vessel/engineroom/dashboard.php');
    }
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Logger - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .form-floating {
            margin-bottom: 20px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .feature-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin-top: 30px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .feature-item i {
            color: #667eea;
            margin-right: 15px;
            width: 20px;
        }
        .company-search {
            position: relative;
        }
        .company-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        .company-suggestion {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .company-suggestion:hover {
            background-color: #f8f9fa;
        }
        .company-suggestion:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <!-- Left side - Login Form -->
                <div class="col-md-6">
                    <div class="login-header">
                        <i class="fas fa-ship fa-3x mb-3"></i>
                        <h2>Vessel Logger</h2>
                        <p class="mb-0">Professional Maritime Logging</p>
                    </div>
                    
                    <div class="login-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="loginForm">
                            <div class="form-floating company-search">
                                <input type="text" class="form-control" id="company_domain" name="company_domain" 
                                       placeholder="Company Domain" required autocomplete="off">
                                <label for="company_domain">Company Domain</label>
                                <div class="company-suggestions" id="companySuggestions"></div>
                                <small class="form-text text-muted">Enter your company's domain (e.g., acme-marine)</small>
                            </div>

                            <div class="form-floating">
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="name@example.com" required>
                                <label for="email">Email Address</label>
                            </div>

                            <div class="form-floating">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Password" required>
                                <label for="password">Password</label>
                            </div>

                            <div class="d-grid">
                                <button class="btn btn-primary btn-login" type="submit" name="login">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <a href="forgot_password.php" class="text-decoration-none">
                                    <i class="fas fa-key me-1"></i>Forgot Password?
                                </a>
                            </div>

                            <hr class="my-4">

                            <div class="text-center">
                                <p class="text-muted mb-2">Need an account for your company?</p>
                                <a href="signup.php" class="btn btn-outline-primary">
                                    <i class="fas fa-user-plus me-2"></i>Start Free Trial
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right side - Features -->
                <div class="col-md-6">
                    <div class="feature-list">
                        <h4 class="mb-4">
                            <i class="fas fa-star text-warning me-2"></i>
                            Why Choose Vessel Logger?
                        </h4>

                        <div class="feature-item">
                            <i class="fas fa-cogs"></i>
                            <span>Complete Engine Room Logging</span>
                        </div>

                        <div class="feature-item">
                            <i class="fas fa-mobile-alt"></i>
                            <span>Works Offline & Online</span>
                        </div>

                        <div class="feature-item">
                            <i class="fas fa-users"></i>
                            <span>Multi-User Collaboration</span>
                        </div>

                        <div class="feature-item">
                            <i class="fas fa-chart-line"></i>
                            <span>Performance Analytics</span>
                        </div>

                        <div class="feature-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Secure Cloud Backup</span>
                        </div>

                        <div class="feature-item">
                            <i class="fas fa-clipboard-check"></i>
                            <span>Regulatory Compliance</span>
                        </div>

                        <div class="feature-item">
                            <i class="fas fa-headset"></i>
                            <span>24/7 Maritime Support</span>
                        </div>

                        <div class="feature-item">
                            <i class="fas fa-dollar-sign"></i>
                            <span>30-Day Free Trial</span>
                        </div>

                        <div class="mt-4 text-center">
                            <h6 class="text-muted">Trusted by maritime professionals worldwide</h6>
                            <div class="d-flex justify-content-center mt-3">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <span class="ms-2 text-muted">4.9/5 from 500+ reviews</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Company domain autocomplete
        let searchTimeout;
        const companyInput = document.getElementById('company_domain');
        const suggestions = document.getElementById('companySuggestions');

        companyInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                suggestions.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                searchCompanies(query);
            }, 300);
        });

        companyInput.addEventListener('blur', function() {
            // Hide suggestions after a short delay to allow for clicks
            setTimeout(() => {
                suggestions.style.display = 'none';
            }, 200);
        });

        function searchCompanies(query) {
            fetch('api/search_companies.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    suggestions.innerHTML = '';
                    
                    if (data.companies && data.companies.length > 0) {
                        data.companies.forEach(company => {
                            const div = document.createElement('div');
                            div.className = 'company-suggestion';
                            div.innerHTML = `
                                <strong>${company.company_name}</strong><br>
                                <small class="text-muted">${company.company_domain}</small>
                            `;
                            div.addEventListener('click', () => {
                                companyInput.value = company.company_domain;
                                suggestions.style.display = 'none';
                            });
                            suggestions.appendChild(div);
                        });
                        suggestions.style.display = 'block';
                    } else {
                        suggestions.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error searching companies:', error);
                    suggestions.style.display = 'none';
                });
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const companyDomain = document.getElementById('company_domain').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!companyDomain || !email || !password) {
                e.preventDefault();
                alert('Please fill in all fields.');
                return;
            }

            // Basic company domain validation
            if (!/^[a-z0-9-]+$/.test(companyDomain)) {
                e.preventDefault();
                alert('Company domain can only contain lowercase letters, numbers, and hyphens.');
                return;
            }
        });

        // Show loading state on form submission
        document.getElementById('loginForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>

<?php
/**
 * Authentication Functions
 */

function authenticateUser($email, $password, $company_domain) {
    try {
        $saas_config = getSaaSConfig();
        
        // Connect to license database
        $license_pdo = new PDO(
            "mysql:host={$saas_config['license_host']};dbname={$saas_config['license_database']}", 
            $saas_config['license_username'], 
            $saas_config['license_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Find company by domain
        $stmt = $license_pdo->prepare("SELECT * FROM companies WHERE company_domain = ? AND subscription_status IN ('active', 'suspended')");
        $stmt->execute([$company_domain]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$company) {
            return ['success' => false, 'message' => 'Company not found or account suspended.'];
        }
        
        // Check if trial expired and no subscription
        if ($company['subscription_plan'] === 'trial' && $company['trial_end_date'] < date('Y-m-d') && $company['subscription_status'] !== 'active') {
            // Update status to expired
            $stmt = $license_pdo->prepare("UPDATE companies SET subscription_status = 'expired' WHERE id = ?");
            $stmt->execute([$company['id']]);
            $company['subscription_status'] = 'expired';
        }
        
        // Connect to company database
        $company_pdo = new PDO(
            "mysql:host={$company['database_host']};dbname={$company['database_name']}", 
            $company['database_username'], 
            base64_decode($company['database_password']),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Find user by email
        $stmt = $company_pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }
        
        return [
            'success' => true,
            'user' => $user,
            'company' => $company
        ];
        
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Authentication service unavailable. Please try again later.'];
    }
}

function updateLastLogin($company_id, $user_id) {
    try {
        $saas_config = getSaaSConfig();
        
        // Update company last access
        $license_pdo = new PDO(
            "mysql:host={$saas_config['license_host']};dbname={$saas_config['license_database']}", 
            $saas_config['license_username'], 
            $saas_config['license_password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $stmt = $license_pdo->prepare("UPDATE companies SET last_access_at = NOW() WHERE id = ?");
        $stmt->execute([$company_id]);
        
        // Get company database info
        $stmt = $license_pdo->prepare("SELECT database_host, database_name, database_username, database_password FROM companies WHERE id = ?");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($company) {
            // Update user last login
            $company_pdo = new PDO(
                "mysql:host={$company['database_host']};dbname={$company['database_name']}", 
                $company['database_username'], 
                base64_decode($company['database_password']),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $company_pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user_id]);
        }
        
    } catch (Exception $e) {
        error_log("Last login update error: " . $e->getMessage());
    }
}
?>
