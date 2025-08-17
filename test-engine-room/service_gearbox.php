<?php
/**
 * Gearbox Service Management Page
 * Track and manage service items for individual gearboxes
 */

require_once 'test_db.php';
require_once 'settings_helper.php';
require_once 'log_helper.php';

$pdo = getTestDatabase();
$message = '';
$error = '';

// Get gearbox type from URL
$gearbox_type = $_GET['type'] ?? '';
if (empty($gearbox_type)) {
    header('Location: index.php');
    exit;
}

$vessel_settings = getVesselSettings($pdo);

// Check if gearbox is active
$is_active = isGearboxActive($gearbox_type, $vessel_settings);
if (!$is_active) {
    header('Location: index.php');
    exit;
}

// Handle service completion
if ($_POST && isset($_POST['complete_service'])) {
    try {
        $service_code = $_POST['service_code'];
        $current_hours = floatval($_POST['current_hours']);
        $notes = trim($_POST['notes'] ?? '');
        $performed_by = trim($_POST['performed_by'] ?? 'Marine Engineer');
        
        // Get service item details
        $service_stmt = $pdo->prepare("
            SELECT si.item_name, ss.interval_hours
            FROM service_items si
            JOIN service_settings ss ON si.item_code = ss.service_item_code
            WHERE si.item_code = ? AND ss.equipment_type = 'gearbox' AND ss.equipment_id = ?
        ");
        $service_stmt->execute([$service_code, $gearbox_type]);
        $service_item = $service_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service_item) {
            $next_service_hours = $current_hours + $service_item['interval_hours'];
            
            // Create log entry first
            $log_message = "Service completed: {$service_item['item_name']} on " . 
                          ucwords(str_replace('_', ' ', $gearbox_type)) . " Gearbox at {$current_hours} hours. " .
                          "Next service due at {$next_service_hours} hours.";
            if (!empty($notes)) {
                $log_message .= " Notes: " . $notes;
            }
            
            $log_id = createLogEntry($pdo, 'service', $gearbox_type, $log_message, $performed_by);
            
            // Update service tracking
            $update_stmt = $pdo->prepare("
                UPDATE service_tracking 
                SET last_service_hours = ?, 
                    last_service_date = DATE('now'),
                    next_service_hours = ?,
                    notes = ?,
                    performed_by = ?,
                    log_entry_id = ?,
                    updated_at = datetime('now')
                WHERE equipment_type = 'gearbox' 
                  AND equipment_id = ? 
                  AND service_item_code = ?
            ");
            
            $update_stmt->execute([
                $current_hours,
                $next_service_hours,
                $notes,
                $performed_by,
                $log_id,
                $gearbox_type,
                $service_code
            ]);
            
            $message = "Service completed and logged successfully! Next " . 
                      $service_item['item_name'] . " due at " . number_format($next_service_hours) . " hours.";
        }
        
    } catch (Exception $e) {
        $error = "Error completing service: " . $e->getMessage();
    }
}

// Get current gearbox hours
$hours_stmt = $pdo->prepare("SELECT total_hours FROM gearbox_hours WHERE gearbox_type = ?");
$hours_stmt->execute([$gearbox_type]);
$gearbox_data = $hours_stmt->fetch(PDO::FETCH_ASSOC);
$current_gearbox_hours = $gearbox_data ? $gearbox_data['total_hours'] : 0;

// Get service items for this gearbox
$services_stmt = $pdo->prepare("
    SELECT si.item_code, si.item_name, si.category, si.description,
           st.last_service_hours, st.last_service_date, st.next_service_hours,
           st.notes, st.performed_by, ss.interval_hours, ss.is_enabled,
           (? - st.last_service_hours) as hours_since_service,
           CASE 
               WHEN (? - st.last_service_hours) >= ss.interval_hours THEN 'overdue'
               WHEN (? - st.last_service_hours) >= (ss.interval_hours - 72) THEN 'due_soon'
               ELSE 'good'
           END as status
    FROM service_items si
    JOIN service_settings ss ON si.item_code = ss.service_item_code
    LEFT JOIN service_tracking st ON si.item_code = st.service_item_code 
                                  AND st.equipment_type = 'gearbox' 
                                  AND st.equipment_id = ?
    WHERE ss.equipment_type = 'gearbox' 
      AND ss.equipment_id = ? 
      AND ss.is_enabled = 1
    ORDER BY si.category, 
             CASE status 
                 WHEN 'overdue' THEN 1 
                 WHEN 'due_soon' THEN 2 
                 ELSE 4 
             END,
             si.item_name
");
$services_stmt->execute([
    $current_gearbox_hours, $current_gearbox_hours, $current_gearbox_hours,
    $gearbox_type, $gearbox_type
]);
$services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group services by category
$grouped_services = [];
foreach ($services as $service) {
    $grouped_services[$service['category']][] = $service;
}

$gearbox_name = ucwords(str_replace('_', ' ', $gearbox_type)) . ' Gearbox';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $gearbox_name; ?> Service Management - Marine Engine Room</title>
    <link rel="stylesheet" href="test_styles.css">
</head>
<body>
    <div class="header">
        <h1>⚙️ <?php echo $gearbox_name; ?> Service Management</h1>
        <p>Track and manage service intervals for gearbox maintenance items</p>
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
        
        <!-- Gearbox Status Summary -->
        <div class="form-section" style="background: #f8f9fa; border: 1px solid #dee2e6;">
            <h3>📊 <?php echo $gearbox_name; ?> Service Status</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;">
                        <?php echo number_format($current_gearbox_hours, 1); ?>
                    </div>
                    <div style="font-size: 14px; color: #6c757d;">Total Gearbox Hours</div>
                </div>
                <?php 
                $overdue = array_filter($services, function($s) { return $s['status'] == 'overdue'; });
                $due_soon = array_filter($services, function($s) { return $s['status'] == 'due_soon'; });
                $upcoming = array_filter($services, function($s) { return $s['status'] == 'upcoming'; });
                ?>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #dc3545;">
                        <?php echo count($overdue); ?>
                    </div>
                    <div style="font-size: 14px; color: #6c757d;">Services Overdue</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #ffc107;">
                        <?php echo count($due_soon); ?>
                    </div>
                    <div style="font-size: 14px; color: #6c757d;">Due Soon</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #28a745;">
                        <?php echo count($services) - count($overdue) - count($due_soon); ?>
                    </div>
                    <div style="font-size: 14px; color: #6c757d;">Current</div>
                </div>
            </div>
        </div>
        
        <!-- Service Items by Category -->
        <?php if (!empty($grouped_services)): ?>
            <?php foreach ($grouped_services as $category => $category_services): ?>
                <div class="form-section">
                    <h3>
                        <?php 
                        $category_icons = [
                            'Filters' => '🔽',
                            'Oil Changes' => '🛢️', 
                            'Maintenance' => '🔧'
                        ];
                        echo ($category_icons[$category] ?? '⚙️') . ' ' . $category;
                        ?>
                    </h3>
                    
                    <div class="service-items-grid" style="display: grid; gap: 15px;">
                        <?php foreach ($category_services as $service): ?>
                            <?php
                            $status_colors = [
                                'overdue' => '#dc3545',
                                'due_soon' => '#ffc107', 
                                'upcoming' => '#fd7e14',
                                'good' => '#28a745'
                            ];
                            $status_labels = [
                                'overdue' => '🔴 OVERDUE',
                                'due_soon' => '🟡 DUE SOON',
                                'upcoming' => '🟠 UPCOMING', 
                                'good' => '🟢 GOOD'
                            ];
                            ?>
                            
                            <div class="service-item-card" style="border: 2px solid <?php echo $status_colors[$service['status']]; ?>; border-radius: 8px; padding: 15px; background: white;">
                                <div style="display: flex; justify-content: between; align-items: flex-start; margin-bottom: 10px;">
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 5px 0; color: #2c3e50;">
                                            <?php echo htmlspecialchars($service['item_name']); ?>
                                        </h4>
                                        <p style="margin: 0; font-size: 12px; color: #6c757d;">
                                            <?php echo htmlspecialchars($service['description']); ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <span style="background: <?php echo $status_colors[$service['status']]; ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">
                                            <?php echo $status_labels[$service['status']]; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="service-details" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin: 15px 0; font-size: 12px;">
                                    <div>
                                        <strong>Last Service:</strong><br>
                                        <?php echo $service['last_service_hours'] ? number_format($service['last_service_hours']) . ' hrs' : 'Never'; ?><br>
                                        <?php echo $service['last_service_date'] ? date('M j, Y', strtotime($service['last_service_date'])) : ''; ?>
                                    </div>
                                    <div>
                                        <strong>Hours Since:</strong><br>
                                        <?php echo number_format($service['hours_since_service'] ?? 0); ?> hrs<br>
                                        <span style="color: #6c757d;">of <?php echo number_format($service['interval_hours']); ?> interval</span>
                                    </div>
                                    <div>
                                        <strong>Next Due:</strong><br>
                                        <?php echo $service['next_service_hours'] ? number_format($service['next_service_hours']) . ' hrs' : 'TBD'; ?><br>
                                        <span style="color: #6c757d;">
                                            <?php 
                                            if ($service['next_service_hours']) {
                                                $hours_until = $service['next_service_hours'] - $current_gearbox_hours;
                                                if ($hours_until <= 0) {
                                                    echo abs($hours_until) . ' hrs overdue';
                                                } else {
                                                    echo $hours_until . ' hrs remaining';
                                                }
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($service['notes']): ?>
                                    <div style="background: #f8f9fa; padding: 8px; border-radius: 4px; margin: 10px 0; font-size: 11px;">
                                        <strong>Last Notes:</strong> <?php echo htmlspecialchars($service['notes']); ?>
                                        <?php if ($service['performed_by']): ?>
                                            <br><em>by <?php echo htmlspecialchars($service['performed_by']); ?></em>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Service Completion Form -->
                                <form method="post" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                                    <div style="display: grid; grid-template-columns: 120px 200px 1fr auto; gap: 10px; align-items: end;">
                                        <div>
                                            <label style="font-size: 11px; color: #495057;">Gearbox Hours:</label>
                                            <input type="number" name="current_hours" value="<?php echo $current_gearbox_hours; ?>" 
                                                   step="0.1" min="0" required
                                                   style="width: 100%; padding: 6px; font-size: 12px; border: 1px solid #ced4da; border-radius: 4px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; color: #495057;">Performed By:</label>
                                            <input type="text" name="performed_by" value="Marine Engineer" 
                                                   style="width: 100%; padding: 6px; font-size: 12px; border: 1px solid #ced4da; border-radius: 4px;">
                                        </div>
                                        <div>
                                            <label style="font-size: 11px; color: #495057;">Notes (optional):</label>
                                            <input type="text" name="notes" placeholder="Service notes..." 
                                                   style="width: 100%; padding: 6px; font-size: 12px; border: 1px solid #ced4da; border-radius: 4px;">
                                        </div>
                                        <div>
                                            <input type="hidden" name="service_code" value="<?php echo $service['item_code']; ?>">
                                            <button type="submit" name="complete_service" 
                                                    style="background: #28a745; color: white; border: none; padding: 8px 15px; border-radius: 4px; font-size: 12px; cursor: pointer;"
                                                    onclick="return confirm('Mark this service as completed?')">
                                                ✅ Complete Service
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="form-section" style="text-align: center; padding: 40px;">
                <h3>No Service Items Configured</h3>
                <p>No service items are currently enabled for this gearbox.</p>
                <a href="settings.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                    Configure Service Items
                </a>
            </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div style="margin: 30px 0; text-align: center;">
            <a href="manage_gearbox.php?type=<?php echo $gearbox_type; ?>" 
               style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;">
                📊 Gearbox Readings
            </a>
            <a href="settings.php" 
               style="background: #f39c12; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;">
                ⚙️ Service Settings
            </a>
            <a href="view_logs.php" 
               style="background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">
                📋 View Logs
            </a>
        </div>
    </div>
</body>
</html>
