<?php
session_start(); // Start session first
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

// Force set the Test Vessel session to ensure it's available
set_active_vessel(4, 'Test Vessel');

echo "<!-- DEBUG MODE -->";
echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;'>";
echo "<h4>DEBUG INFO:</h4>";
echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'NOT SET') . "<br>";
echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'NOT SET') . "<br>";

// Get current vessel
$current_vessel = get_current_vessel($conn);
if ($current_vessel) {
    echo "Current vessel: {$current_vessel['VesselName']} (ID: {$current_vessel['VesselID']})<br>";
    echo "EngineConfig: " . ($current_vessel['EngineConfig'] ?? 'NOT SET') . "<br>";
} else {
    echo "❌ current_vessel is NULL<br>";
}

// Get available sides for current vessel
$sides = get_vessel_sides($conn, $current_vessel['VesselID']);
echo "Sides: " . implode(', ', $sides) . " (count: " . count($sides) . ")<br>";
echo "</div>";

// Skip require_vessel_selection() for now and continue with the normal logic
// require_vessel_selection();

// Get current user 
$current_user = get_logged_in_user();

// Get filter values
$equipment_type = $_GET['equipment'] ?? '';
$side = $_GET['side'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$highlight_hours = $_GET['hours'] ?? '';

// Available equipment types
$equipment_types = ['mainengines', 'generators', 'gears'];

// Function to get logs based on filters (copied from original)
function getLogs($conn, $equipment_type, $vessel_id, $side, $date_from, $date_to) {
    if (empty($equipment_type) || !in_array($equipment_type, ['mainengines', 'generators', 'gears'])) {
        return [];
    }
    
    $sql = "SELECT e.*, COALESCE(CONCAT(u.FirstName, ' ', u.LastName), 'Unknown User') as RecordedByName 
            FROM $equipment_type e 
            LEFT JOIN users u ON e.RecordedBy = u.UserID 
            WHERE e.VesselID = ?";
    $params = [$vessel_id];
    $types = 'i';
    
    if (!empty($side)) {
        $sql .= " AND e.Side = ?";
        $params[] = $side;
        $types .= 's';
    }
    
    if (!empty($date_from)) {
        $sql .= " AND e.EntryDate >= ?";
        $params[] = $date_from;
        $types .= 's';
    }
    
    if (!empty($date_to)) {
        $sql .= " AND e.EntryDate <= ?";
        $params[] = $date_to;
        $types .= 's';
    }
    
    $sql .= " ORDER BY e.EntryDate DESC, e.Timestamp DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

$logs = [];
if (!empty($equipment_type)) {
    $logs = getLogs($conn, $equipment_type, $current_vessel['VesselID'], $side, $date_from, $date_to);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEBUG View Logs - Vessel Data Logger</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>🐛 DEBUG View Logs - <?= htmlspecialchars($current_vessel['VesselName']) ?></h1>
        
        <div class="filter-container">
            <h2>Filter Logs</h2>
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="equipment">Equipment Type:</label>
                        <select name="equipment" id="equipment" required>
                            <option value="">Select Equipment</option>
                            <?php foreach ($equipment_types as $type): ?>
                                <option value="<?= $type ?>" <?= $equipment_type === $type ? 'selected' : '' ?>>
                                    <?= ucfirst($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="side">Side:</label>
                        <select name="side" id="side">
                            <option value="">All Sides</option>
                            <?php 
                            echo "<!-- DEBUG: Generating side options -->\n";
                            foreach ($sides as $side_name): 
                                echo "<!-- DEBUG: Adding side: {$side_name} -->\n";
                            ?>
                                <option value="<?= htmlspecialchars($side_name) ?>" <?= $side === $side_name ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($side_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div style="margin-top: 5px; font-size: 12px; color: #666;">
                            DEBUG: Available sides: <?= implode(', ', $sides) ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_from">From Date:</label>
                        <input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_to">To Date:</label>
                        <input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">🔍 Search Logs</button>
                    <a href="debug_view_logs_test.php" class="btn btn-info">Clear Filters</a>
                </div>
            </form>
        </div>
        
        <?php if (!empty($equipment_type)): ?>
        <div class="table-container">
            <h2><?= ucfirst($equipment_type) ?> Logs</h2>
            
            <?php if (empty($logs)): ?>
                <p style="text-align: center; color: #666; padding: 20px;">
                    No logs found for the selected criteria.
                </p>
            <?php else: ?>
                <p style="color: #666; margin-bottom: 15px;">
                    Found <?= count($logs) ?> log entries
                </p>
                
                <table>
                    <thead>
                        <tr>
                            <th>Entry ID</th>
                            <th>Date</th>
                            <th>Side</th>
                            <th>VesselID</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= $log['EntryID'] ?? $log['GenID'] ?? $log['GearID'] ?></td>
                            <td><?= $log['EntryDate'] ?></td>
                            <td><?= htmlspecialchars($log['Side']) ?></td>
                            <td><?= $log['VesselID'] ?></td>
                            <td>
                                <?php if ($equipment_type === 'mainengines'): ?>
                                    RPM: <?= $log['RPM'] ?>, Hrs: <?= $log['MainHrs'] ?>
                                <?php elseif ($equipment_type === 'generators'): ?>
                                    Hrs: <?= $log['GenHrs'] ?>
                                <?php else: ?>
                                    Hrs: <?= $log['GearHrs'] ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="comprehensive_debug.php" class="btn btn-info">🔧 Comprehensive Debug</a>
            <a href="manage_vessels.php" class="btn btn-secondary">⚙️ Manage Vessels</a>
        </div>
    </div>
</body>
</html>
