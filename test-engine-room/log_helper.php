<?php
/**
 * Log Helper Functions
 * Functions to automatically create log entries from engine room activities
 */

function createLogEntry($pdo, $entry_type, $equipment_id, $log_entry, $created_by = 'System') {
    try {
        $log_date = date('Y-m-d');
        $log_time = date('H:i:s');
        
        $stmt = $pdo->prepare("INSERT INTO log_entries 
            (vessel_id, log_date, log_time, entry_type, equipment_id, log_entry, created_by) 
            VALUES (1, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([$log_date, $log_time, $entry_type, $equipment_id, $log_entry, $created_by]);
        
        return true;
    } catch (Exception $e) {
        // Log the error but don't fail the main operation
        error_log("Failed to create log entry: " . $e->getMessage());
        return false;
    }
}

function createEngineLogEntry($pdo, $engine_type, $readings, $created_by = 'Marine Engineer') {
    $log_text = "Engine readings recorded - ";
    $details = [];
    
    if ($readings['rpm']) $details[] = "RPM: {$readings['rpm']}";
    if ($readings['oil_pressure']) $details[] = "Oil Pressure: {$readings['oil_pressure']} PSI";
    if ($readings['water_temp_out']) $details[] = "Water Temp: {$readings['water_temp_out']}°F";
    if ($readings['engine_hours_total']) $details[] = "Total Hours: {$readings['engine_hours_total']}";
    
    $log_text .= implode(', ', $details);
    
    return createLogEntry($pdo, 'engine', $engine_type, $log_text, $created_by);
}

function createGearboxLogEntry($pdo, $gearbox_type, $readings, $created_by = 'Marine Engineer') {
    $log_text = "Gearbox readings recorded - ";
    $details = [];
    
    if ($readings['oil_pressure']) $details[] = "Oil Pressure: {$readings['oil_pressure']} PSI";
    if ($readings['oil_temp']) $details[] = "Oil Temp: {$readings['oil_temp']}°F";
    if ($readings['operating_hours']) $details[] = "Operating Hours: {$readings['operating_hours']}";
    
    $log_text .= implode(', ', $details);
    
    return createLogEntry($pdo, 'gearbox', $gearbox_type, $log_text, $created_by);
}

function createGeneratorLogEntry($pdo, $generator_type, $readings, $created_by = 'Marine Engineer') {
    $log_text = "Generator readings recorded - ";
    $details = [];
    
    if ($readings['voltage']) $details[] = "Voltage: {$readings['voltage']}V";
    if ($readings['frequency']) $details[] = "Frequency: {$readings['frequency']} Hz";
    if ($readings['load_percentage']) $details[] = "Load: {$readings['load_percentage']}%";
    if ($readings['operating_hours']) $details[] = "Hours: {$readings['operating_hours']}";
    
    $log_text .= implode(', ', $details);
    
    return createLogEntry($pdo, 'generator', $generator_type, $log_text, $created_by);
}

function createFluidLogEntry($pdo, $fluid_type, $action, $amount = null, $created_by = 'Marine Engineer') {
    $log_text = ucfirst($fluid_type) . " - " . $action;
    
    if ($amount !== null) {
        $log_text .= " ({$amount} gallons)";
    }
    
    return createLogEntry($pdo, 'fluid', $fluid_type, $log_text, $created_by);
}

function createMaintenanceLogEntry($pdo, $equipment_type, $equipment_id, $maintenance_action, $created_by = 'Marine Engineer') {
    $log_text = ucfirst($equipment_type) . " maintenance - " . $maintenance_action;
    
    return createLogEntry($pdo, 'maintenance', $equipment_id, $log_text, $created_by);
}
?>
