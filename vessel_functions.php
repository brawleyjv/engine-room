<?php
// Vessel session management functions

function get_active_vessel_id() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return $_SESSION['active_vessel_id'] ?? 1;
}

function get_active_vessel_info($conn) {
    $vessel_id = get_active_vessel_id();
    $sql = "SELECT * FROM vessels WHERE VesselID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $vessel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_all_active_vessels($conn) {
    $sql = "SELECT VesselID, VesselName, VesselType FROM vessels WHERE IsActive = 1 ORDER BY VesselName";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function render_vessel_selector($conn, $current_page = '') {
    $active_vessel = get_active_vessel_info($conn);
    $all_vessels = get_all_active_vessels($conn);
    
    if (!$active_vessel) {
        return '<div class="alert alert-warning">No active vessel selected. <a href="manage_vessels.php">Manage Vessels</a></div>';
    }
    
    ob_start();
    ?>
    <div class="vessel-selector">
        <div class="current-vessel-info">
            <span class="vessel-icon">🚢</span>
            <div class="vessel-details">
                <strong><?= htmlspecialchars($active_vessel['VesselName']) ?></strong>
                <small><?= htmlspecialchars($active_vessel['VesselType']) ?></small>
            </div>
            <?php if (count($all_vessels) > 1): ?>
                <div class="vessel-switch">
                    <select id="vesselSwitch" onchange="switchVessel()" class="form-control">
                        <?php foreach ($all_vessels as $vessel): ?>
                            <option value="<?= $vessel['VesselID'] ?>" 
                                    <?= $vessel['VesselID'] == $active_vessel['VesselID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($vessel['VesselName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <a href="manage_vessels.php" class="btn btn-sm btn-outline">⚙️ Manage</a>
        </div>
    </div>
    
    <style>
        .vessel-selector {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .current-vessel-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .vessel-icon {
            font-size: 24px;
        }
        .vessel-details strong {
            display: block;
            color: #333;
        }
        .vessel-details small {
            color: #666;
        }
        .vessel-switch {
            margin-left: auto;
            margin-right: 10px;
        }
        .vessel-switch select {
            min-width: 200px;
        }
        .btn-outline {
            border: 1px solid #007bff;
            color: #007bff;
            background: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        .btn-outline:hover {
            background: #007bff;
            color: white;
        }
    </style>
    
    <script>
        function switchVessel() {
            const vesselId = document.getElementById('vesselSwitch').value;
            // Send AJAX request to switch vessel
            fetch('switch_vessel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'vessel_id=' + vesselId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to reflect the vessel change
                    window.location.reload();
                } else {
                    alert('Error switching vessel: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error switching vessel');
            });
        }
    </script>
    <?php
    return ob_get_clean();
}
?>
