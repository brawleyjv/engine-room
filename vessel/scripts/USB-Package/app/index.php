<?php
/**
 * Vessel Logger - Main Entry Point
 * Handles initial setup, authentication, and routing
 */

// Define application constant
define('VESSEL_LOGGER', true);

// Include core configuration
require_once __DIR__ . '/config/config.php';

// Set security headers
setSecurityHeaders();

// Initialize default admin user if needed
$default_password = initializeDefaultUser();

// Check if this is the initial setup
$vessel_config = getVesselConfig('setup_completed', false);

// If not set up, redirect to setup
if (!$vessel_config && !strpos($_SERVER['REQUEST_URI'], 'setup.php')) {
    header('Location: setup.php');
    exit;
}

// If setup is complete but no user is logged in, redirect to login
if ($vessel_config && !isLoggedIn() && !strpos($_SERVER['REQUEST_URI'], 'login.php')) {
    header('Location: login.php');
    exit;
}

// If logged in, redirect to dashboard
if (isLoggedIn() && ($_SERVER['REQUEST_URI'] === '/' || basename($_SERVER['REQUEST_URI']) === 'index.php')) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Logger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }
        .status-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin: 5px;
        }
        .status-online {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-offline {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .vessel-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
        }
        .feature-card {
            text-align: center;
            padding: 30px 20px;
            height: 100%;
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .feature-icon {
            font-size: 3rem;
            color: #764ba2;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-10">
                <!-- Header -->
                <div class="text-center mb-5">
                    <div class="vessel-icon">
                        <i class="fas fa-ship"></i>
                    </div>
                    <h1 class="text-white mb-3">Vessel Data Logger</h1>
                    <p class="text-white-50 fs-5">Professional Marine Equipment Management System</p>
                </div>

                <!-- Status Card -->
                <div class="card mb-4">
                    <div class="card-body text-center p-4">
                        <h3 class="card-title mb-4">
                            <i class="fas fa-tachometer-alt text-primary me-2"></i>
                            System Status
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <span class="status-badge status-online">
                                    <i class="fas fa-server me-1"></i>
                                    Server Online
                                </span>
                            </div>
                            <div class="col-md-3">
                                <span class="status-badge status-online">
                                    <i class="fas fa-database me-1"></i>
                                    Database Ready
                                </span>
                            </div>
                            <div class="col-md-3">
                                <span class="status-badge <?= isOnline() ? 'status-online' : 'status-offline' ?>">
                                    <i class="fas fa-wifi me-1"></i>
                                    <?= isOnline() ? 'Online' : 'Offline' ?>
                                </span>
                            </div>
                            <div class="col-md-3">
                                <span class="status-badge status-online">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Secure
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($default_password): ?>
                            <div class="alert alert-warning mt-4">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Default Admin Created:</strong> Username: <code>admin</code>, Password: <code><?= htmlspecialchars($default_password) ?></code>
                                <br><small>Please change this password after first login for security.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Feature Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <h5 class="card-title">Equipment Monitoring</h5>
                                <p class="card-text">Track main engines, generators, and auxiliary systems with real-time data logging.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h5 class="card-title">Performance Analytics</h5>
                                <p class="card-text">Advanced reporting and trend analysis for equipment performance optimization.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                                <h5 class="card-title">Office Sync</h5>
                                <p class="card-text">Automatic synchronization with office systems when internet connection is available.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="text-center">
                    <?php if (!$vessel_config): ?>
                        <a href="setup.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-play me-2"></i>
                            Start Setup
                        </a>
                    <?php elseif (!isLoggedIn()): ?>
                        <a href="login.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Login
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Go to Dashboard
                        </a>
                    <?php endif; ?>
                    
                    <a href="test.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-vial me-2"></i>
                        System Test
                    </a>
                </div>

                <!-- Footer -->
                <div class="text-center mt-5">
                    <p class="text-white-50">
                        <small>Vessel Logger v2.0 | Secure | Offline-First | USB Deployable</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
