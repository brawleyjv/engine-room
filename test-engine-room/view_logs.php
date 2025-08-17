<?php
/**
 * Engine Room Log Book Viewer
 * Traditional marine logbook display with editing capabilities
 */

require_once 'test_db.php';
require_once 'settings_helper.php';

$message = '';
$error = '';

// Get selected date (default to today)
$selected_date = $_GET['date'] ?? date('Y-m-d');
$view_mode = $_GET['view'] ?? 'day'; // 'day', 'week', 'month'

// Handle manual log entry
if ($_POST && isset($_POST['add_manual_entry'])) {
    try {
        $pdo = getTestDatabase();
        
        $log_time = $_POST['log_time'];
        $log_entry = trim($_POST['log_entry']);
        $created_by = $_POST['created_by'] ?: 'Marine Engineer';
        
        if (!empty($log_entry)) {
            $stmt = $pdo->prepare("INSERT INTO log_entries 
                (vessel_id, log_date, log_time, entry_type, log_entry, created_by) 
                VALUES (1, ?, ?, 'manual', ?, ?)");
            $stmt->execute([$selected_date, $log_time, $log_entry, $created_by]);
            
            $message = "Manual log entry added successfully!";
        }
    } catch (Exception $e) {
        $error = "Error adding log entry: " . $e->getMessage();
    }
}

// Handle log editing
if ($_POST && isset($_POST['edit_log_entry'])) {
    try {
        $pdo = getTestDatabase();
        
        $log_id = $_POST['log_id'];
        $new_entry = trim($_POST['new_entry']);
        $edit_reason = trim($_POST['edit_reason']);
        $edited_by = $_POST['edited_by'] ?: 'Marine Engineer';
        
        // Get original entry
        $stmt = $pdo->prepare("SELECT log_entry FROM log_entries WHERE id = ?");
        $stmt->execute([$log_id]);
        $original = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($original && !empty($new_entry) && !empty($edit_reason)) {
            // Record the edit in audit trail
            $stmt = $pdo->prepare("INSERT INTO log_edits 
                (log_entry_id, original_entry, new_entry, edit_reason, edited_by) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$log_id, $original['log_entry'], $new_entry, $edit_reason, $edited_by]);
            
            // Update the log entry
            $stmt = $pdo->prepare("UPDATE log_entries SET log_entry = ?, is_edited = 1 WHERE id = ?");
            $stmt->execute([$new_entry, $log_id]);
            
            $message = "Log entry updated successfully! Edit recorded in audit trail.";
        }
    } catch (Exception $e) {
        $error = "Error editing log entry: " . $e->getMessage();
    }
}

