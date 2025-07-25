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
            <h1>🚢 Vessel Data Logger</h1>
            <p>Engine Room Equipment Management System</p>
        </header>
        
        <nav class="main-nav">
            <div class="nav-card">
                <h3>📊 View Data</h3>
                <p>Search and view equipment logs</p>
                <a href="view_logs.php" class="btn btn-primary">View Logs</a>
            </div>
            
            <div class="nav-card">
                <h3>➕ Add Entry</h3>
                <p>Record new equipment readings</p>
                <a href="add_log.php" class="btn btn-success">Add Log Entry</a>
            </div>
            
            <div class="nav-card">
                <h3>📈 Trend Graphs</h3>
                <p>Visual performance trends</p>
                <a href="view_logs.php" class="btn btn-warning">Select Equipment for Graphs</a>
            </div>
            
            <div class="nav-card">
                <h3>⚙️ Equipment Status</h3>
                <p>Current equipment overview</p>
                <a href="dashboard.php" class="btn btn-info">Dashboard</a>
            </div>
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
