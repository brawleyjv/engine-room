<?php
// Include weather widget for dashboard
require_once 'weather/weather_widget.php';

// Get weather summary for dashboard
$weather_summary = getDashboardWeather();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Wheelhouse Dashboard - Vessel Logger</title>
    <link rel="stylesheet" type="text/css" href="../css/styles.css">
    <style>
        .wh-dashboard { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .dashboard-header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .dashboard-title { margin: 0; font-size: 28px; display: flex; align-items: center; }
        .dashboard-title::before { content: "🧭"; margin-right: 15px; font-size: 32px; }
        .dashboard-subtitle { margin: 10px 0 0 0; opacity: 0.9; font-size: 16px; }
        
        .button-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .wh-button { 
            background: white; 
            border: none; 
            border-radius: 8px; 
            padding: 25px; 
            text-align: left; 
            cursor: pointer; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
            transition: all 0.3s ease; 
            text-decoration: none; 
            color: inherit; 
            display: block;
        }
        .wh-button:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.15); 
            background: #f8f9fa;
        }
        .wh-button-icon { font-size: 32px; margin-bottom: 15px; display: block; }
        .wh-button-title { font-size: 20px; font-weight: bold; color: #2c3e50; margin-bottom: 8px; }
        .wh-button-desc { color: #7f8c8d; font-size: 14px; line-height: 1.4; }
        
        .quick-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
        .stat-value { font-size: 24px; font-weight: bold; color: #3498db; }
        .stat-label { color: #7f8c8d; font-size: 14px; margin-top: 5px; }
        
        .nav-breadcrumb { margin-bottom: 20px; }
        .nav-breadcrumb a { color: #3498db; text-decoration: none; }
        .nav-breadcrumb a:hover { color: #2980b9; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="wh-dashboard">
        <div class="nav-breadcrumb">
            <a href="../main_menu.php">← Main Menu</a> / <strong>Wheelhouse</strong>
        </div>
        
        <div class="dashboard-header">
            <h1 class="dashboard-title">Wheelhouse Command Center</h1>
            <p class="dashboard-subtitle">Navigation, crew management, and position logging</p>
        </div>
        
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-value">--</div>
                <div class="stat-label">Current Position</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">--</div>
                <div class="stat-label">Last Log Entry</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">--</div>
                <div class="stat-label">Crew on Watch</div>
            </div>
            <a href="weather/index.php" class="stat-card weather-card" style="text-decoration: none; color: inherit; transition: transform 0.3s;">
                <div class="stat-value"><?php echo htmlspecialchars($weather_summary['temperature']); ?></div>
                <div class="stat-label"><?php echo htmlspecialchars($weather_summary['condition']); ?></div>
                <div class="weather-location"><?php echo htmlspecialchars($weather_summary['location']); ?></div>
            </a>
        </div>
        
        <div class="button-grid">
            <a href="crew/index.php" class="wh-button">
                <span class="wh-button-icon">👥</span>
                <div class="wh-button-title">Crew Management</div>
                <div class="wh-button-desc">Manage crew schedules, watch assignments, and personnel records</div>
            </a>
            
            <a href="log_entry/update_position.php" class="wh-button">
                <span class="wh-button-icon">📍</span>
                <div class="wh-button-title">Update Position</div>
                <div class="wh-button-desc">Record current position, course, speed, and navigation data</div>
            </a>
            
            <a href="log_entry/search.php" class="wh-button">
                <span class="wh-button-icon">🔍</span>
                <div class="wh-button-title">Search Logs</div>
                <div class="wh-button-desc">Search and review historical position logs and navigation records</div>
            </a>
            
            <a href="weather/index.php" class="wh-button">
                <span class="wh-button-icon">🌤️</span>
                <div class="wh-button-title">Marine Weather</div>
                <div class="wh-button-desc">Real-time weather conditions and marine forecasts from NOAA</div>
            </a>
            
            <a href="#" class="wh-button" style="opacity: 0.6; cursor: not-allowed;">
                <span class="wh-button-icon">⏰</span>
                <div class="wh-button-title">Watch Schedule</div>
                <div class="wh-button-desc">Manage watch rotations and duty assignments (Coming Soon)</div>
            </a>
            
            <a href="#" class="wh-button" style="opacity: 0.6; cursor: not-allowed;">
                <span class="wh-button-icon">📊</span>
                <div class="wh-button-title">Navigation Reports</div>
                <div class="wh-button-desc">Generate navigation and voyage reports (Coming Soon)</div>
            </a>
        </div>
    </div>
</body>
</html>
