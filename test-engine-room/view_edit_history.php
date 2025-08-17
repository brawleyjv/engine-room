<?php
/**
 * Log Entry Edit History Viewer
 * Shows audit trail of all edits made to a log entry
 */

require_once 'test_db.php';

$entry_id = $_GET['entry_id'] ?? 0;

try {
    $pdo = getTestDatabase();
    
    // Get the log entry
    $stmt = $pdo->prepare("SELECT * FROM log_entries WHERE id = ?");
    $stmt->execute([$entry_id]);
    $log_entry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all edits for this entry
    $stmt = $pdo->prepare("SELECT * FROM log_edits WHERE log_entry_id = ? ORDER BY edited_at DESC");
    $stmt->execute([$entry_id]);
    $edits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error loading edit history: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit History - Log Entry #<?php echo $entry_id; ?></title>
    <link rel="stylesheet" href="test_styles.css">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .edit-history {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .edit-entry {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .edit-entry:last-child {
            border-bottom: none;
        }
        .edit-meta {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .diff-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 15px 0;
        }
        .original-text, .new-text {
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            line-height: 1.5;
        }
        .original-text {
            background: #ffebee;
            border-left: 4px solid #f44336;
        }
        .new-text {
            background: #e8f5e8;
            border-left: 4px solid #4caf50;
        }
    </style>
</head>
<body>
    <div class="edit-history">
        <div style="background: #2c3e50; color: white; padding: 20px; border-radius: 8px 8px 0 0;">
            <h2>📋 Edit History - Log Entry #<?php echo $entry_id; ?></h2>
            <?php if ($log_entry): ?>
                <p>Original Entry: <?php echo date('Y-m-d H:i', strtotime($log_entry['log_date'] . ' ' . $log_entry['log_time'])); ?></p>
            <?php endif; ?>
        </div>

        <?php if (isset($error)): ?>
            <div style="padding: 20px; background: #f8d7da; color: #721c24;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif (empty($edits)): ?>
            <div style="padding: 40px; text-align: center; color: #666;">
                <h3>No Edit History</h3>
                <p>This log entry has not been edited.</p>
            </div>
        <?php else: ?>
            <div style="padding: 20px; background: #e7f3ff; border-bottom: 1px solid #ddd;">
                <h3>Current Version</h3>
                <div class="new-text">
                    <?php echo nl2br(htmlspecialchars($log_entry['log_entry'] ?? '')); ?>
                </div>
                <p style="margin-top: 10px; font-size: 14px; color: #666;">
                    Total Edits: <?php echo count($edits); ?>
                </p>
            </div>

            <?php foreach ($edits as $index => $edit): ?>
                <div class="edit-entry">
                    <div class="edit-meta">
                        <strong>Edit #<?php echo count($edits) - $index; ?></strong> - 
                        <?php echo date('F j, Y \a\t g:i A', strtotime($edit['edited_at'])); ?><br>
                        <strong>Edited by:</strong> <?php echo htmlspecialchars($edit['edited_by']); ?><br>
                        <strong>Reason:</strong> <?php echo htmlspecialchars($edit['edit_reason']); ?>
                    </div>

                    <div class="diff-container">
                        <div>
                            <h4 style="margin-top: 0; color: #f44336;">Before (Original)</h4>
                            <div class="original-text">
                                <?php echo nl2br(htmlspecialchars($edit['original_entry'])); ?>
                            </div>
                        </div>
                        <div>
                            <h4 style="margin-top: 0; color: #4caf50;">After (Edited)</h4>
                            <div class="new-text">
                                <?php echo nl2br(htmlspecialchars($edit['new_entry'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="padding: 20px; text-align: center; border-top: 1px solid #eee; background: #f8f9fa;">
            <button onclick="window.close()" class="btn btn-secondary">Close Window</button>
        </div>
    </div>
</body>
</html>
