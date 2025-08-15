<?php
/**
 * Vessel Logger - Main Entry Point
 * Handles initial setup, authentication, and routing
 */

// Define application constant
define('VESSEL_LOGGER', true);

// Include core configuration
require_once __DIR__ . '/app/config/config.php';

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
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .welcome-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            max-width: 600px;
            margin: 2rem;
        }
        
        .vessel-icon {
            font-size: 4rem;
            color: #3498db;
            margin-bottom: 1.5rem;
        }
        
        .btn-action {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            margin: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .status-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: left;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        
        @media (max-width: 768px) {
            .welcome-container {
                margin: 1rem;
                padding: 2rem;
            }
            
            .vessel-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <div class="vessel-icon">
            <i class="fas fa-ship"></i>
        </div>
        
        <h1 class="h2 mb-3">Vessel Logger</h1>
        <p class="text-muted mb-4">
            Professional vessel management and engine logging system
        </p>
        
        <?php if (!$vessel_config): ?>
            <!-- Setup Required -->
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Setup Required</h5>
                <p>This vessel logger needs to be configured before use.</p>
            </div>
            
            <a href="setup.php" class="btn btn-primary btn-action">
                <i class="fas fa-cog me-2"></i>Start Setup
            </a>
            
            <?php if ($default_password): ?>
            <div class="alert alert-info mt-3">
                <strong>Default Admin Password Created:</strong><br>
                Check the file: <code>data/default_admin_password.txt</code>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Setup Complete -->
            <div class="alert alert-success">
                <h5><i class="fas fa-check-circle me-2"></i>System Ready</h5>
                <p>Vessel logger is configured and ready for use.</p>
            </div>
            
            <a href="login.php" class="btn btn-primary btn-action">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
            
            <a href="dashboard.php" class="btn btn-outline-primary btn-action">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
        <?php endif; ?>
        
        <!-- System Status -->
        <div class="status-card">
            <h6 class="mb-3">
                <i class="fas fa-info-circle me-2"></i>System Status
            </h6>
            
            <div class="status-item">
                <span>Version:</span>
                <span class="fw-bold"><?= VESSEL_APP_VERSION ?></span>
            </div>
            
            <div class="status-item">
                <span>Setup Status:</span>
                <span class="<?= $vessel_config ? 'text-success' : 'text-warning' ?>">
                    <i class="fas fa-<?= $vessel_config ? 'check' : 'exclamation-triangle' ?> me-1"></i>
                    <?= $vessel_config ? 'Complete' : 'Required' ?>
                </span>
            </div>
            
            <div class="status-item">
                <span>Database:</span>
                <span class="<?= file_exists(VESSEL_DB_FILE) ? 'text-success' : 'text-danger' ?>">
                    <i class="fas fa-<?= file_exists(VESSEL_DB_FILE) ? 'check' : 'times' ?> me-1"></i>
                    <?= file_exists(VESSEL_DB_FILE) ? 'Connected' : 'Not Found' ?>
                </span>
            </div>
            
            <div class="status-item">
                <span>Internet:</span>
                <span class="<?= isOnline() ? 'text-success' : 'text-warning' ?>">
                    <i class="fas fa-<?= isOnline() ? 'wifi' : 'wifi-slash' ?> me-1"></i>
                    <?= isOnline() ? 'Connected' : 'Offline' ?>
                </span>
            </div>
            
            <?php if ($vessel_config): ?>
            <div class="status-item">
                <span>Vessel:</span>
                <span class="fw-bold"><?= htmlspecialchars(getVesselConfig('vessel_name', 'Not Set')) ?></span>
            </div>
            
            <div class="status-item">
                <span>Company:</span>
                <span class="fw-bold"><?= htmlspecialchars(getVesselConfig('company_name', 'Not Set')) ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <?php if ($vessel_config): ?>
        <div class="mt-3">
            <small class="text-muted">
                <i class="fas fa-info-circle me-1"></i>
                <?= isOnline() ? 'Online - Data will sync automatically' : 'Offline - Data saved locally' ?>
            </small>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh online status every 30 seconds
        setInterval(function() {
            if (window.location.pathname.endsWith('index.php') || window.location.pathname === '/') {
                window.location.reload();
            }
        }, 30000);
    </script>
</body>
</html>
