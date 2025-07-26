<?php
require_once 'config.php';
require_once 'vessel_functions.php';
require_once 'auth_functions.php';

// Get current user if logged in
$current_user = is_logged_in() ? get_logged_in_user() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚢 Vessel Data Logger</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>🚢 Vessel Data Logger</h1>
                    <p>Engine Room Equipment Management System</p>
                </div>
                <div style="text-align: right;">
                    <?php if ($current_user): ?>
                        <div style="color: #666; font-size: 14px; margin-bottom: 5px;">
                            Welcome, <strong><?= htmlspecialchars($current_user['full_name']) ?></strong>
                        </div>
                        <a href="logout.php" class="btn btn-secondary" style="font-size: 12px;">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary" style="font-size: 14px;">🔑 Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>
        
        <?php echo render_vessel_selector($conn, 'index'); ?>
        
        <?php if (!$current_user): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0; text-align: center;">
                <h3 style="color: #856404; margin: 0 0 10px 0;">📋 View-Only Mode</h3>
                <p style="color: #856404; margin: 0 0 15px 0;">You can view logs and graphs, but need to log in to add new entries or manage vessels.</p>
                <a href="login.php" class="btn btn-primary">🔑 Login to Add Entries</a>
            </div>
        <?php endif; ?>

        <nav class="main-nav">
            <div class="nav-card">
                <h3>📊 View Data</h3>
                <p>Search and view equipment logs</p>
                <a href="view_logs.php" class="btn btn-primary">View Logs</a>
            </div>
            
            <div class="nav-card">
                <h3>➕ Add Entry</h3>
                <p>Record new equipment readings</p>
                <?php if ($current_user): ?>
                    <a href="add_log.php" class="btn btn-success">Add Log Entry</a>
                <?php else: ?>
                    <a href="login.php?redirect=add_log.php" class="btn btn-secondary">Login to Add Entries</a>
                <?php endif; ?>
            </div>
            
            <div class="nav-card">
                <h3>📈 Trend Graphs</h3>
                <p>Visual performance trends</p>
                <a href="view_logs.php" class="btn btn-warning">View Graphs</a>
            </div>
            
            <div class="nav-card">
                <h3>🚢 Vessel Management</h3>
                <p>Add and manage vessels</p>
                <?php if ($current_user && $current_user['is_admin']): ?>
                    <a href="manage_vessels.php" class="btn btn-secondary">Manage Vessels</a>
                <?php elseif ($current_user): ?>
                    <a href="manage_vessels.php" class="btn btn-info">View Vessels</a>
                <?php else: ?>
                    <a href="login.php?redirect=manage_vessels.php" class="btn btn-secondary">Login to Manage</a>
                <?php endif; ?>
            </div>
            
            <div class="nav-card">
                <h3>⚙️ Equipment Status</h3>
                <p>Current equipment overview</p>
                <a href="dashboard.php" class="btn btn-info">Dashboard</a>
            </div>
            
            <?php if ($current_user && $current_user['is_admin']): ?>
            <div class="nav-card">
                <h3>👥 User Management</h3>
                <p>Add and manage user accounts</p>
                <a href="manage_users.php" class="btn btn-primary">Manage Users</a>
            </div>
            <?php endif; ?>
        </nav>
        
        <div class="equipment-overview">
            <h2>Equipment Types</h2>
            <div class="equipment-grid">
                <div class="equipment-item">
                    <h4>🔧 Main Engines</h4>
                    <ul>
                        <li>RPM Monitoring</li>
                        <li>Main Hours</li>
                        <li>Oil Pressure (PSI) & Temperature (°F)</li>
                        <li>Fuel Pressure (PSI)</li>
                        <li>Water Temperature (°F)</li>
                    </ul>
                </div>
                
                <div class="equipment-item">
                    <h4>⚡ Generators</h4>
                    <ul>
                        <li>Generator Hours</li>
                        <li>Oil Pressure (PSI)</li>
                        <li>Fuel Pressure (PSI)</li>
                        <li>Water Temperature (°F)</li>
                    </ul>
                </div>
                
                <div class="equipment-item">
                    <h4>⚙️ Gears</h4>
                    <ul>
                        <li>Gear Hours</li>
                        <li>Oil Pressure (PSI)</li>
                        <li>Temperature (°F)</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <footer>
            <p>&copy; 2025 Vessel Data Logger | Engine Room Management</p>
        </footer>
    </div>
</body>
</html>
