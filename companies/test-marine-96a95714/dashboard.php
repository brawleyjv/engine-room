<?php
/**
 * Company Dashboard Template
 * Secure main dashboard with authentication
 */

// Include company configuration and authentication
require_once 'config.php';
require_once 'includes/auth.php';

// Require authentication to access this page
requireAuth();

// Check if password change is required
if (isPasswordChangeRequired()) {
    header('Location: change_password.php');
    exit;
}

$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(COMPANY_NAME) ?> - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #2c3e50, #3498db) !important;
            border-bottom: 3px solid #1abc9c;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .main-content {
            margin-top: 2rem;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: none;
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            color: inherit;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            color: inherit;
            text-decoration: none;
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
        }
        
        .security-info {
            background: #e8f5e8;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-ship me-2"></i>Vessel Logger
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="vessels.php">
                            <i class="fas fa-ship me-1"></i>Vessels
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="alert('Feature coming soon!')">
                            <i class="fas fa-clipboard-list me-1"></i>Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="alert('Feature coming soon!')">
                            <i class="fas fa-chart-line me-1"></i>Reports
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($current_user['full_name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="change_password.php">
                                <i class="fas fa-key me-2"></i>Change Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?action=logout">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container main-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-anchor me-3"></i>Welcome to Vessel Logger</h1>
                    <h3><?= htmlspecialchars(COMPANY_NAME) ?></h3>
                    <p class="mb-0">Professional maritime management system for your fleet operations.</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="stat-card bg-transparent border border-light">
                        <div class="stat-number"><?= defined('TRIAL_END') ? ceil((strtotime(TRIAL_END) - time()) / (60*60*24)) : '30' ?></div>
                        <div>Days Left in Trial</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Status -->
        <div class="security-info">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6><i class="fas fa-shield-alt text-success me-2"></i>Security Status</h6>
                    <p class="mb-0">
                        <strong>Logged in as:</strong> <?= htmlspecialchars($current_user['username']) ?> (<?= htmlspecialchars($current_user['role']) ?>)<br>
                        <strong>Company:</strong> <?= htmlspecialchars(COMPANY_NAME) ?> (<?= htmlspecialchars(COMPANY_DOMAIN) ?>)
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <small class="text-muted">
                        Session secured with encryption<br>
                        <i class="fas fa-lock text-success"></i> Protected
                    </small>
                </div>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-md-3">
                <div class="dashboard-card stat-card mb-4">
                    <div class="stat-number">0</div>
                    <div>Vessels</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card stat-card mb-4">
                    <div class="stat-number">0</div>
                    <div>Engine Hours</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card stat-card mb-4">
                    <div class="stat-number">0</div>
                    <div>Log Entries</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="dashboard-card stat-card mb-4">
                    <div class="stat-number">0</div>
                    <div>Alerts</div>
                </div>
            </div>
        </div>

        <!-- Feature Cards -->
        <div class="feature-grid">
            <a href="vessels.php" class="feature-card">
                <div class="feature-icon" style="background: #3498db;">
                    <i class="fas fa-ship"></i>
                </div>
                <h5>Vessel Management</h5>
                <p class="text-muted">Add and manage your fleet of vessels with detailed specifications.</p>
            </a>
            
            <a href="#" onclick="alert('Feature coming soon!')" class="feature-card">
                <div class="feature-icon" style="background: #e74c3c;">
                    <i class="fas fa-cogs"></i>
                </div>
                <h5>Engine Room Logs</h5>
                <p class="text-muted">Record engine hours, temperatures, pressures, and maintenance.</p>
            </a>
            
            <a href="#" onclick="alert('Feature coming soon!')" class="feature-card">
                <div class="feature-icon" style="background: #f39c12;">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h5>Performance Reports</h5>
                <p class="text-muted">Analyze fuel consumption, efficiency, and operational trends.</p>
            </a>
            
            <a href="#" onclick="alert('Feature coming soon!')" class="feature-card">
                <div class="feature-icon" style="background: #27ae60;">
                    <i class="fas fa-wrench"></i>
                </div>
                <h5>Maintenance Tracking</h5>
                <p class="text-muted">Schedule and track preventive maintenance tasks.</p>
            </a>
            
            <a href="#" onclick="alert('Feature coming soon!')" class="feature-card">
                <div class="feature-icon" style="background: #9b59b6;">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h5>Offline Access</h5>
                <p class="text-muted">Work without internet connection, sync when available.</p>
            </a>
            
            <a href="#" onclick="alert('Feature coming soon!')" class="feature-card">
                <div class="feature-icon" style="background: #34495e;">
                    <i class="fas fa-users"></i>
                </div>
                <h5>User Management</h5>
                <p class="text-muted">Manage crew access, roles, and permissions.</p>
            </a>
        </div>

        <!-- Getting Started -->
        <div class="row mt-5">
            <div class="col-md-12">
                <div class="dashboard-card">
                    <div class="card-body p-4">
                        <h4><i class="fas fa-rocket me-2"></i>Getting Started</h4>
                        <p>Welcome to your new vessel management system! Here's how to get started:</p>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <strong>1</strong>
                                    </div>
                                    <div>
                                        <strong>Add Your Vessels</strong><br>
                                        <small class="text-muted">Start by adding your fleet to the system</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <strong>2</strong>
                                    </div>
                                    <div>
                                        <strong>Configure Engines</strong><br>
                                        <small class="text-muted">Set up engine configurations and parameters</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <strong>3</strong>
                                    </div>
                                    <div>
                                        <strong>Start Logging</strong><br>
                                        <small class="text-muted">Begin recording engine room data</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="vessels.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus me-2"></i>Add Your First Vessel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh trial days counter
        setInterval(function() {
            // Could add AJAX call to update trial days dynamically
        }, 60000); // Every minute
        
        // Welcome animation
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.feature-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>
