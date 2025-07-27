<?php
session_start();
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

// Require login first
require_login();

// Ensure we have an active vessel set
if (!isset($_SESSION['current_vessel_id']) || empty($_SESSION['current_vessel_id'])) {
    // Check if we have active_vessel_id instead
    if (isset($_SESSION['active_vessel_id']) && !empty($_SESSION['active_vessel_id'])) {
        // Sync the session variables
        $_SESSION['current_vessel_id'] = $_SESSION['active_vessel_id'];
        
        // Get vessel name for session
        $sql = "SELECT VesselName FROM vessels WHERE VesselID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $_SESSION['active_vessel_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $vessel = $result->fetch_assoc();
        if ($vessel) {
            $_SESSION['current_vessel_name'] = $vessel['VesselName'];
        }
    } else {
        // No vessel selected, redirect to vessel selection
        header('Location: select_vessel.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

// Get current user and vessel
$current_user = get_logged_in_user();
$current_vessel = get_current_vessel($conn);

// Get filter values
$equipment_type = $_GET['equipment'] ?? '';
$side = $_GET['side'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$highlight_hours = $_GET['hours'] ?? ''; // For highlighting specific entries

// Available equipment types
$equipment_types = ['mainengines', 'generators', 'gears'];

// Get available sides for current vessel (default to mainengines, will be updated by JavaScript)
$sides = get_vessel_sides($conn, $current_vessel['VesselID'], $equipment_type);

// Function to get logs based on filters
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
    <title>View Logs - Vessel Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="alternate icon" href="favicon.ico">
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
                        <select name="equipment" id="equipment" required onchange="updateSideOptions()">
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
                            <?php foreach ($sides as $side_name): ?>
                                <option value="<?= htmlspecialchars($side_name) ?>" <?= $side === $side_name ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($side_name) ?>
                                </option>
                            <?php endforeach; ?>
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
                    $side_counts = [];
                    
                    // Initialize counts for all available sides
                    foreach ($sides as $side_name) {
                        $side_counts[$side_name] = 0;
                    }
                    
                    if (!empty($logs)) {
                        foreach ($logs as $log) {
                            if (isset($side_counts[$log['Side']])) {
                                $side_counts[$log['Side']]++;
                            }
                        }
                    }
                    ?>
                    
                    <?php foreach ($sides as $side_name): ?>
                        <?php if ($side_counts[$side_name] > 0): ?>
                            <a href="graph_logs.php?equipment=<?= $equipment_type ?>&side=<?= urlencode($side_name) ?><?= !empty($date_from) ? '&date_from=' . $date_from : '' ?><?= !empty($date_to) ? '&date_to=' . $date_to : '' ?>" 
                               class="btn btn-success" style="margin-left: 10px;">
                                📊 <?= htmlspecialchars($side_name) ?> Graph (<?= $side_counts[$side_name] ?>)
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
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
                                <?php
                                $side_color = '#666'; // Default gray
                                if ($log['Side'] === 'Port') {
                                    $side_color = '#dc3545'; // Red
                                } elseif ($log['Side'] === 'Starboard') {
                                    $side_color = '#28a745'; // Green
                                } elseif ($log['Side'] === 'Center Main') {
                                    $side_color = '#007bff'; // Blue
                                }
                                ?>
                                <span style="color: <?= $side_color ?>;">
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
                            
                            <td><?= htmlspecialchars($log['RecordedByName'] ?? '-') ?></td>
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
    
    <script>
        function updateSideOptions() {
            const equipmentType = document.getElementById('equipment').value;
            const sideSelect = document.getElementById('side');
            
            if (!equipmentType) {
                return;
            }
            
            // Store current selection
            const currentSide = sideSelect.value;
            
            // Fetch sides for this equipment type
            fetch('get_equipment_sides.php?equipment_type=' + encodeURIComponent(equipmentType))
                .then(response => response.json())
                .then(data => {
                    if (data.sides) {
                        // Clear current options except the first one
                        sideSelect.innerHTML = '<option value="">All Sides</option>';
                        
                        // Add new options
                        data.sides.forEach(side => {
                            const option = document.createElement('option');
                            option.value = side;
                            option.textContent = side;
                            
                            // Restore selection if it still exists
                            if (side === currentSide) {
                                option.selected = true;
                            }
                            
                            sideSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching sides:', error);
                });
        }
        
        // Update sides on page load if equipment type is already selected
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('equipment').value) {
                updateSideOptions();
            }
        });
    </script>
</body>
</html>
