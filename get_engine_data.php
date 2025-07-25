<?php
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get parameters
$side = $_GET['side'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Validate side parameter
if (empty($side) || !in_array($side, ['Port', 'Starboard'])) {
    echo json_encode([]);
    exit;
}

try {
    // Get main engine data for the corresponding side
    $sql = "SELECT EntryDate, RPM FROM mainengines WHERE Side = ?";
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
    
    $engine_data = [];
    while ($row = $result->fetch_assoc()) {
        $engine_data[] = [
            'date' => $row['EntryDate'],
            'RPM' => (int)$row['RPM']
        ];
    }
    
    echo json_encode($engine_data);
    
} catch (Exception $e) {
    error_log("Error in get_engine_data.php: " . $e->getMessage());
    echo json_encode([]);
}
?>
