<?php
/**
 * Vessel Fuel API Endpoint
 * Handles vessel fuel data synchronization from vessel SQLite to main MariaDB
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Include database configuration
require_once __DIR__ . '/../../config.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    if (!isset($input['vessel_name']) || !isset($input['hin']) || !isset($input['fuel_data'])) {
        throw new Exception('Missing required fields: vessel_name, hin, fuel_data');
    }
    
    $vessel_name = trim($input['vessel_name']);
    $hin = trim($input['hin']);
    $vessel_id = $input['vessel_id'] ?? null;
    $fuel_data = $input['fuel_data'];
    
    // Connect to database
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // First, find or create the vessel record
    $vessel_db_id = getOrCreateVessel($pdo, $vessel_name, $hin, $vessel_id);
    
    // Insert the fuel data
    if ($input['action'] === 'add_fuel_level') {
        $stmt = $pdo->prepare("
            INSERT INTO fuel_levels_main (
                vessel_id, 
                fuel_level, 
                tank_capacity, 
                consumption_rate,
                estimated_hours_remaining,
                fuel_type,
                temperature,
                density,
                notes,
                vessel_fuel_id,
                vessel_created_at,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $vessel_db_id,
            $fuel_data['fuel_level'] ?? 0,
            $fuel_data['tank_capacity'] ?? 0,
            $fuel_data['consumption_rate'] ?? 0,
            $fuel_data['estimated_hours_remaining'] ?? 0,
            $fuel_data['fuel_type'] ?? 'diesel',
            $fuel_data['temperature'] ?? null,
            $fuel_data['density'] ?? null,
            $fuel_data['notes'] ?? '',
            $fuel_data['id'], // Original vessel SQLite ID
            $fuel_data['created_at'] ?? date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Fuel level synchronized',
            'vessel_id' => $vessel_db_id
        ]);
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Get or create vessel record in main database
 */
function getOrCreateVessel($pdo, $vessel_name, $hin, $vessel_id = null) {
    // First try to find by vessel_name (primary identifier)
    $stmt = $pdo->prepare("SELECT id FROM vessels_main WHERE vessel_name = ? AND hin = ?");
    $stmt->execute([$vessel_name, $hin]);
    $vessel = $stmt->fetch();
    
    if ($vessel) {
        return $vessel['id'];
    }
    
    // If not found, create new vessel record
    $stmt = $pdo->prepare("
        INSERT INTO vessels_main (
            vessel_name, 
            hin, 
            external_vessel_id,
            status,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, 'active', NOW(), NOW())
    ");
    
    $stmt->execute([$vessel_name, $hin, $vessel_id]);
    return $pdo->lastInsertId();
}
?>
