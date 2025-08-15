<?php
/**
 * Office Vessel Dashboard
 * Allows office staff to select vessels and view their synced data
 */

// Include database configuration
require_once 'config.php';

// Handle AJAX requests
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_GET['ajax'] === 'get_vessels') {
        // Get list of all vessels
        $result = $conn->query("SELECT id, vessel_name, hin, status, updated_at FROM vessels ORDER BY vessel_name");
        $vessels = [];
        
        while ($row = $result->fetch_assoc()) {
            $vessels[] = $row;
        }
        
        echo json_encode(['success' => true, 'vessels' => $vessels]);
        exit;
    }
    
    if ($_GET['ajax'] === 'get_vessel_data' && isset($_GET['vessel_id'])) {
        $vessel_id = intval($_GET['vessel_id']);
        
        // Get vessel info
        $vessel_stmt = $conn->prepare("SELECT * FROM vessels WHERE id = ?");
        $vessel_stmt->bind_param("i", $vessel_id);
        $vessel_stmt->execute();
        $vessel = $vessel_stmt->get_result()->fetch_assoc();
        $vessel_stmt->close();
        
        if (!$vessel) {
            echo json_encode(['success' => false, 'error' => 'Vessel not found']);
            exit;
        }
        
        // Get recent logs
        $logs_stmt = $conn->prepare("
            SELECT log_entry, logged_by, vessel_timestamp, created_at 
            FROM vessel_logs 
            WHERE vessel_id = ? 
            ORDER BY vessel_timestamp DESC, created_at DESC 
            LIMIT 20
        ");
        $logs_stmt->bind_param("i", $vessel_id);
        $logs_stmt->execute();
        $logs_result = $logs_stmt->get_result();
        $logs = [];
        while ($row = $logs_result->fetch_assoc()) {
            $logs[] = $row;
        }
        $logs_stmt->close();
        
        // Get current navigation data
        $nav_stmt = $conn->prepare("
            SELECT destination, eta, vessel_timestamp, updated_at 
            FROM navigation_data 
            WHERE vessel_id = ? 
            ORDER BY updated_at DESC 
            LIMIT 1
        ");
        $nav_stmt->bind_param("i", $vessel_id);
        $nav_stmt->execute();
        $navigation = $nav_stmt->get_result()->fetch_assoc();
        $nav_stmt->close();
        
        echo json_encode([
            'success' => true, 
            'vessel' => $vessel,
            'logs' => $logs,
            'navigation' => $navigation
        ]);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Office Vessel Dashboard</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2em;
        }
        .content {
            padding: 20px;
        }
        .vessel-selector {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        .vessel-selector h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        select, button {
            padding: 10px 15px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #2980b9;
        }
        .vessel-data {
            display: none;
        }
        .data-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: white;
        }
        .data-section h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
            color: #2c3e50;
        }
        .vessel-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            border-left: 3px solid #3498db;
        }
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }
        .info-value {
            color: #34495e;
            font-size: 1.1em;
        }
        .log-entry {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 3px solid #27ae60;
        }
        .log-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9em;
            color: #7f8c8d;
        }
        .log-content {
            color: #2c3e50;
            line-height: 1.5;
        }
        .no-data {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
            font-style: italic;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #3498db;
        }
        .navigation-info {
            background: #e8f5e8;
            border-left-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚢 Office Vessel Dashboard</h1>
            <p>Monitor vessel logs and navigation data from all connected vessels</p>
        </div>
        
        <div class="content">
            <div class="vessel-selector">
                <h3>Select Vessel</h3>
                <select id="vesselSelect">
                    <option value="">Loading vessels...</option>
                </select>
                <button onclick="loadVesselData()">View Vessel Data</button>
                <button onclick="refreshVessels()">Refresh Vessels</button>
            </div>
            
            <div id="vesselData" class="vessel-data">
                <div class="data-section">
                    <h3>📋 Vessel Information</h3>
                    <div id="vesselInfo" class="vessel-info"></div>
                </div>
                
                <div class="data-section">
                    <h3>🧭 Current Navigation</h3>
                    <div id="navigationData"></div>
                </div>
                
                <div class="data-section">
                    <h3>📝 Recent Vessel Logs</h3>
                    <div id="vesselLogs"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load vessels on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadVessels();
        });

        function loadVessels() {
            fetch('?ajax=get_vessels')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('vesselSelect');
                    select.innerHTML = '<option value="">Select a vessel...</option>';
                    
                    if (data.success && data.vessels.length > 0) {
                        data.vessels.forEach(vessel => {
                            const option = document.createElement('option');
                            option.value = vessel.id;
                            option.textContent = `${vessel.vessel_name} ${vessel.hin ? '(' + vessel.hin + ')' : ''}`;
                            select.appendChild(option);
                        });
                    } else {
                        select.innerHTML = '<option value="">No vessels found</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading vessels:', error);
                    document.getElementById('vesselSelect').innerHTML = '<option value="">Error loading vessels</option>';
                });
        }

        function refreshVessels() {
            loadVessels();
        }

        function loadVesselData() {
            const vesselId = document.getElementById('vesselSelect').value;
            const dataDiv = document.getElementById('vesselData');
            
            if (!vesselId) {
                alert('Please select a vessel first');
                return;
            }
            
            // Show loading
            dataDiv.style.display = 'block';
            document.getElementById('vesselInfo').innerHTML = '<div class="loading">Loading vessel data...</div>';
            document.getElementById('navigationData').innerHTML = '<div class="loading">Loading navigation data...</div>';
            document.getElementById('vesselLogs').innerHTML = '<div class="loading">Loading logs...</div>';
            
            fetch(`?ajax=get_vessel_data&vessel_id=${vesselId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayVesselInfo(data.vessel);
                        displayNavigationData(data.navigation);
                        displayVesselLogs(data.logs);
                    } else {
                        alert('Error loading vessel data: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading vessel data:', error);
                    alert('Error loading vessel data');
                });
        }

        function displayVesselInfo(vessel) {
            const html = `
                <div class="info-item">
                    <span class="info-label">Vessel Name</span>
                    <span class="info-value">${vessel.vessel_name}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">HIN</span>
                    <span class="info-value">${vessel.hin || 'Not specified'}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="info-value">${vessel.status}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Last Updated</span>
                    <span class="info-value">${new Date(vessel.updated_at).toLocaleString()}</span>
                </div>
            `;
            document.getElementById('vesselInfo').innerHTML = html;
        }

        function displayNavigationData(navigation) {
            if (navigation) {
                const html = `
                    <div class="info-item navigation-info">
                        <span class="info-label">Current Destination</span>
                        <span class="info-value">${navigation.destination || 'Not specified'}</span>
                    </div>
                    <div class="info-item navigation-info">
                        <span class="info-label">ETA</span>
                        <span class="info-value">${navigation.eta ? new Date(navigation.eta).toLocaleString() : 'Not specified'}</span>
                    </div>
                    <div class="info-item navigation-info">
                        <span class="info-label">Last Updated</span>
                        <span class="info-value">${new Date(navigation.updated_at).toLocaleString()}</span>
                    </div>
                `;
                document.getElementById('navigationData').innerHTML = html;
            } else {
                document.getElementById('navigationData').innerHTML = '<div class="no-data">No navigation data available</div>';
            }
        }

        function displayVesselLogs(logs) {
            if (logs && logs.length > 0) {
                const html = logs.map(log => `
                    <div class="log-entry">
                        <div class="log-header">
                            <span><strong>By:</strong> ${log.logged_by}</span>
                            <span><strong>Time:</strong> ${new Date(log.vessel_timestamp || log.created_at).toLocaleString()}</span>
                        </div>
                        <div class="log-content">${log.log_entry}</div>
                    </div>
                `).join('');
                document.getElementById('vesselLogs').innerHTML = html;
            } else {
                document.getElementById('vesselLogs').innerHTML = '<div class="no-data">No logs available</div>';
            }
        }
    </script>
</body>
</html>