try {
    $pdo = getTestDatabase();
    
    // Get log entries for selected date
    $stmt = $pdo->prepare("SELECT * FROM log_entries 
                          WHERE log_date = ? 
                          ORDER BY log_time ASC");
    $stmt->execute([$selected_date]);
    $log_entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get vessel info
    $vessel_info = $pdo->query("SELECT * FROM test_vessels LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error loading log entries: " . $e->getMessage();
    $log_entries = [];
}

// Helper function to format entry type
function formatEntryType($type) {
    $types = [
        'engine' => '🔧 Engine',
        'gearbox' => '⚙️ Gearbox', 
        'generator' => '🔌 Generator',
        'fluid' => '🛢️ Fluid',
        'maintenance' => '🔨 Maintenance',
        'manual' => '📝 Manual Entry'
    ];
    return $types[$type] ?? ucfirst($type);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engine Room Log Book - <?php echo date('F j, Y', strtotime($selected_date)); ?></title>
    <link rel="stylesheet" href="test_styles.css">
    <style>
        .logbook-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .logbook-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .log-entry {
            border-bottom: 1px solid #eee;
            padding: 15px 20px;
            display: flex;
            align-items: flex-start;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-time {
            font-weight: bold;
            color: #2c3e50;
            min-width: 80px;
            font-family: monospace;
            font-size: 16px;
        }
        
        .log-content {
            flex-grow: 1;
            margin-left: 20px;
        }
        
        .log-type {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .log-text {
            line-height: 1.5;
            color: #333;
        }
        
        .log-meta {
            font-size: 12px;
            color: #888;
            margin-top: 8px;
        }
        
        .edited-indicator {
            color: #e67e22;
            font-weight: bold;
        }
        
        .log-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .edit-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #3498db;
        }
        
        .date-navigation {
            background: #ecf0f1;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: between;
            align-items: center;
            gap: 20px;
        }
        
        .manual-entry-form {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin: 20px;
            border-left: 4px solid #27ae60;
        }
        
        .print-ready {
            font-family: 'Courier New', monospace;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            .logbook-container {
                box-shadow: none;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Engine Room Log Book</h1>
        <p><?php echo $vessel_info['name'] ?? 'Marine Vessel'; ?> - Daily Operations Log</p>
        <a href="index.php" style="color: white; text-decoration: none;">← Back to Dashboard</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="logbook-container">
            <!-- Date Navigation -->
            <div class="date-navigation no-print">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <label for="log_date"><strong>Select Date:</strong></label>
                    <input type="date" id="log_date" value="<?php echo $selected_date; ?>" 
                           onchange="window.location.href='view_logs.php?date=' + this.value;">
                    
                    <a href="view_logs.php?date=<?php echo date('Y-m-d', strtotime($selected_date . ' -1 day')); ?>" 
                       class="btn btn-secondary" style="padding: 5px 10px;">← Previous Day</a>
                    <a href="view_logs.php?date=<?php echo date('Y-m-d', strtotime($selected_date . ' +1 day')); ?>" 
                       class="btn btn-secondary" style="padding: 5px 10px;">Next Day →</a>
                    <a href="view_logs.php?date=<?php echo date('Y-m-d'); ?>" 
                       class="btn btn-primary" style="padding: 5px 10px;">Today</a>
                </div>
                
                <button onclick="window.print()" class="btn btn-success">🖨️ Print Log</button>
            </div>

            <!-- Logbook Header -->
            <div class="logbook-header">
                <div>
                    <h2>Daily Engine Room Log</h2>
                    <p><?php echo date('l, F j, Y', strtotime($selected_date)); ?></p>
                </div>
                <div style="text-align: right;">
                    <p><strong>Vessel:</strong> <?php echo $vessel_info['name'] ?? 'Marine Vessel'; ?></p>
                    <p><strong>Total Entries:</strong> <?php echo count($log_entries); ?></p>
                </div>
            </div>

            <!-- Log Entries -->
            <div class="log-entries">
                <?php if (empty($log_entries)): ?>
                    <div style="padding: 40px; text-align: center; color: #666;">
                        <h3>No log entries for <?php echo date('F j, Y', strtotime($selected_date)); ?></h3>
                        <p>Add manual entries below or navigate to a different date.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($log_entries as $entry): ?>
                        <div class="log-entry" id="entry-<?php echo $entry['id']; ?>">
                            <div class="log-time">
                                <?php echo date('H:i', strtotime($entry['log_time'])); ?>
                            </div>
                            <div class="log-content">
                                <div class="log-type">
                                    <?php echo formatEntryType($entry['entry_type']); ?>
                                    <?php if ($entry['equipment_id']): ?>
                                        - <?php echo ucwords(str_replace('_', ' ', $entry['equipment_id'])); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="log-text">
                                    <?php echo nl2br(htmlspecialchars($entry['log_entry'])); ?>
                                </div>
                                <div class="log-meta">
                                    By: <?php echo htmlspecialchars($entry['created_by']); ?> 
                                    at <?php echo date('H:i', strtotime($entry['created_at'])); ?>
                                    <?php if ($entry['is_edited']): ?>
                                        <span class="edited-indicator">• EDITED</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Edit Actions (No Print) -->
                                <div class="log-actions no-print">
                                    <button onclick="showEditForm(<?php echo $entry['id']; ?>)" 
                                            class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">
                                        ✏️ Edit Entry
                                    </button>
                                    <?php if ($entry['is_edited']): ?>
                                        <button onclick="showEditHistory(<?php echo $entry['id']; ?>)" 
                                                class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">
                                            📋 View Edit History
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Edit Form (Hidden by default) -->
                                <div id="edit-form-<?php echo $entry['id']; ?>" class="edit-form no-print" style="display: none;">
                                    <form method="POST">
                                        <input type="hidden" name="log_id" value="<?php echo $entry['id']; ?>">
                                        
                                        <div style="margin-bottom: 15px;">
                                            <label><strong>Edit Entry:</strong></label>
                                            <textarea name="new_entry" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required><?php echo htmlspecialchars($entry['log_entry']); ?></textarea>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <label><strong>Reason for Edit:</strong></label>
                                            <input type="text" name="edit_reason" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" 
                                                   placeholder="e.g., Corrected typo, Added missing information" required>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <label><strong>Edited By:</strong></label>
                                            <input type="text" name="edited_by" value="Marine Engineer" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                                        </div>
                                        
                                        <div style="display: flex; gap: 10px;">
                                            <button type="submit" name="edit_log_entry" class="btn btn-primary">💾 Save Changes</button>
                                            <button type="button" onclick="hideEditForm(<?php echo $entry['id']; ?>)" class="btn btn-secondary">❌ Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Manual Entry Form -->
        <div class="manual-entry-form no-print">
            <h3>📝 Add Manual Log Entry</h3>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label><strong>Time:</strong></label>
                        <input type="time" name="log_time" value="<?php echo date('H:i'); ?>" 
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                    </div>
                    <div>
                        <label><strong>Log Entry:</strong></label>
                        <textarea name="log_entry" rows="2" 
                                  style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" 
                                  placeholder="Enter manual log entry..." required></textarea>
                    </div>
                    <div>
                        <label><strong>Created By:</strong></label>
                        <input type="text" name="created_by" value="Marine Engineer" 
                               style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                    </div>
                </div>
                <button type="submit" name="add_manual_entry" class="btn btn-success">➕ Add Log Entry</button>
            </form>
        </div>
    </div>

    <script>
        function showEditForm(entryId) {
            document.getElementById('edit-form-' + entryId).style.display = 'block';
        }

        function hideEditForm(entryId) {
            document.getElementById('edit-form-' + entryId).style.display = 'none';
        }

        function showEditHistory(entryId) {
            // Open edit history in a new window/popup
            window.open('view_edit_history.php?entry_id=' + entryId, 'EditHistory', 
                       'width=800,height=600,scrollbars=yes,resizable=yes');
        }
    </script>
</body>
</html>
