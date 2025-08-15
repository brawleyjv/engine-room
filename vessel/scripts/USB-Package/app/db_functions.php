<?php
/**
 * Database connection and helper functions
 */

function getDatabase() {
    $db_path = __DIR__ . '/vessel_data.sqlite';
    
    try {
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Ensure tables exist with correct schema
        ensureTablesExist($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        return null;
    }
}

function ensureTablesExist($pdo) {
    // Check if we need to add columns to existing tables
    
    // For vessel_logs - add engine-specific columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE vessel_logs ADD COLUMN engine_id TEXT");
    } catch (Exception $e) {
        // Column already exists, that's fine
    }
    
    try {
        $pdo->exec("ALTER TABLE vessel_logs ADD COLUMN rpm INTEGER");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE vessel_logs ADD COLUMN temperature REAL");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE vessel_logs ADD COLUMN oil_pressure REAL");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE vessel_logs ADD COLUMN notes TEXT");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE vessel_logs ADD COLUMN log_time DATETIME");
    } catch (Exception $e) {
        // Column already exists
    }
    
    // For navigation_data - add navigation-specific columns
    try {
        $pdo->exec("ALTER TABLE navigation_data ADD COLUMN log_time DATETIME");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE navigation_data ADD COLUMN latitude REAL");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE navigation_data ADD COLUMN longitude REAL");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE navigation_data ADD COLUMN position_text TEXT");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE navigation_data ADD COLUMN speed REAL");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE navigation_data ADD COLUMN course INTEGER");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE navigation_data ADD COLUMN weather TEXT");
    } catch (Exception $e) {
        // Column already exists
    }
    
    try {
        $pdo->exec("ALTER TABLE navigation_data ADD COLUMN notes TEXT");
    } catch (Exception $e) {
        // Column already exists
    }
    
    // For crew_members - ensure date_on and twic_exp_date columns exist  
    try {
        $pdo->exec("ALTER TABLE crew_members ADD COLUMN twic_exp_date DATE");
    } catch (Exception $e) {
        // Column already exists
    }
}

