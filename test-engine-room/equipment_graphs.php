<?php
/**
 * Equipment Performance Graphs - Marine Engine Room
 * Historical trend analysis with interactive charts
 */

require_once 'test_db.php';
require_once 'settings_helper.php';

$message = '';
$error = '';

// Get vessel settings for scale ranges
$pdo = getTestDatabase();
$vessel_settings = getVesselSettings($pdo);

// Equipment types and their parameters
$equipment_params = [
    'engine' => [
        'rpm_params' => ['rpm'],
        'pressure_params' => ['oil_pressure', 'fuel_pressure', 'turbo_oil_pressure', 'governor_air_pressure', 
                             'aftercooler_water_pressure', 'lube_oil_filter_pressure_in', 'lube_oil_filter_pressure_out', 
                             'air_box_pressure', 'ship_air_pressure'],
        'temp_params' => ['water_temp_in', 'water_temp_out', 'oil_temp_in', 'oil_temp_out', 'aftercooler_water_temp_out'],
        'other_params' => ['crankcase_vacuum']
    ],
    'generator' => [
        'rpm_params' => ['rpm'],
        'pressure_params' => ['lube_oil_pressure', 'fuel_pressure'],
        'temp_params' => ['water_temp_in', 'water_temp_out'],
        'electrical_params' => ['battery_voltage', 'voltage_out', 'frequency_hz', 'amperage']
    ]
];

