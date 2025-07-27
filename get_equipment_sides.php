<?php
session_start();
require_once 'config.php';
require_once 'vessel_functions.php';
require_once 'auth_functions.php';

// Require login
require_login();

// Get current vessel
$current_vessel = get_current_vessel($conn);
if (!$current_vessel) {
    http_response_code(400);
    echo json_encode(['error' => 'No vessel selected']);
    exit;
}

$equipment_type = $_GET['equipment_type'] ?? '';

if (empty($equipment_type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Equipment type required']);
    exit;
}

// Get sides for this equipment type
$sides = get_vessel_sides($conn, $current_vessel['VesselID'], $equipment_type);

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['sides' => $sides]);
?>