function addEngineLog($engine_id, $rpm, $temperature, $oil_pressure, $notes, $log_time = null) {
    $db = getDatabase();
    if (!$db) return false;
    
    if (!$log_time) {
        $log_time = date('Y-m-d H:i:s');
    }
    
    // Create entry text from the parameters
    $entry_text = "Engine: " . str_replace('_', ' ', ucwords($engine_id, '_'));
    if ($rpm) $entry_text .= " | RPM: " . $rpm;
    if ($temperature) $entry_text .= " | Temp: " . $temperature . "°F";
    if ($oil_pressure) $entry_text .= " | Oil: " . $oil_pressure . " PSI";
    if ($notes) $entry_text .= " | Notes: " . $notes;
    
    try {
        // Use the correct required columns
        $stmt = $db->prepare("INSERT INTO vessel_logs 
            (log_time, engine_id, rpm, temperature, oil_pressure, notes, log_type, entry_text, author, author_role, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'engine', ?, 'vessel_logger', 'system', ?)");
        
        return $stmt->execute([$log_time, $engine_id, $rpm, $temperature, $oil_pressure, $notes, $entry_text, date('Y-m-d H:i:s')]);
    } catch (Exception $e) {
        error_log('Engine log insert failed: ' . $e->getMessage());
        return false;
    }
}

function addNavigationLog($position, $speed, $course, $weather, $notes, $log_time = null) {
    $db = getDatabase();
    if (!$db) return false;
    
    if (!$log_time) {
        $log_time = date('Y-m-d H:i:s');
    }
    
    // Parse position text to extract lat/lon if possible
    $latitude = null;
    $longitude = null;
    if (preg_match('/([0-9.]+)°?\s*[NS],?\s*([0-9.]+)°?\s*[EW]/i', $position, $matches)) {
        $latitude = floatval($matches[1]);
        $longitude = floatval($matches[2]);
    }
    
    // Check what columns exist and insert accordingly
    try {
        // Try with all new columns
        $stmt = $db->prepare("INSERT INTO navigation_data 
            (log_time, latitude, longitude, position_text, speed, course, weather, notes, updated_by, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'system', ?)");
        
        return $stmt->execute([$log_time, $latitude, $longitude, $position, $speed, $course, $weather, $notes, date('Y-m-d H:i:s')]);
    } catch (Exception $e) {
        // Fall back to minimal required columns
        try {
            $stmt = $db->prepare("INSERT INTO navigation_data 
                (destination, updated_by, updated_at) 
                VALUES (?, 'vessel_logger', ?)");
            
            $destination = $position . ($speed ? " @ {$speed}kts" : "") . ($course ? " Course {$course}°" : "");
            if ($weather) $destination .= " - " . $weather;
            if ($notes) $destination .= " (" . $notes . ")";
            
            return $stmt->execute([$destination, date('Y-m-d H:i:s')]);
        } catch (Exception $e2) {
            error_log('Navigation insert failed: ' . $e2->getMessage());
            return false;
        }
    }
}

function addCrewMember($name, $position, $date_on, $date_off, $twic_exp_date) {
    $db = getDatabase();
    if (!$db) return false;
    
    try {
        // Try with all columns
        $stmt = $db->prepare("INSERT INTO crew_members 
            (name, position, date_on, date_off, twic_exp_date, twic_expiry_date, status, added_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'active', 'vessel_logger', ?)");
        
        return $stmt->execute([$name, $position, $date_on, $date_off ?: null, $twic_exp_date, $twic_exp_date, date('Y-m-d H:i:s')]);
    } catch (Exception $e) {
        // Fall back to minimal required columns
        try {
            $stmt = $db->prepare("INSERT INTO crew_members 
                (name, position, date_on, date_off, twic_expiry_date, status, added_by, created_at) 
                VALUES (?, ?, ?, ?, ?, 'active', 'vessel_logger', ?)");
            
            return $stmt->execute([$name, $position, $date_on, $date_off ?: null, $twic_exp_date, date('Y-m-d H:i:s')]);
        } catch (Exception $e2) {
            error_log('Crew member insert failed: ' . $e2->getMessage());
            return false;
        }
    }
}

function getRecentEngineLogs($limit = 10) {
    $db = getDatabase();
    if (!$db) return [];
    
    $stmt = $db->prepare("SELECT * FROM vessel_logs WHERE log_type = 'engine' OR engine_id IS NOT NULL ORDER BY COALESCE(log_time, created_at) DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentNavigationLogs($limit = 10) {
    $db = getDatabase();
    if (!$db) return [];
    
    $stmt = $db->prepare("SELECT * FROM navigation_data WHERE log_time IS NOT NULL OR position_text IS NOT NULL ORDER BY COALESCE(log_time, updated_at) DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCurrentCrew() {
    $db = getDatabase();
    if (!$db) return [];
    
    $stmt = $db->query("SELECT *, 
        CASE 
            WHEN date_off IS NULL THEN 'Active'
            ELSE 'Inactive'
        END as status,
        CASE 
            WHEN date_off IS NULL AND julianday(COALESCE(twic_exp_date, twic_expiry_date)) - julianday('now') < 90 THEN 'Expiring'
            WHEN date_off IS NULL THEN 'Valid'
            ELSE 'Inactive'
        END as twic_status
        FROM crew_members ORDER BY date_on DESC");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUnsyncedData() {
    $db = getDatabase();
    if (!$db) return ['logs' => [], 'navigation' => [], 'crew' => []];
    
    $logs = $db->query("SELECT * FROM vessel_logs WHERE synced_at IS NULL ORDER BY COALESCE(log_time, created_at)")->fetchAll(PDO::FETCH_ASSOC);
    $navigation = $db->query("SELECT * FROM navigation_data WHERE synced_at IS NULL ORDER BY COALESCE(log_time, updated_at)")->fetchAll(PDO::FETCH_ASSOC);
    $crew = $db->query("SELECT * FROM crew_members WHERE synced_at IS NULL ORDER BY created_at")->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'logs' => $logs,
        'navigation' => $navigation,
        'crew' => $crew
    ];
}

function markAsSynced($table, $ids) {
    $db = getDatabase();
    if (!$db || empty($ids)) return false;
    
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $db->prepare("UPDATE {$table} SET synced_at = CURRENT_TIMESTAMP WHERE id IN ({$placeholders})");
    return $stmt->execute($ids);
}
?>
