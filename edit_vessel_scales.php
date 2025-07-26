<?php
require_once 'config.php';
require_once 'vessel_functions.php';
require_once 'auth_functions.php';

// Require admin access for editing vessel scales
require_admin();

$vessel_id = $_GET['vessel_id'] ?? null;
$message = '';
$message_type = '';

// If no vessel_id provided, get current vessel
if (!$vessel_id) {
    $current_vessel = get_current_vessel($conn);
    if ($current_vessel) {
        $vessel_id = $current_vessel['VesselID'];
    } else {
        header('Location: manage_vessels.php?error=no_vessel_selected');
        exit;
    }
}

// Get vessel info
$sql = "SELECT * FROM vessels WHERE VesselID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $vessel_id);
$stmt->execute();
$vessel = $stmt->get_result()->fetch_assoc();

if (!$vessel) {
    die('Vessel not found');
}

// Handle form submission
if ($_POST) {
    $rpm_min = (int)$_POST['rpm_min'];
    $rpm_max = (int)$_POST['rpm_max'];
    $temp_min = (int)$_POST['temp_min'];
    $temp_max = (int)$_POST['temp_max'];
    $gen_min = (int)$_POST['gen_min'];
    $gen_max = (int)$_POST['gen_max'];
    
    $sql = "UPDATE vessels SET RPMMin=?, RPMMax=?, TempMin=?, TempMax=?, GenMin=?, GenMax=? WHERE VesselID=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiiiiii', $rpm_min, $rpm_max, $temp_min, $temp_max, $gen_min, $gen_max, $vessel_id);
    
    if ($stmt->execute()) {
        $message = 'Chart scales updated successfully!';
        $message_type = 'success';
        // Refresh vessel data
        $stmt = $conn->prepare("SELECT * FROM vessels WHERE VesselID = ?");
        $stmt->bind_param('i', $vessel_id);
        $stmt->execute();
        $vessel = $stmt->get_result()->fetch_assoc();
    } else {
        $message = 'Error updating scales: ' . $conn->error;
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Chart Scales - <?= htmlspecialchars($vessel['VesselName']) ?> - Vessel Data Logger</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .scale-form {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .section-header {
            background: #f8f9fa;
            padding: 15px;
            margin: 20px -30px;
            border-left: 4px solid #007bff;
            font-weight: bold;
            color: #333;
        }
        .current-scales {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .preview-note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Edit Chart Scales: <?= htmlspecialchars($vessel['VesselName']) ?></h1>
            <p>
                <a href="manage_vessels.php" class="btn btn-info">← Back to Vessels</a>
                <a href="index.php" class="btn btn-primary">🏠 Home</a>
            </p>
        </header>

        <div class="scale-form">
            <?php if ($message): ?>
                <div class="message <?= $message_type ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div class="current-scales">
                <h3>ℹ️ About Chart Scales</h3>
                <p>These settings control the Y-axis ranges on all charts for this vessel. Set them based on your engine's normal operating ranges for optimal chart readability.</p>
            </div>

            <form method="POST">
                <div class="section-header">🚀 RPM Scale</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="rpm_min">RPM Minimum</label>
                        <input type="number" id="rpm_min" name="rpm_min" value="<?= $vessel['RPMMin'] ?>" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="rpm_max">RPM Maximum</label>
                        <input type="number" id="rpm_max" name="rpm_max" value="<?= $vessel['RPMMax'] ?>" min="0" required>
                    </div>
                </div>

                <div class="section-header">🌡️ Temperature/Pressure Scale (°F/PSI)</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="temp_min">Temp/Pressure Minimum</label>
                        <input type="number" id="temp_min" name="temp_min" value="<?= $vessel['TempMin'] ?>" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="temp_max">Temp/Pressure Maximum</label>
                        <input type="number" id="temp_max" name="temp_max" value="<?= $vessel['TempMax'] ?>" min="0" required>
                    </div>
                </div>

                <div class="section-header">⚡ Generator Scale (°F/PSI)</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="gen_min">Generator Temp/Pressure Minimum</label>
                        <input type="number" id="gen_min" name="gen_min" value="<?= $vessel['GenMin'] ?>" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="gen_max">Generator Temp/Pressure Maximum</label>
                        <input type="number" id="gen_max" name="gen_max" value="<?= $vessel['GenMax'] ?>" min="0" required>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn btn-success">💾 Update Chart Scales</button>
                    <a href="manage_vessels.php" class="btn btn-info">Cancel</a>
                </div>
            </form>

            <div class="preview-note">
                <strong>💡 Tip:</strong> After updating scales, view your charts to see how they look. You can always come back and adjust these values as needed.
            </div>
        </div>

        <footer>
            <p>&copy; 2025 Vessel Data Logger | <a href="index.php">Home</a></p>
        </footer>
    </div>
</body>
</html>
