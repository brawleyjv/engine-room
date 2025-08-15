<?php
/**
 * Vessels Management Page Template
 * Secure vessel management with authentication
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
    <title><?= htmlspecialchars(COMPANY_NAME) ?> - Vessels</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="offline/manifest.json">
    <meta name="theme-color" content="#3498db">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Vessel Logger">
    
    <!-- Styles -->
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="vessels.php">
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
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-ship me-3"></i>Vessel Management</h1>
                    <button class="btn btn-primary" onclick="alert('Add vessel feature coming soon!')">
                        <i class="fas fa-plus me-2"></i>Add Vessel
                    </button>
                </div>
                
                <!-- Coming Soon Message -->
                <div class="alert alert-info" id="coming-soon-alert">
                    <h4><i class="fas fa-info-circle me-2"></i>Vessel Management Coming Soon</h4>
                    <p>This feature is under development. You will be able to:</p>
                    <ul>
                        <li>Add and manage your fleet of vessels</li>
                        <li>Configure engines and equipment</li>
                        <li>Track vessel specifications and documents</li>
                        <li>Manage crew assignments</li>
                    </ul>
                    <p class="mb-0">Check back soon for updates!</p>
                </div>
                
                <!-- Offline Demo Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-wifi-slash me-2"></i>Offline Capabilities Demo</h5>
                    </div>
                    <div class="card-body">
                        <p>Test the offline functionality:</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <button class="btn btn-primary w-100 mb-2" onclick="addSampleVessel()">
                                    <i class="fas fa-plus me-2"></i>Add Sample Vessel (Offline)
                                </button>
                                
                                <button class="btn btn-success w-100 mb-2" onclick="addSampleLog()">
                                    <i class="fas fa-clipboard-list me-2"></i>Add Sample Log (Offline)
                                </button>
                            </div>
                            
                            <div class="col-md-6">
                                <button class="btn btn-info w-100 mb-2" onclick="viewOfflineData()">
                                    <i class="fas fa-database me-2"></i>View Offline Data
                                </button>
                                
                                <button class="btn btn-warning w-100 mb-2" onclick="forceSync()">
                                    <i class="fas fa-sync-alt me-2"></i>Force Sync
                                </button>
                            </div>
                        </div>
                        
                        <div id="offline-demo-results" class="mt-3"></div>
                    </div>
                </div>
                
                <!-- Security Verification -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5><i class="fas fa-shield-alt text-success me-2"></i>Security Status</h5>
                        <p class="mb-0">
                            <strong>Logged in as:</strong> <?= htmlspecialchars($current_user['username']) ?> (<?= htmlspecialchars($current_user['role']) ?>)<br>
                            <strong>Company:</strong> <?= htmlspecialchars(COMPANY_NAME) ?> (<?= htmlspecialchars(COMPANY_DOMAIN) ?>)<br>
                            <strong>Page Access:</strong> <span class="text-success">✓ Authenticated</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/indexeddb.js"></script>
    <script src="assets/js/offline.js"></script>
    
    <script>
        // Offline demo functionality
        async function addSampleVessel() {
            try {
                const vesselData = {
                    id: 'temp_' + Date.now(),
                    name: 'MV Test Vessel ' + Math.floor(Math.random() * 1000),
                    type: 'Cargo',
                    description: 'Sample vessel created offline',
                    specifications: {
                        length: 150,
                        beam: 25,
                        draft: 8.5,
                        gross_tonnage: 5000
                    }
                };
                
                if (window.offlineManager) {
                    await window.offlineManager.addVessel(vesselData);
                    showDemoResult('Vessel added to offline storage!', 'success');
                } else {
                    showDemoResult('Offline manager not initialized', 'error');
                }
                
            } catch (error) {
                console.error('Error adding vessel:', error);
                showDemoResult('Error: ' + error.message, 'error');
            }
        }
        
        async function addSampleLog() {
            try {
                const logData = {
                    id: 'temp_log_' + Date.now(),
                    vessel_id: 1,
                    engine_id: 1,
                    timestamp: new Date().toISOString(),
                    temperature: 185 + Math.random() * 20,
                    pressure: 40 + Math.random() * 10,
                    hours: 1000 + Math.random() * 500,
                    notes: 'Sample log entry created offline'
                };
                
                if (window.offlineManager) {
                    await window.offlineManager.addLog(logData);
                    showDemoResult('Log entry added to offline storage!', 'success');
                } else {
                    showDemoResult('Offline manager not initialized', 'error');
                }
                
            } catch (error) {
                console.error('Error adding log:', error);
                showDemoResult('Error: ' + error.message, 'error');
            }
        }
        
        async function viewOfflineData() {
            try {
                if (!window.offlineManager || !window.offlineManager.db) {
                    showDemoResult('Offline manager not initialized', 'error');
                    return;
                }
                
                const vessels = await window.offlineManager.getVessels();
                const status = window.offlineManager.getConnectionStatus();
                
                let html = '<div class="alert alert-info">';
                html += '<h6>Offline Data Summary:</h6>';
                html += `<p><strong>Vessels:</strong> ${vessels.length}</p>`;
                html += `<p><strong>Connection:</strong> ${status.online ? 'Online' : 'Offline'}</p>`;
                html += `<p><strong>Pending Changes:</strong> ${status.pendingChanges}</p>`;
                
                if (vessels.length > 0) {
                    html += '<h6>Recent Vessels:</h6><ul>';
                    vessels.slice(0, 3).forEach(vessel => {
                        html += `<li>${vessel.name} (${vessel.type})</li>`;
                    });
                    html += '</ul>';
                }
                
                html += '</div>';
                
                showDemoResult(html, 'info', false);
                
            } catch (error) {
                console.error('Error viewing data:', error);
                showDemoResult('Error: ' + error.message, 'error');
            }
        }
        
        async function forceSync() {
            try {
                if (window.offlineManager) {
                    await window.offlineManager.forcSync();
                    showDemoResult('Sync initiated!', 'info');
                } else {
                    showDemoResult('Offline manager not initialized', 'error');
                }
                
            } catch (error) {
                console.error('Error syncing:', error);
                showDemoResult('Error: ' + error.message, 'error');
            }
        }
        
        function showDemoResult(message, type = 'info', isText = true) {
            const resultsDiv = document.getElementById('offline-demo-results');
            const alertClass = type === 'error' ? 'danger' : type;
            
            resultsDiv.innerHTML = `
                <div class="alert alert-${alertClass} alert-dismissible fade show">
                    ${isText ? escapeHtml(message) : message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                const alert = resultsDiv.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Initialize offline functionality when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a moment for offline manager to initialize
            setTimeout(() => {
                if (window.offlineManager) {
                    console.log('Offline functionality ready');
                    
                    // Set up event listeners for offline/online events
                    window.offlineManager.onOffline(() => {
                        document.getElementById('coming-soon-alert').classList.add('d-none');
                        showDemoResult('Now working offline - try the demo buttons!', 'warning');
                    });
                    
                    window.offlineManager.onOnline(() => {
                        showDemoResult('Back online - data will sync automatically!', 'success');
                    });
                } else {
                    console.warn('Offline manager not available');
                }
            }, 1000);
        });
    </script>
</body>
</html>
