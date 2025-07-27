<?php
session_start();
require_once 'config.php';
require_once 'vessel_functions.php';

header('Content-Type: application/json');

if ($_POST && isset($_POST['vessel_id'])) {
    $vessel_id = (int)$_POST['vessel_id'];
    
    // Verify vessel exists and is active
    $sql = "SELECT VesselID, VesselName FROM vessels WHERE VesselID = ? AND IsActive = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $vessel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $vessel = $result->fetch_assoc();
        set_active_vessel($vessel_id, $vessel['VesselName']);
        
        echo json_encode([
            'success' => true,
            'vessel_name' => $vessel['VesselName'],
            'message' => 'Switched to vessel: ' . $vessel['VesselName']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid vessel ID or vessel is not active'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Missing vessel ID'
    ]);
}
?>
