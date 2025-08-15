<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vessel Data Office Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .vessel-selector {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #ecf0f1;
            border-radius: 5px;
        }
        .vessel-selector label {
            font-weight: bold;
            margin-right: 10px;
        }
        .vessel-selector select {
            padding: 8px 12px;
            border-radius: 4px;
            border: 1px solid #bdc3c7;
            font-size: 16px;
        }
        .data-section {
            margin-bottom: 30px;
        }
        .data-section h2 {
            color: #34495e;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .vessel-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        .info-card strong {
            display: block;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background-color: #3498db;
            color: white;
        }
        .data-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .no-data {
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
            padding: 20px;
        }
        .refresh-btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-left: 10px;
        }
        .refresh-btn:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚢 Vessel Data Office Dashboard</h1>
        
        <div class="vessel-selector">
            <label for="vesselSelect">Select Vessel:</label>
            <select id="vesselSelect" onchange="loadVesselData()">
                <option value="">-- Select a Vessel --</option>
            </select>
            <button class="refresh-btn" onclick="loadVessels()">🔄 Refresh</button>
        </div>

        <div id="vesselData" style="display: none;">
            <!-- Vessel Information -->
            <div class="data-section">
                <h2>Vessel Information</h2>
                <div class="vessel-info" id="vesselInfo">
                    <!-- Vessel info will be populated here -->
                </div>
            </div>

            <!-- Current Navigation -->
            <div class="data-section">
                <h2>Current Navigation</h2>
                <div id="navigationData">
                    <!-- Navigation data will be populated here -->
                </div>
            </div>

            <!-- Recent Vessel Logs -->
            <div class="data-section">
                <h2>Recent Vessel Logs</h2>
                <div id="vesselLogs">
                    <!-- Logs will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // Load vessels on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadVessels();
        });

        async function loadVessels() {
            try {
                const response = await fetch('vessel_data_api.php?action=get_vessels');
                const data = await response.json();
                
                const select = document.getElementById('vesselSelect');
                select.innerHTML = '<option value="">-- Select a Vessel --</option>';
                
                if (data.success && data.vessels) {
                    data.vessels.forEach(vessel => {
                        const option = document.createElement('option');
                        option.value = vessel.id;
                        option.textContent = `${vessel.vessel_name} ${vessel.hin ? '(HIN: ' + vessel.hin + ')' : ''}`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading vessels:', error);
                alert('Failed to load vessels');
            }
        }

        async function loadVesselData() {
            const vesselId = document.getElementById('vesselSelect').value;
            const vesselDataDiv = document.getElementById('vesselData');
            
            if (!vesselId) {
                vesselDataDiv.style.display = 'none';
                return;
            }

            try {
                // Load vessel info
                const vesselResponse = await fetch(`vessel_data_api.php?action=get_vessel&id=${vesselId}`);
                const vesselData = await vesselResponse.json();
                
                if (vesselData.success) {
                    displayVesselInfo(vesselData.vessel);
                    
                    // Load navigation data
                    const navResponse = await fetch(`vessel_data_api.php?action=get_navigation&vessel_id=${vesselId}`);
                    const navData = await navResponse.json();
                    displayNavigationData(navData.navigation || null);
                    
                    // Load recent logs
                    const logsResponse = await fetch(`vessel_data_api.php?action=get_logs&vessel_id=${vesselId}`);
                    const logsData = await logsResponse.json();
                    displayVesselLogs(logsData.logs || []);
                    
                    vesselDataDiv.style.display = 'block';
                } else {
                    alert('Failed to load vessel data: ' + vesselData.error);
                }
            } catch (error) {
                console.error('Error loading vessel data:', error);
                alert('Failed to load vessel data');
            }
        }

        function displayVesselInfo(vessel) {
            const infoDiv = document.getElementById('vesselInfo');
            infoDiv.innerHTML = `
                <div class="info-card">
                    <strong>Vessel Name</strong>
                    ${vessel.vessel_name}
                </div>
                <div class="info-card">
                    <strong>HIN</strong>
                    ${vessel.hin || 'Not specified'}
                </div>
                <div class="info-card">
                    <strong>Status</strong>
                    ${vessel.status}
                </div>
                <div class="info-card">
                    <strong>Last Updated</strong>
                    ${new Date(vessel.updated_at).toLocaleString()}
                </div>
            `;
        }

        function displayNavigationData(navigation) {
            const navDiv = document.getElementById('navigationData');
            
            if (navigation) {
                navDiv.innerHTML = `
                    <div class="vessel-info">
                        <div class="info-card">
                            <strong>Destination</strong>
                            ${navigation.destination || 'Not set'}
                        </div>
                        <div class="info-card">
                            <strong>ETA</strong>
                            ${navigation.eta ? new Date(navigation.eta).toLocaleString() : 'Not set'}
                        </div>
                        <div class="info-card">
                            <strong>Last Updated</strong>
                            ${new Date(navigation.vessel_timestamp).toLocaleString()}
                        </div>
                    </div>
                `;
            } else {
                navDiv.innerHTML = '<div class="no-data">No navigation data available</div>';
            }
        }

        function displayVesselLogs(logs) {
            const logsDiv = document.getElementById('vesselLogs');
            
            if (logs.length > 0) {
                let tableHTML = `
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Logged By</th>
                                <th>Log Entry</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                logs.forEach(log => {
                    tableHTML += `
                        <tr>
                            <td>${new Date(log.vessel_timestamp).toLocaleString()}</td>
                            <td>${log.logged_by}</td>
                            <td>${log.log_entry}</td>
                        </tr>
                    `;
                });
                
                tableHTML += '</tbody></table>';
                logsDiv.innerHTML = tableHTML;
            } else {
                logsDiv.innerHTML = '<div class="no-data">No log entries available</div>';
            }
        }
    </script>
</body>
</html>
