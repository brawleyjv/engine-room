<?php
require_once 'config.php';

// Get filter values
$equipment_type = $_GET['equipment'] ?? '';
$side = $_GET['side'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$highlight_hours = $_GET['hours'] ?? ''; // For highlighting specific entries

// Available equipment types
$equipment_types = ['mainengines', 'generators', 'gears'];
$sides = ['Port', 'Starboard'];

// Function to get logs based on filters
function getLogs($conn, $equipment_type, $side, $date_from, $date_to) {
    if (empty($equipment_type) || !in_array($equipment_type, ['mainengines', 'generators', 'gears'])) {
        return [];
    }
    
    $sql = "SELECT * FROM $equipment_type WHERE 1=1";
    $params = [];
    $types = '';
    
    if (!empty($side)) {
        $sql .= " AND Side = ?";
        $params[] = $side;
        $types .= 's';
    }
    
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
    
    $sql .= " ORDER BY EntryDate DESC, Timestamp DESC";
    
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
    $logs = getLogs($conn, $equipment_type, $side, $date_from, $date_to);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Logs - Vessel Data Logger</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 View Equipment Logs</h1>
            <p><a href="index.php" class="btn btn-info">← Back to Home</a></p>
        </header>
        
        <div class="form-container">
            <?php if (!empty($highlight_hours) && !empty($equipment_type) && !empty($side)): ?>
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>🔍 Duplicate Hours Search:</strong> Showing existing entry with <?= htmlspecialchars($highlight_hours) ?> hours for <?= ucfirst($equipment_type) ?> (<?= htmlspecialchars($side) ?> side). The highlighted entry below needs to be updated or you need to use different hours for your new entry.
                </div>
            <?php endif; ?>
            
            <h2>Filter Logs</h2>
            <form method="GET" action="view_logs.php">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    
                    <div class="form-group">
                        <label for="equipment">Equipment Type:</label>
                        <select name="equipment" id="equipment" required>
                            <option value="">Select Equipment</option>
                            <option value="mainengines" <?= $equipment_type === 'mainengines' ? 'selected' : '' ?>>Main Engines</option>
                            <option value="generators" <?= $equipment_type === 'generators' ? 'selected' : '' ?>>Generators</option>
                            <option value="gears" <?= $equipment_type === 'gears' ? 'selected' : '' ?>>Gears</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="side">Side:</label>
                        <select name="side" id="side">
                            <option value="">All Sides</option>
                            <option value="Port" <?= $side === 'Port' ? 'selected' : '' ?>>Port</option>
                            <option value="Starboard" <?= $side === 'Starboard' ? 'selected' : '' ?>>Starboard</option>
                        </select>
                    </div>
                    
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
                    <a href="view_logs.php" class="btn btn-info">Clear Filters</a>
                </div>
            </form>
        </div>
        
        <?php if (!empty($equipment_type)): ?>
        <div class="table-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><?= ucfirst($equipment_type) ?> Logs</h2>
                
                <!-- Graph buttons for each side -->
                <div>
                    <?php
                    // Check if we have data for each side
                    $port_count = 0;
                    $starboard_count = 0;
                    
                    if (!empty($logs)) {
                        foreach ($logs as $log) {
                            if ($log['Side'] === 'Port') $port_count++;
                            if ($log['Side'] === 'Starboard') $starboard_count++;
                        }
                    }
                    ?>
                    
                    <?php if ($port_count > 0): ?>
                        <a href="graph_logs.php?equipment=<?= $equipment_type ?>&side=Port<?= !empty($date_from) ? '&date_from=' . $date_from : '' ?><?= !empty($date_to) ? '&date_to=' . $date_to : '' ?>" 
                           class="btn btn-success" style="margin-left: 10px;">
                            📊 Port Graph (<?= $port_count ?>)
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($starboard_count > 0): ?>
                        <a href="graph_logs.php?equipment=<?= $equipment_type ?>&side=Starboard<?= !empty($date_from) ? '&date_from=' . $date_from : '' ?><?= !empty($date_to) ? '&date_to=' . $date_to : '' ?>" 
                           class="btn btn-success" style="margin-left: 10px;">
                            📊 Starboard Graph (<?= $starboard_count ?>)
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
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
                            <?php if ($equipment_type === 'mainengines'): ?>
                                <th>RPM</th>
                                <th>Main Hrs</th>
                                <th>Oil Pressure (PSI)</th>
                                <th>Oil Temp (°F)</th>
                                <th>Fuel Press (PSI)</th>
                                <th>Water Temp (°F)</th>
                            <?php elseif ($equipment_type === 'generators'): ?>
                                <th>Gen Hrs</th>
                                <th>Oil Press (PSI)</th>
                                <th>Fuel Press (PSI)</th>
                                <th>Water Temp (°F)</th>
                            <?php elseif ($equipment_type === 'gears'): ?>
                                <th>Gear Hrs</th>
                                <th>Oil Press (PSI)</th>
                                <th>Temperature (°F)</th>
                            <?php endif; ?>
                            <th>Recorded By</th>
                            <th>Notes</th>
                            <th>Timestamp</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            // Check if this row should be highlighted due to duplicate hours
                            $should_highlight = false;
                            if (!empty($highlight_hours)) {
                                if ($equipment_type === 'mainengines' && $log['MainHrs'] == $highlight_hours) {
                                    $should_highlight = true;
                                } elseif ($equipment_type === 'generators' && $log['GenHrs'] == $highlight_hours) {
                                    $should_highlight = true;
                                } elseif ($equipment_type === 'gears' && $log['GearHrs'] == $highlight_hours) {
                                    $should_highlight = true;
                                }
                            }
                        ?>
                        <tr <?= $should_highlight ? 'style="background-color: #fff3cd; border: 2px solid #ffeaa7; box-shadow: 0 0 10px rgba(255, 193, 7, 0.3);"' : '' ?>>
                            <td><?= htmlspecialchars($log['EntryID']) ?></td>
                            <td><?= htmlspecialchars($log['EntryDate']) ?></td>
                            <td>
                                <span style="color: <?= $log['Side'] === 'Port' ? '#dc3545' : '#28a745' ?>;">
                                    <?= htmlspecialchars($log['Side']) ?>
                                </span>
                            </td>
                            
                            <?php if ($equipment_type === 'mainengines'): ?>
                                <td><?= $log['RPM'] ?? '-' ?></td>
                                <td <?= $should_highlight ? 'style="font-weight: bold; color: #d63031;"' : '' ?>><?= $log['MainHrs'] ?? '-' ?></td>
                                <td><?= $log['OilPressure'] ?? '-' ?></td>
                                <td><?= $log['OilTemp'] ?? '-' ?></td>
                                <td><?= $log['FuelPress'] ?? '-' ?></td>
                                <td><?= $log['WaterTemp'] ?? '-' ?></td>
                            <?php elseif ($equipment_type === 'generators'): ?>
                                <td <?= $should_highlight ? 'style="font-weight: bold; color: #d63031;"' : '' ?>><?= $log['GenHrs'] ?? '-' ?></td>
                                <td><?= $log['OilPress'] ?? '-' ?></td>
                                <td><?= $log['FuelPress'] ?? '-' ?></td>
                                <td><?= $log['WaterTemp'] ?? '-' ?></td>
                            <?php elseif ($equipment_type === 'gears'): ?>
                                <td <?= $should_highlight ? 'style="font-weight: bold; color: #d63031;"' : '' ?>><?= $log['GearHrs'] ?? '-' ?></td>
                                <td><?= $log['OilPress'] ?? '-' ?></td>
                                <td><?= $log['Temp'] ?? '-' ?></td>
                            <?php endif; ?>
                            
                            <td><?= htmlspecialchars($log['RecordedBy'] ?? '-') ?></td>
                            <td style="max-width: 200px;">
                                <?= !empty($log['Notes']) ? htmlspecialchars(substr($log['Notes'], 0, 50)) . (strlen($log['Notes']) > 50 ? '...' : '') : '-' ?>
                            </td>
                            <td><?= htmlspecialchars($log['Timestamp'] ?? '-') ?></td>
                            <td>
                                <a href="edit_log.php?equipment=<?= $equipment_type ?>&id=<?= $log['EntryID'] ?>" 
                                   class="btn btn-warning" style="font-size: 12px; padding: 5px 10px;">
                                    ✏️ Edit
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <footer>
            <p>&copy; 2025 Vessel Data Logger | <a href="index.php">Home</a></p>
        </footer>
    </div>
</body>
</html>