// Get scale ranges from settings (with defaults)
$scale_ranges = [
    'rpm_min' => floatval($vessel_settings['graph_rpm_min'] ?? 0),
    'rpm_max' => floatval($vessel_settings['graph_rpm_max'] ?? 2000),
    'pressure_min' => floatval($vessel_settings['graph_pressure_min'] ?? 0),
    'pressure_max' => floatval($vessel_settings['graph_pressure_max'] ?? 100),
    'temp_min' => floatval($vessel_settings['graph_temp_min'] ?? 100),
    'temp_max' => floatval($vessel_settings['graph_temp_max'] ?? 300)
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Performance Graphs - Marine Engine Room</title>
    <link rel="stylesheet" href="test_styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.0/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
</head>
<body>
    <div class="header">
        <h1>📊 Equipment Performance Graphs</h1>
        <p>Historical trend analysis and performance monitoring</p>
        <a href="index.php" style="color: white; text-decoration: none;">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Graph Controls -->
        <div class="form-section">
            <h3>📈 Graph Configuration</h3>
            
            <div class="form-grid" style="grid-template-columns: 200px 200px 200px 200px 1fr;">
                <div class="form-group">
                    <label>Equipment Type:</label>
                    <select id="equipment_type" onchange="updateEquipmentList()">
                        <option value="">Select Type...</option>
                        <option value="engine">Main Engine</option>
                        <option value="generator">Generator</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Equipment:</label>
                    <select id="equipment_id">
                        <option value="">Select Equipment...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Date Range:</label>
                    <select id="date_range" onchange="toggleCustomDates()">
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                        <option value="365">Last year</option>
                        <option value="all">All time</option>
                        <option value="custom">Custom range</option>
                    </select>
                </div>
                
                <div class="form-group" id="custom_dates" style="display: none;">
                    <label>From:</label>
                    <input type="date" id="date_from">
                    <label>To:</label>
                    <input type="date" id="date_to">
                </div>
                
                <div class="form-group">
                    <button onclick="loadGraph()" class="btn btn-primary">📊 Load Graph</button>
                    <button onclick="showScaleSettings()" class="btn btn-secondary">⚙️ Scale Settings</button>
                </div>
            </div>
        </div>

        <!-- Parameter Selection -->
        <div class="form-section" id="parameter_section" style="display: none;">
            <h3>📊 Parameter Selection</h3>
            <p>Select which parameters to display on the graph:</p>
            
            <div id="parameter_checkboxes" class="parameter-grid">
                <!-- Dynamically populated based on equipment type -->
            </div>
        </div>

        <!-- Graph Display -->
        <div class="form-section" id="graph_section" style="display: none;">
            <div class="graph-header">
                <h3 id="graph_title">Equipment Performance Over Time</h3>
                <div class="graph-info">
                    <span id="data_points_info"></span>
                    <span id="date_range_info"></span>
                </div>
            </div>
            
            <div class="chart-container" style="position: relative; height: 600px; margin: 20px 0;">
                <canvas id="performance_chart"></canvas>
            </div>
            
            <div class="chart-legend" id="chart_legend">
                <!-- Legend will be populated by JavaScript -->
            </div>
        </div>

        <!-- Scale Settings Modal (will be shown/hidden) -->
        <div id="scale_settings" class="modal" style="display: none;">
            <div class="modal-content">
                <h3>⚙️ Graph Scale Settings</h3>
                <p>Configure Y-axis ranges for different equipment types:</p>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>RPM Range:</label>
                        <input type="number" id="rpm_min" value="<?php echo $scale_ranges['rpm_min']; ?>" placeholder="Min RPM">
                        <input type="number" id="rpm_max" value="<?php echo $scale_ranges['rpm_max']; ?>" placeholder="Max RPM">
                    </div>
                    
                    <div class="form-group">
                        <label>Pressure Range (PSI):</label>
                        <input type="number" id="pressure_min" value="<?php echo $scale_ranges['pressure_min']; ?>" placeholder="Min PSI">
                        <input type="number" id="pressure_max" value="<?php echo $scale_ranges['pressure_max']; ?>" placeholder="Max PSI">
                    </div>
                    
                    <div class="form-group">
                        <label>Temperature Range (°F):</label>
                        <input type="number" id="temp_min" value="<?php echo $scale_ranges['temp_min']; ?>" placeholder="Min °F">
                        <input type="number" id="temp_max" value="<?php echo $scale_ranges['temp_max']; ?>" placeholder="Max °F">
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <button onclick="saveScaleSettings()" class="btn btn-success">💾 Save Settings</button>
                    <button onclick="hideScaleSettings()" class="btn btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Equipment configuration
        const equipmentConfig = <?php echo json_encode($equipment_params); ?>;
        const scaleRanges = <?php echo json_encode($scale_ranges); ?>;
        
        let performanceChart = null;
        
        // Update equipment list based on type selection
        function updateEquipmentList() {
            const equipmentType = document.getElementById('equipment_type').value;
            const equipmentSelect = document.getElementById('equipment_id');
            
            equipmentSelect.innerHTML = '<option value="">Select Equipment...</option>';
            
            if (equipmentType === 'engine') {
                equipmentSelect.innerHTML += '<option value="port_main">Port Main Engine</option>';
                equipmentSelect.innerHTML += '<option value="center_main">Center Main Engine</option>';
                equipmentSelect.innerHTML += '<option value="starboard_main">Starboard Main Engine</option>';
            } else if (equipmentType === 'generator') {
                equipmentSelect.innerHTML += '<option value="port_gen">Port Generator</option>';
                equipmentSelect.innerHTML += '<option value="center_gen">Center Generator</option>';
                equipmentSelect.innerHTML += '<option value="starboard_gen">Starboard Generator</option>';
            }
            
            updateParameterCheckboxes();
        }
        
        // Update parameter checkboxes based on equipment type
        function updateParameterCheckboxes() {
            const equipmentType = document.getElementById('equipment_type').value;
            const container = document.getElementById('parameter_checkboxes');
            
            if (!equipmentType) {
                container.innerHTML = '';
                document.getElementById('parameter_section').style.display = 'none';
                return;
            }
            
            document.getElementById('parameter_section').style.display = 'block';
            container.innerHTML = '';
            
            const params = equipmentConfig[equipmentType];
            
            // Add parameter groups
            Object.keys(params).forEach(group => {
                const groupDiv = document.createElement('div');
                groupDiv.className = 'parameter-group';
                
                const groupTitle = document.createElement('h4');
                groupTitle.textContent = group.replace('_', ' ').toUpperCase();
                groupDiv.appendChild(groupTitle);
                
                params[group].forEach(param => {
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.id = 'param_' + param;
                    checkbox.value = param;
                    checkbox.checked = true;
                    
                    const label = document.createElement('label');
                    label.htmlFor = checkbox.id;
                    label.textContent = formatParameterName(param);
                    
                    const div = document.createElement('div');
                    div.className = 'checkbox-item';
                    div.appendChild(checkbox);
                    div.appendChild(label);
                    
                    groupDiv.appendChild(div);
                });
                
                container.appendChild(groupDiv);
            });
        }
        
        // Format parameter names for display
        function formatParameterName(param) {
            return param.replace(/_/g, ' ')
                       .replace(/\b\w/g, l => l.toUpperCase());
        }
        
        // Toggle custom date inputs
        function toggleCustomDates() {
            const dateRange = document.getElementById('date_range').value;
            const customDates = document.getElementById('custom_dates');
            
            if (dateRange === 'custom') {
                customDates.style.display = 'block';
                document.getElementById('date_to').value = new Date().toISOString().split('T')[0];
                const fromDate = new Date();
                fromDate.setDate(fromDate.getDate() - 30);
                document.getElementById('date_from').value = fromDate.toISOString().split('T')[0];
            } else {
                customDates.style.display = 'none';
            }
        }
        
        // Show/hide scale settings
        function showScaleSettings() {
            document.getElementById('scale_settings').style.display = 'flex';
        }
        
        function hideScaleSettings() {
            document.getElementById('scale_settings').style.display = 'none';
        }
        
        // Save scale settings
        async function saveScaleSettings() {
            const settings = {
                rpm_min: document.getElementById('rpm_min').value,
                rpm_max: document.getElementById('rpm_max').value,
                pressure_min: document.getElementById('pressure_min').value,
                pressure_max: document.getElementById('pressure_max').value,
                temp_min: document.getElementById('temp_min').value,
                temp_max: document.getElementById('temp_max').value
            };
            
            try {
                const response = await fetch('graph_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'save_scale_settings',
                        settings: settings
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Scale settings saved successfully!');
                    hideScaleSettings();
                    // Update local scale ranges
                    Object.assign(scaleRanges, settings);
                    // Reload graph if it's currently displayed
                    if (performanceChart) {
                        loadGraph();
                    }
                } else {
                    alert('Error saving settings: ' + result.error);
                }
            } catch (error) {
                alert('Error saving settings: ' + error.message);
            }
        }
        
        // Load and display graph
        async function loadGraph() {
            const equipmentType = document.getElementById('equipment_type').value;
            const equipmentId = document.getElementById('equipment_id').value;
            const dateRange = document.getElementById('date_range').value;
            
            if (!equipmentType || !equipmentId) {
                alert('Please select equipment type and equipment.');
                return;
            }
            
            // Get selected parameters
            const selectedParams = [];
            const checkboxes = document.querySelectorAll('#parameter_checkboxes input[type="checkbox"]:checked');
            checkboxes.forEach(cb => selectedParams.push(cb.value));
            
            if (selectedParams.length === 0) {
                alert('Please select at least one parameter to display.');
                return;
            }
            
            // Prepare date range
            let dateFrom = null, dateTo = null;
            if (dateRange === 'custom') {
                dateFrom = document.getElementById('date_from').value;
                dateTo = document.getElementById('date_to').value;
            }
            
            // Show loading indicator
            document.getElementById('graph_section').style.display = 'block';
            document.getElementById('graph_title').textContent = 'Loading performance data...';
            
            try {
                const response = await fetch('graph_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'get_graph_data',
                        equipment_type: equipmentType,
                        equipment_id: equipmentId,
                        date_range: dateRange,
                        date_from: dateFrom,
                        date_to: dateTo,
                        parameters: selectedParams
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    renderChart(data.data, equipmentType, equipmentId, selectedParams);
                } else {
                    alert('Error loading data: ' + data.error);
                }
            } catch (error) {
                alert('Error loading data: ' + error.message);
            }
        }
        
        // Render the chart
        function renderChart(data, equipmentType, equipmentId, selectedParams) {
            const ctx = document.getElementById('performance_chart').getContext('2d');
            
            // Destroy existing chart
            if (performanceChart) {
                performanceChart.destroy();
            }
            
            // Update title and info
            document.getElementById('graph_title').textContent = 
                `${equipmentId.replace('_', ' ').toUpperCase()} ${equipmentType.toUpperCase()} Performance`;
            document.getElementById('data_points_info').textContent = 
                `${data.datasets[0]?.data?.length || 0} data points`;
            document.getElementById('date_range_info').textContent = 
                data.date_range || '';
            
            // Create chart
            performanceChart = new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: `${equipmentId.replace('_', ' ').toUpperCase()} Performance Trends`
                        },
                        legend: {
                            display: true,
                            onClick: function(e, legendItem) {
                                const index = legendItem.datasetIndex;
                                const chart = this.chart;
                                const meta = chart.getDatasetMeta(index);
                                meta.hidden = !meta.hidden;
                                chart.update();
                            }
                        },
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItems) {
                                    return new Date(tooltipItems[0].parsed.x).toLocaleString();
                                },
                                label: function(tooltipItem) {
                                    const datasetLabel = tooltipItem.dataset.label;
                                    const value = tooltipItem.parsed.y;
                                    const unit = getParameterUnit(datasetLabel);
                                    return `${datasetLabel}: ${value}${unit}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                displayFormats: {
                                    hour: 'MMM dd HH:mm',
                                    day: 'MMM dd',
                                    week: 'MMM dd',
                                    month: 'MMM yyyy'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Date/Time'
                            }
                        },
                        'y-rpm': {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            min: scaleRanges.rpm_min,
                            max: scaleRanges.rpm_max,
                            title: {
                                display: true,
                                text: 'RPM'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        },
                        'y-other': {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            min: Math.min(scaleRanges.pressure_min, scaleRanges.temp_min),
                            max: Math.max(scaleRanges.pressure_max, scaleRanges.temp_max),
                            title: {
                                display: true,
                                text: 'Pressure (PSI) / Temperature (°F)'
                            },
                            grid: {
                                drawOnChartArea: true,
                            },
                        }
                    }
                }
            });
        }
        
        // Get unit for parameter
        function getParameterUnit(paramName) {
            const lower = paramName.toLowerCase();
            if (lower.includes('rpm')) return ' RPM';
            if (lower.includes('pressure')) return ' PSI';
            if (lower.includes('temp')) return '°F';
            if (lower.includes('voltage')) return 'V';
            if (lower.includes('amperage')) return 'A';
            if (lower.includes('frequency')) return 'Hz';
            if (lower.includes('vacuum')) return ' inHg';
            return '';
        }
    </script>

    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
        }
        
        .parameter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .parameter-group h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        
        .checkbox-item {
            margin: 8px 0;
        }
        
        .checkbox-item input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .graph-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .graph-info {
            font-size: 14px;
            color: #666;
        }
        
        .graph-info span {
            margin-left: 20px;
        }
        
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</body>
</html>
