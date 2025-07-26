<?php
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

// Require login first
require_login();

$user = get_logged_in_user();
$error = '';
$success = '';

// Handle vessel selection
if ($_POST && isset($_POST['select_vessel'])) {
    $vessel_id = intval($_POST['vessel_id']);
    
    // Verify vessel exists and is active
    $sql = "SELECT VesselID, VesselName, VesselType FROM vessels WHERE VesselID = ? AND IsActive = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $vessel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($vessel = $result->fetch_assoc()) {
        $_SESSION['current_vessel_id'] = $vessel['VesselID'];
        $_SESSION['current_vessel_name'] = $vessel['VesselName'];
        $_SESSION['current_vessel_type'] = $vessel['VesselType'];
        
        $success = "Vessel selected: " . $vessel['VesselName'];
        
        // Redirect to originally requested page or dashboard
        $redirect = $_GET['redirect'] ?? 'dashboard.php';
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = "Invalid vessel selected.";
    }
}

// Get available vessels
$vessels_sql = "SELECT VesselID, VesselName, VesselType FROM vessels WHERE IsActive = 1 ORDER BY VesselType, VesselName";
$vessels_result = mysqli_query($conn, $vessels_sql);
$vessels = [];
if ($vessels_result) {
    while ($row = mysqli_fetch_assoc($vessels_result)) {
        $vessels[] = $row;
    }
}

// Check if user already has a vessel selected
$current_vessel = get_current_vessel($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Vessel - Engine Room Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .vessel-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .vessel-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .vessel-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .vessel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .vessel-card {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .vessel-card:hover {
            border-color: #3498db;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
        }
        .vessel-card.selected {
            border-color: #3498db;
            background: #e3f2fd;
        }
        .vessel-type {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .vessel-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .select-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        .select-btn:hover {
            background: #2980b9;
        }
        .current-vessel {
            background: #d4edda;
            border: 2px solid #27ae60;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        .user-info {
            text-align: right;
            margin-bottom: 20px;
            color: #666;
        }
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        .action-buttons a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
        .continue-btn {
            background: #27ae60;
            color: white;
        }
        .continue-btn:hover {
            background: #229954;
        }
        .logout-btn {
            background: #6c757d;
            color: white;
        }
        .logout-btn:hover {
            background: #545b62;
        }
    </style>
</head>
<body>
    <div class="vessel-container">
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($user['full_name']); ?> | 
            <a href="logout.php" style="color: #e74c3c;">Logout</a>
        </div>
        
        <div class="vessel-header">
            <h1>🚢 Select Your Vessel</h1>
            <p>Choose the vessel you are currently aboard to begin logging data</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($current_vessel): ?>
            <div class="current-vessel">
                <strong>✅ Currently selected vessel:</strong><br>
                <?php echo htmlspecialchars($current_vessel['VesselName']); ?> 
                (<?php echo htmlspecialchars($current_vessel['VesselType']); ?>)
            </div>
            
            <div class="action-buttons">
                <a href="dashboard.php" class="continue-btn">Continue to Dashboard</a>
                <span style="margin: 0 10px;">or select a different vessel below</span>
            </div>
        <?php endif; ?>

        <?php if (empty($vessels)): ?>
            <div class="error">
                <strong>No vessels available</strong><br>
                Please contact an administrator to set up vessels before you can log data.
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="vessel-grid">
                    <?php 
                    $vessel_types = [];
                    foreach ($vessels as $vessel) {
                        $vessel_types[$vessel['VesselType']][] = $vessel;
                    }
                    
                    foreach ($vessel_types as $type => $type_vessels): 
                    ?>
                        <?php foreach ($type_vessels as $vessel): ?>
                            <div class="vessel-card <?php echo ($current_vessel && $current_vessel['VesselID'] == $vessel['VesselID']) ? 'selected' : ''; ?>" 
                                 onclick="selectVessel(<?php echo $vessel['VesselID']; ?>)">
                                <div class="vessel-type"><?php echo htmlspecialchars($vessel['VesselType']); ?></div>
                                <div class="vessel-name"><?php echo htmlspecialchars($vessel['VesselName']); ?></div>
                                <button type="button" class="select-btn" onclick="selectVessel(<?php echo $vessel['VesselID']; ?>)">
                                    <?php echo ($current_vessel && $current_vessel['VesselID'] == $vessel['VesselID']) ? 'Selected' : 'Select'; ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
                
                <input type="hidden" name="vessel_id" id="selectedVesselId" value="">
                <input type="hidden" name="select_vessel" value="1">
            </form>
        <?php endif; ?>

        <?php if (is_admin()): ?>
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                <a href="manage_vessels.php" style="color: #666; text-decoration: none;">
                    ⚙️ Manage Vessels (Admin)
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function selectVessel(vesselId) {
            document.getElementById('selectedVesselId').value = vesselId;
            
            // Update visual selection
            document.querySelectorAll('.vessel-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Submit form
            document.querySelector('form').submit();
        }
    </script>
</body>
</html>
