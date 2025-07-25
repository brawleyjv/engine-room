<?php
require_once 'config.php';
require_once 'vessel_functions.php';

// Get parameters
$equipment_type = $_GET['equipment'] ?? '';
$side = $_GET['side'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Get vessel-specific scales
$vessel_scales = get_vessel_scales($conn);

// Validate parameters
if (empty($equipment_type) || empty($side) || !in_array($equipment_type, ['mainengines', 'generators', 'gears'])) {
    $error = 'Invalid parameters. Please select equipment type and side.';
} else {
    // Get data for graphing
    $sql = "SELECT * FROM $equipment_type WHERE Side = ?";
    $params = [$side];
    $types = 's';
    
    if (!empty($date_from)) {
        $sql .= " AND EntryDate >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    
    if (!empty($date_to)) {
        $sql .= " AND EntryDate <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
    
    $sql .= " ORDER BY EntryDate ASC, Timestamp ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
}

// Prepare data for JavaScript
$chart_data = [];
if (isset($data) && !empty($data)) {
    foreach ($data as $row) {
        $chart_data[] = [
            'date' => $row['EntryDate'],
            'timestamp' => $row['Timestamp'],
            'data' => $row
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Trends - <?= ucfirst($equipment_type) ?> (<?= $side ?>) - Vessel Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/date-fns@2.29.3/index.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@2.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <style>
        .chart-container {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .chart-wrapper {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }
        .chart-controls {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .chart-controls h3 {
            margin-top: 0;
            color: #333;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Equipment Trends: <?= ucfirst($equipment_type) ?> (<?= $side ?> Side)</h1>
            <p>
                <a href="view_logs.php?equipment=<?= $equipment_type ?>" class="btn btn-info">← Back to Logs</a>
                <a href="index.php" class="btn btn-primary">🏠 Home</a>
            </p>
        </header>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <?= $error ?>
                <br><br>
                <a href="view_logs.php">Go to View Logs</a> to select equipment and side.
            </div>
        <?php elseif (empty($data)): ?>
            <div class="error-message">
                No data found for <?= ucfirst($equipment_type) ?> (<?= $side ?> side) in the selected date range.
                <br><br>
                <a href="view_logs.php?equipment=<?= $equipment_type ?>">View all logs</a> or 
                <a href="add_log.php">add some data</a> first.
            </div>
        <?php else: ?>
            
            <!-- Date Range Info -->
            <div class="chart-controls">
                <h3>📅 Data Range</h3>
                <p>
                    <strong>Equipment:</strong> <?= ucfirst($equipment_type) ?> (<?= $side ?> side) | 
                    <strong>Records:</strong> <?= count($data) ?> entries |
                    <strong>Period:</strong> <?= $data[0]['EntryDate'] ?> to <?= end($data)['EntryDate'] ?>
                </p>
                
                <!-- Date filter form -->
                <form method="GET" style="display: inline-flex; gap: 10px; align-items: center; margin-top: 10px;">
                    <input type="hidden" name="equipment" value="<?= $equipment_type ?>">
                    <input type="hidden" name="side" value="<?= $side ?>">
                    
                    <label>From:</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" style="padding: 5px;">
                    
                    <label>To:</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" style="padding: 5px;">
                    
                    <button type="submit" class="btn btn-primary" style="padding: 5px 15px;">Update Range</button>
                    <a href="?equipment=<?= $equipment_type ?>&side=<?= $side ?>" class="btn btn-info" style="padding: 5px 15px;">Clear Filter</a>
                </form>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid" id="statsGrid">
                <!-- Statistics will be populated by JavaScript -->
            </div>

            <!-- Charts -->
            <div class="chart-container">
                <?php if ($equipment_type === 'mainengines'): ?>
                    <h2>Main Engine Performance Trends</h2>
                    
                    <div class="chart-wrapper">
                        <canvas id="combinedChart"></canvas>
                    </div>
                    
                <?php elseif ($equipment_type === 'generators'): ?>
                    <h2>Generator Performance Trends</h2>
                    
                    <div class="chart-wrapper">
                        <canvas id="combinedChart"></canvas>
                    </div>
                    
                <?php elseif ($equipment_type === 'gears'): ?>
                    <h2>Gear System Performance Trends</h2>
                    
                    <div class="chart-wrapper">
                        <canvas id="combinedChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <footer>
            <p>&copy; 2025 Vessel Data Logger | <a href="index.php">Home</a></p>
        </footer>
    </div>

    <?php if (!empty($data)): ?>
    <script>
        // Chart data from PHP
        const chartData = <?= json_encode($chart_data) ?>;
        const equipmentType = '<?= $equipment_type ?>';
        const vesselScales = <?= json_encode($vessel_scales) ?>;
        
        // Common chart configuration
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day',
                        displayFormats: {
                            day: 'MMM dd'
                        }
                    },
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    beginAtZero: false,
                    title: {
                        display: true,
                        text: 'Value'
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        };

        // Prepare data for charts
        const dates = chartData.map(item => item.date);
        
        // Color schemes for different metrics
        const colors = {
            hours: '#007bff',
            rpm: '#28a745',
            oilPressure: '#dc3545',
            oilTemp: '#fd7e14',
            fuelPress: '#6f42c1',
            waterTemp: '#20c997',
            temp: '#ffc107'
        };

        // Statistics calculation
        function calculateStats(data, field) {
            const values = data.filter(v => v != null && !isNaN(v));
            if (values.length === 0) return { min: 0, max: 0, avg: 0, latest: 0 };
            
            return {
                min: Math.min(...values),
                max: Math.max(...values),
                avg: Math.round(values.reduce((a, b) => a + b, 0) / values.length * 100) / 100,
                latest: values[values.length - 1]
            };
        }

        // Create statistics cards
        function createStatsCards() {
            const statsGrid = document.getElementById('statsGrid');
            let statsHTML = '';

            if (equipmentType === 'mainengines') {
                const rpmData = chartData.map(item => parseInt(item.data.RPM));
                const hoursData = chartData.map(item => parseInt(item.data.MainHrs));
                const oilPressData = chartData.map(item => parseInt(item.data.OilPressure));
                const oilTempData = chartData.map(item => parseInt(item.data.OilTemp));
                const fuelPressData = chartData.map(item => parseInt(item.data.FuelPress));
                const waterTempData = chartData.map(item => parseInt(item.data.WaterTemp));

                const rpmStats = calculateStats(rpmData, 'RPM');
                const hoursStats = calculateStats(hoursData, 'Hours');
                const oilPressStats = calculateStats(oilPressData, 'Oil Pressure');
                const oilTempStats = calculateStats(oilTempData, 'Oil Temp');
                const fuelPressStats = calculateStats(fuelPressData, 'Fuel Press');
                const waterTempStats = calculateStats(waterTempData, 'Water Temp');

                statsHTML = `
                    <div class="stat-card">
                        <div class="stat-value">${hoursStats.latest}</div>
                        <div class="stat-label">Current Hours</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${rpmStats.latest}</div>
                        <div class="stat-label">Latest RPM</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${oilPressStats.avg}</div>
                        <div class="stat-label">Avg Oil Pressure (PSI)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${oilTempStats.avg}°F</div>
                        <div class="stat-label">Avg Oil Temp</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${fuelPressStats.avg}</div>
                        <div class="stat-label">Avg Fuel Pressure (PSI)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${waterTempStats.avg}°F</div>
                        <div class="stat-label">Avg Water Temp</div>
                    </div>
                `;
            } else if (equipmentType === 'generators') {
                const hoursData = chartData.map(item => parseInt(item.data.GenHrs));
                const oilPressData = chartData.map(item => parseInt(item.data.OilPress));
                const fuelPressData = chartData.map(item => parseInt(item.data.FuelPress));
                const waterTempData = chartData.map(item => parseInt(item.data.WaterTemp));

                const hoursStats = calculateStats(hoursData, 'Hours');
                const oilPressStats = calculateStats(oilPressData, 'Oil Pressure');
                const fuelPressStats = calculateStats(fuelPressData, 'Fuel Press');
                const waterTempStats = calculateStats(waterTempData, 'Water Temp');

                statsHTML = `
                    <div class="stat-card">
                        <div class="stat-value">${hoursStats.latest}</div>
                        <div class="stat-label">Current Hours</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${oilPressStats.avg}</div>
                        <div class="stat-label">Avg Oil Pressure (PSI)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${fuelPressStats.avg}</div>
                        <div class="stat-label">Avg Fuel Pressure (PSI)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${waterTempStats.avg}°F</div>
                        <div class="stat-label">Avg Water Temp</div>
                    </div>
                `;
            } else if (equipmentType === 'gears') {
                const hoursData = chartData.map(item => parseInt(item.data.GearHrs));
                const oilPressData = chartData.map(item => parseInt(item.data.OilPress));
                const tempData = chartData.map(item => parseInt(item.data.Temp));

                const hoursStats = calculateStats(hoursData, 'Hours');
                const oilPressStats = calculateStats(oilPressData, 'Oil Pressure');
                const tempStats = calculateStats(tempData, 'Temperature');

                statsHTML = `
                    <div class="stat-card">
                        <div class="stat-value">${hoursStats.latest}</div>
                        <div class="stat-label">Current Hours</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${oilPressStats.avg}</div>
                        <div class="stat-label">Avg Oil Pressure (PSI)</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${tempStats.avg}°F</div>
                        <div class="stat-label">Avg Temperature</div>
                    </div>
                `;
            }

            statsGrid.innerHTML = statsHTML;
        }

        // Create charts based on equipment type
        if (equipmentType === 'mainengines') {
            // Filter out zero RPM values
            const filteredData = chartData.filter(item => item.data.RPM > 0);
            const filteredDates = filteredData.map(item => item.date);
            
            // Combined Main Engine Chart - All metrics on one chart with dual Y-axes
            new Chart(document.getElementById('combinedChart'), {
                type: 'line',
                data: {
                    labels: filteredDates,
                    datasets: [
                        {
                            label: 'RPM',
                            data: filteredData.map(item => item.data.RPM),
                            borderColor: colors.rpm,
                            backgroundColor: colors.rpm + '20',
                            tension: 0.1,
                            yAxisID: 'y-rpm'
                        },
                        {
                            label: 'Oil Pressure (PSI)',
                            data: filteredData.map(item => item.data.OilPressure),
                            borderColor: colors.oilPressure,
                            backgroundColor: colors.oilPressure + '20',
                            tension: 0.1,
                            yAxisID: 'y-right'
                        },
                        {
                            label: 'Oil Temperature (°F)',
                            data: filteredData.map(item => item.data.OilTemp),
                            borderColor: colors.oilTemp,
                            backgroundColor: colors.oilTemp + '20',
                            tension: 0.1,
                            yAxisID: 'y-right'
                        },
                        {
                            label: 'Fuel Pressure (PSI)',
                            data: filteredData.map(item => item.data.FuelPress),
                            borderColor: colors.fuelPress,
                            backgroundColor: colors.fuelPress + '20',
                            tension: 0.1,
                            yAxisID: 'y-right'
                        },
                        {
                            label: 'Water Temperature (°F)',
                            data: filteredData.map(item => item.data.WaterTemp),
                            borderColor: colors.waterTemp,
                            backgroundColor: colors.waterTemp + '20',
                            tension: 0.1,
                            yAxisID: 'y-right'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Main Engine Performance - All Metrics (<?= $side ?> Side)'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day',
                                displayFormats: {
                                    day: 'MMM dd'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        'y-rpm': {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            min: vesselScales.rpm_min,
                            max: vesselScales.rpm_max,
                            title: {
                                display: true,
                                text: 'RPM'
                            },
                            grid: {
                                drawOnChartArea: true,
                            },
                        },
                        'y-right': {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            min: vesselScales.temp_min,
                            max: vesselScales.temp_max,
                            title: {
                                display: true,
                                text: 'Temperature (°F) / Pressure (PSI)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });

        } else if (equipmentType === 'generators') {
            // Filter out zero oil pressure values
            const filteredData = chartData.filter(item => item.data.OilPress > 0);
            const filteredDates = filteredData.map(item => item.date);
            
            // Combined Generator Chart - All metrics on single scale
            new Chart(document.getElementById('combinedChart'), {
                type: 'line',
                data: {
                    labels: filteredDates,
                    datasets: [
                        {
                            label: 'Oil Pressure (PSI)',
                            data: filteredData.map(item => item.data.OilPress),
                            borderColor: colors.oilPressure,
                            backgroundColor: colors.oilPressure + '20',
                            tension: 0.1
                        },
                        {
                            label: 'Fuel Pressure (PSI)',
                            data: filteredData.map(item => item.data.FuelPress),
                            borderColor: colors.fuelPress,
                            backgroundColor: colors.fuelPress + '20',
                            tension: 0.1
                        },
                        {
                            label: 'Water Temperature (°F)',
                            data: filteredData.map(item => item.data.WaterTemp),
                            borderColor: colors.waterTemp,
                            backgroundColor: colors.waterTemp + '20',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        title: {
                            display: true,
                            text: 'Generator Performance - All Metrics (<?= $side ?> Side)'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day',
                                displayFormats: {
                                    day: 'MMM dd'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            min: vesselScales.gen_min,
                            max: vesselScales.gen_max,
                            title: {
                                display: true,
                                text: 'Temperature (°F) / Pressure (PSI)'
                            },
                            grid: {
                                drawOnChartArea: true,
                            },
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });

        } else if (equipmentType === 'gears') {
            // For gears, we need to fetch corresponding main engine RPM data
            // We'll make an AJAX call to get main engine data for the same side and dates
            
            fetch(`get_engine_data.php?side=<?= $side ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>`)
                .then(response => response.json())
                .then(engineData => {
                    console.log('Engine data received:', engineData);
                    console.log('Gear data available:', chartData);
                    
                    // Filter out zero oil pressure values first
                    const filteredGearData = chartData.filter(item => item.data.OilPress > 0);
                    console.log('Filtered gear data (OilPress > 0):', filteredGearData);
                    
                    // Create a map of engine data by date for easy lookup
                    const engineDataMap = {};
                    engineData.forEach(item => {
                        engineDataMap[item.date] = item.RPM;
                    });
                    
                    // Prepare combined data - include gear data points with oil pressure > 0
                    const combinedData = [];
                    filteredGearData.forEach(gearItem => {
                        const engineRPM = engineDataMap[gearItem.date] || null;
                        combinedData.push({
                            date: gearItem.date,
                            gearData: gearItem.data,
                            engineRPM: engineRPM
                        });
                    });
                    
                    console.log('Combined data:', combinedData);
                    
                    const finalDates = combinedData.map(item => item.date);
                    
                    // Create a combined chart with gear temp, pressure, and engine RPM
                    new Chart(document.getElementById('combinedChart'), {
                        type: 'line',
                        data: {
                            labels: finalDates,
                            datasets: [
                                {
                                    label: 'Engine RPM',
                                    data: combinedData.map(item => item.engineRPM),
                                    borderColor: colors.rpm,
                                    backgroundColor: colors.rpm + '20',
                                    tension: 0.1,
                                    yAxisID: 'y-rpm',
                                    spanGaps: true // Allow gaps when engine RPM is null
                                },
                                {
                                    label: 'Gear Oil Pressure (PSI)',
                                    data: combinedData.map(item => item.gearData.OilPress),
                                    borderColor: colors.oilPressure,
                                    backgroundColor: colors.oilPressure + '20',
                                    tension: 0.1,
                                    yAxisID: 'y-right'
                                },
                                {
                                    label: 'Gear Temperature (°F)',
                                    data: combinedData.map(item => item.gearData.Temp),
                                    borderColor: colors.temp,
                                    backgroundColor: colors.temp + '20',
                                    tension: 0.1,
                                    yAxisID: 'y-right'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: 'Gear Performance vs Engine RPM (<?= $side ?> Side)'
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                }
                            },
                            scales: {
                                x: {
                                    type: 'time',
                                    time: {
                                        unit: 'day',
                                        displayFormats: {
                                            day: 'MMM dd'
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Date'
                                    }
                                },
                                'y-rpm': {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    min: vesselScales.rpm_min,
                                    max: vesselScales.rpm_max,
                                    title: {
                                        display: true,
                                        text: 'Engine RPM'
                                    },
                                    grid: {
                                        drawOnChartArea: true,
                                    },
                                },
                                'y-right': {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    min: vesselScales.temp_min,
                                    max: vesselScales.temp_max,
                                    title: {
                                        display: true,
                                        text: 'Temperature (°F) / Pressure (PSI)'
                                    },
                                    grid: {
                                        drawOnChartArea: false,
                                    },
                                }
                            },
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error fetching engine data:', error);
                    
                    // Filter out zero oil pressure values for fallback too
                    const filteredGearData = chartData.filter(item => item.data.OilPress > 0);
                    const filteredDates = filteredGearData.map(item => item.date);
                    console.log('Using fallback: showing gear data only for', filteredGearData.length, 'entries');
                    
                    // Fallback: show gear data only
                    new Chart(document.getElementById('combinedChart'), {
                        type: 'line',
                        data: {
                            labels: filteredDates,
                            datasets: [
                                {
                                    label: 'Gear Oil Pressure (PSI)',
                                    data: filteredGearData.map(item => item.data.OilPress),
                                    borderColor: colors.oilPressure,
                                    backgroundColor: colors.oilPressure + '20',
                                    tension: 0.1
                                },
                                {
                                    label: 'Gear Temperature (°F)',
                                    data: filteredGearData.map(item => item.data.Temp),
                                    borderColor: colors.temp,
                                    backgroundColor: colors.temp + '20',
                                    tension: 0.1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: 'Gear Performance Trends (<?= $side ?> Side)'
                                },
                                tooltip: {
                                    mode: 'index',
                                    intersect: false,
                                }
                            },
                            scales: {
                                x: {
                                    type: 'time',
                                    time: {
                                        unit: 'day',
                                        displayFormats: {
                                            day: 'MMM dd'
                                        }
                                    },
                                    title: {
                                        display: true,
                                        text: 'Date'
                                    }
                                },
                                y: {
                                    type: 'linear',
                                    display: true,
                                    min: vesselScales.temp_min,
                                    max: vesselScales.temp_max,
                                    title: {
                                        display: true,
                                        text: 'Temperature (°F) / Pressure (PSI)'
                                    }
                                }
                            },
                            interaction: {
                                mode: 'nearest',
                                axis: 'x',
                                intersect: false
                            }
                        }
                    });
                });
        }

        // Create statistics cards
        createStatsCards();

    </script>
    <?php endif; ?>
</body>
</html>
