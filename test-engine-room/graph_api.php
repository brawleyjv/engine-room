<?php
/**
 * Graph API - Data provider for equipment performance graphs
 * Handles data requests, settings, and chart data formatting
 */

require_once 'test_db.php';
require_once 'settings_helper.php';

header('Content-Type: application/json');

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$action = $input['action'] ?? '';
$pdo = getTestDatabase();

try {
    switch ($action) {
        case 'get_graph_data':
            echo json_encode(getGraphData($pdo, $input));
            break;
            
        case 'save_scale_settings':
            echo json_encode(saveScaleSettings($pdo, $input['settings']));
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Get graph data for specified equipment and parameters
 */
function getGraphData($pdo, $input) {
    $equipmentType = $input['equipment_type'];
    $equipmentId = $input['equipment_id'];
    $dateRange = $input['date_range'];
    $dateFrom = $input['date_from'] ?? null;
    $dateTo = $input['date_to'] ?? null;
    $parameters = $input['parameters'] ?? [];
    
    if (empty($parameters)) {
        return ['success' => false, 'error' => 'No parameters selected'];
    }
    
    // Determine table name
    $tableName = ($equipmentType === 'engine') ? 'engine_readings' : 'generator_readings';
    
    // Build date condition
    $dateCondition = '';
    $dateParams = [];
    
    if ($dateRange === 'custom' && $dateFrom && $dateTo) {
        $dateCondition = 'AND reading_date BETWEEN ? AND ?';
        $dateParams = [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'];
    } elseif (is_numeric($dateRange) && $dateRange > 0) {
        $dateCondition = 'AND reading_date >= ?';
        $dateParams = [date('Y-m-d H:i:s', strtotime("-{$dateRange} days"))];
    }
    // For 'all', no date condition needed
    
    // Build parameter list for SELECT
    $selectParams = array_merge(['reading_date'], $parameters);
    $selectClause = implode(', ', $selectParams);
    
    // Query data
    $sql = "SELECT {$selectClause} FROM {$tableName} 
            WHERE " . ($equipmentType === 'engine' ? 'engine_type' : 'generator_type') . " = ? {$dateCondition}
            ORDER BY reading_date ASC";
    
    $queryParams = array_merge([$equipmentId], $dateParams);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rawData)) {
        return [
            'success' => true,
            'data' => [
                'labels' => [],
                'datasets' => []
            ],
            'date_range' => 'No data found for selected period',
            'message' => 'No data available for the selected equipment and time period'
        ];
    }
    
    // Format data for Chart.js
    $datasets = [];
    $colors = [
        '#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6',
        '#1abc9c', '#e67e22', '#34495e', '#95a5a6', '#c0392b',
        '#8e44ad', '#16a085', '#f1c40f', '#27ae60', '#2980b9'
    ];
    $colorIndex = 0;
    
    foreach ($parameters as $param) {
        $data = [];
        foreach ($rawData as $row) {
            $data[] = [
                'x' => $row['reading_date'],
                'y' => floatval($row[$param] ?? 0)
            ];
        }
        
        // Determine Y-axis based on parameter type
        $yAxisID = 'y-other';
        if (strpos($param, 'rpm') !== false) {
            $yAxisID = 'y-rpm';
        }
        
        $datasets[] = [
            'label' => formatParameterName($param),
            'data' => $data,
            'borderColor' => $colors[$colorIndex % count($colors)],
            'backgroundColor' => $colors[$colorIndex % count($colors)] . '20',
            'fill' => false,
            'tension' => 0.1,
            'yAxisID' => $yAxisID,
            'pointRadius' => 3,
            'pointHoverRadius' => 5
        ];
        $colorIndex++;
    }
    
    // Calculate date range info
    $firstDate = new DateTime($rawData[0]['reading_date']);
    $lastDate = new DateTime($rawData[count($rawData) - 1]['reading_date']);
    $dateRangeInfo = $firstDate->format('M j, Y H:i') . ' to ' . $lastDate->format('M j, Y H:i');
    
    return [
        'success' => true,
        'data' => [
            'labels' => [], // Chart.js will use x values from datasets
            'datasets' => $datasets
        ],
        'date_range' => $dateRangeInfo,
        'total_points' => count($rawData)
    ];
}

/**
 * Save scale settings to vessel configuration
 */
function saveScaleSettings($pdo, $settings) {
    $settingsToSave = [
        'graph_rpm_min' => floatval($settings['rpm_min'] ?? 0),
        'graph_rpm_max' => floatval($settings['rpm_max'] ?? 2000),
        'graph_pressure_min' => floatval($settings['pressure_min'] ?? 0),
        'graph_pressure_max' => floatval($settings['pressure_max'] ?? 100),
        'graph_temp_min' => floatval($settings['temp_min'] ?? 100),
        'graph_temp_max' => floatval($settings['temp_max'] ?? 300)
    ];
    
    foreach ($settingsToSave as $key => $value) {
        $stmt = $pdo->prepare("INSERT OR REPLACE INTO vessel_settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
    }
    
    return ['success' => true, 'message' => 'Scale settings saved successfully'];
}

/**
 * Format parameter name for display
 */
function formatParameterName($param) {
    $formatted = str_replace('_', ' ', $param);
    $formatted = ucwords($formatted);
    
    // Special formatting for common abbreviations
    $replacements = [
        'Rpm' => 'RPM',
        'Psi' => 'PSI',
        'In ' => 'In. ',
        'Out ' => 'Out. ',
        'Oil ' => 'Oil ',
        'Temp ' => 'Temperature ',
        'Pressure ' => 'Pressure'
    ];
    
    foreach ($replacements as $search => $replace) {
        $formatted = str_replace($search, $replace, $formatted);
    }
    
    return $formatted;
}

/**
 * Get available equipment for a given type
 */
function getAvailableEquipment($pdo, $equipmentType) {
    $tableName = ($equipmentType === 'engine') ? 'engine_readings' : 'generator_readings';
    $columnName = ($equipmentType === 'engine') ? 'engine_type' : 'generator_type';
    
    $stmt = $pdo->prepare("SELECT DISTINCT {$columnName} FROM {$tableName} ORDER BY {$columnName}");
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get date range of available data for equipment
 */
function getDataDateRange($pdo, $equipmentType, $equipmentId) {
    $tableName = ($equipmentType === 'engine') ? 'engine_readings' : 'generator_readings';
    $columnName = ($equipmentType === 'engine') ? 'engine_type' : 'generator_type';
    
    $stmt = $pdo->prepare("SELECT MIN(reading_date) as min_date, MAX(reading_date) as max_date, COUNT(*) as total_readings 
                          FROM {$tableName} WHERE {$columnName} = ?");
    $stmt->execute([$equipmentId]);
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
