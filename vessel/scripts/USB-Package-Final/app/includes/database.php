<?php
/**
 * SQLite Database Management for Vessel Logger
 * Handles database initialization, schema creation, and migrations
 */

// Prevent direct access
if (!defined('VESSEL_LOGGER')) {
    die('Direct access not permitted');
}

// Global database connection
$vessel_db = null;

/**
 * Initialize SQLite database
 */
function initializeDatabase() {
    global $vessel_db;
    
    try {
        // Create database connection
        $vessel_db = new PDO('sqlite:' . VESSEL_DB_FILE);
        $vessel_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $vessel_db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        // Enable foreign keys
        $vessel_db->exec('PRAGMA foreign_keys = ON');
        
        // Set SQLite optimizations
        $vessel_db->exec('PRAGMA journal_mode = WAL');
        $vessel_db->exec('PRAGMA synchronous = NORMAL');
        $vessel_db->exec('PRAGMA cache_size = 10000');
        $vessel_db->exec('PRAGMA temp_store = MEMORY');
        
        // Create tables if they don't exist
        createDatabaseSchema();
        
        logMessage('Database initialized successfully');
        
    } catch (PDOException $e) {
        logMessage('Database initialization failed: ' . $e->getMessage(), 'ERROR');
        throw $e;
    }
    
    return $vessel_db;
}

/**
 * Get database connection
 */
function getDatabase() {
    global $vessel_db;
    
    if ($vessel_db === null) {
        $vessel_db = initializeDatabase();
    }
    
    return $vessel_db;
}

/**
 * Create database schema
 */
function createDatabaseSchema() {
    global $vessel_db;
    
    // Vessels table
    $vessel_db->exec("
        CREATE TABLE IF NOT EXISTS vessels (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            hull_number TEXT,
            vessel_type TEXT,
            specifications TEXT, -- JSON data
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            synced BOOLEAN DEFAULT FALSE,
            sync_id TEXT
        )
    ");
    
    // Engines table
    $vessel_db->exec("
        CREATE TABLE IF NOT EXISTS engines (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vessel_id INTEGER,
            name TEXT NOT NULL,
            engine_type TEXT,
            model TEXT,
            serial_number TEXT,
            specifications TEXT, -- JSON data
            position TEXT, -- Main, Auxiliary, Generator, etc.
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            synced BOOLEAN DEFAULT FALSE,
            sync_id TEXT,
            FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE
        )
    ");
    
    // Engine logs table
    $vessel_db->exec("
        CREATE TABLE IF NOT EXISTS engine_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vessel_id INTEGER,
            engine_id INTEGER,
            log_datetime DATETIME NOT NULL,
            engine_hours DECIMAL(10,2),
            temperature DECIMAL(5,2),
            oil_pressure DECIMAL(5,2),
            coolant_pressure DECIMAL(5,2),
            rpm INTEGER,
            fuel_consumption DECIMAL(8,3),
            notes TEXT,
            logged_by TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            synced BOOLEAN DEFAULT FALSE,
            sync_id TEXT,
            FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
            FOREIGN KEY (engine_id) REFERENCES engines(id) ON DELETE CASCADE
        )
    ");
    
    // Equipment table
    $vessel_db->exec("
        CREATE TABLE IF NOT EXISTS equipment (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vessel_id INTEGER,
            name TEXT NOT NULL,
            equipment_type TEXT,
            model TEXT,
            serial_number TEXT,
            location TEXT,
            specifications TEXT, -- JSON data
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            synced BOOLEAN DEFAULT FALSE,
            sync_id TEXT,
            FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE
        )
    ");
    
    // Equipment logs table
    $vessel_db->exec("
        CREATE TABLE IF NOT EXISTS equipment_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vessel_id INTEGER,
            equipment_id INTEGER,
            log_datetime DATETIME NOT NULL,
            status TEXT,
            readings TEXT, -- JSON data for various readings
            maintenance_notes TEXT,
            logged_by TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            synced BOOLEAN DEFAULT FALSE,
            sync_id TEXT,
            FOREIGN KEY (vessel_id) REFERENCES vessels(id) ON DELETE CASCADE,
            FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
        )
    ");
    
    // Users table (local vessel users)
    $vessel_db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT DEFAULT 'crew', -- crew, engineer, captain, admin
            email TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            active BOOLEAN DEFAULT TRUE
        )
    ");
    
    // Sync queue table
    $vessel_db->exec("
        CREATE TABLE IF NOT EXISTS sync_queue (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            table_name TEXT NOT NULL,
            record_id INTEGER NOT NULL,
            action TEXT NOT NULL, -- INSERT, UPDATE, DELETE
            data TEXT, -- JSON data
            priority INTEGER DEFAULT 1, -- 1=high, 2=normal, 3=low
            attempts INTEGER DEFAULT 0,
            max_attempts INTEGER DEFAULT 5,
            last_attempt DATETIME,
            error_message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status TEXT DEFAULT 'pending' -- pending, synced, failed
        )
    ");
    
    // Sync log table
    $vessel_db->exec("
        CREATE TABLE IF NOT EXISTS sync_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sync_type TEXT NOT NULL, -- full, incremental, manual
            started_at DATETIME NOT NULL,
            completed_at DATETIME,
            records_sent INTEGER DEFAULT 0,
            records_received INTEGER DEFAULT 0,
            status TEXT DEFAULT 'running', -- running, completed, failed
            error_message TEXT,
            response_data TEXT -- JSON response from server
        )
    ");
    
    // Settings table
    $vessel_db->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Create indexes for better performance
    createDatabaseIndexes();
    
    // Insert default data
    insertDefaultData();
    
    logMessage('Database schema created successfully');
}

/**
 * Create database indexes
 */
function createDatabaseIndexes() {
    global $vessel_db;
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_engine_logs_datetime ON engine_logs(log_datetime)",
        "CREATE INDEX IF NOT EXISTS idx_engine_logs_vessel ON engine_logs(vessel_id)",
        "CREATE INDEX IF NOT EXISTS idx_engine_logs_engine ON engine_logs(engine_id)",
        "CREATE INDEX IF NOT EXISTS idx_engine_logs_synced ON engine_logs(synced)",
        "CREATE INDEX IF NOT EXISTS idx_equipment_logs_datetime ON equipment_logs(log_datetime)",
        "CREATE INDEX IF NOT EXISTS idx_equipment_logs_vessel ON equipment_logs(vessel_id)",
        "CREATE INDEX IF NOT EXISTS idx_equipment_logs_synced ON equipment_logs(synced)",
        "CREATE INDEX IF NOT EXISTS idx_sync_queue_status ON sync_queue(status)",
        "CREATE INDEX IF NOT EXISTS idx_sync_queue_priority ON sync_queue(priority)",
        "CREATE INDEX IF NOT EXISTS idx_vessels_synced ON vessels(synced)",
        "CREATE INDEX IF NOT EXISTS idx_engines_synced ON engines(synced)"
    ];
    
    foreach ($indexes as $index) {
        $vessel_db->exec($index);
    }
}

/**
 * Insert default data
 */
function insertDefaultData() {
    global $vessel_db;
    
    // Check if default vessel exists
    $stmt = $vessel_db->prepare("SELECT COUNT(*) FROM vessels");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert default vessel (will be updated during setup)
        $stmt = $vessel_db->prepare("
            INSERT INTO vessels (name, hull_number, vessel_type, specifications) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            'Default Vessel',
            'IMO-0000000',
            'Cargo',
            json_encode([
                'length' => 0,
                'beam' => 0,
                'draft' => 0,
                'gross_tonnage' => 0
            ])
        ]);
    }
    
    // Insert default settings
    $default_settings = [
        'app_version' => VESSEL_APP_VERSION,
        'last_backup' => date('Y-m-d H:i:s'),
        'sync_interval' => VESSEL_SYNC_INTERVAL,
        'data_retention_days' => 90
    ];
    
    foreach ($default_settings as $key => $value) {
        $stmt = $vessel_db->prepare("
            INSERT OR REPLACE INTO settings (key, value, updated_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$key, $value]);
    }
}

/**
 * Get database statistics
 */
function getDatabaseStats() {
    global $vessel_db;
    
    $stats = [];
    
    $tables = ['vessels', 'engines', 'engine_logs', 'equipment', 'equipment_logs', 'users', 'sync_queue'];
    
    foreach ($tables as $table) {
        $stmt = $vessel_db->prepare("SELECT COUNT(*) FROM $table");
        $stmt->execute();
        $stats[$table] = $stmt->fetchColumn();
    }
    
    // Get unsynced records count
    $stmt = $vessel_db->prepare("
        SELECT 
            COUNT(CASE WHEN synced = 0 THEN 1 END) as unsynced_logs,
            COUNT(*) as total_logs
        FROM engine_logs
    ");
    $stmt->execute();
    $log_stats = $stmt->fetch();
    $stats['unsynced_logs'] = $log_stats['unsynced_logs'];
    $stats['total_logs'] = $log_stats['total_logs'];
    
    // Get database file size
    $stats['database_size'] = file_exists(VESSEL_DB_FILE) ? filesize(VESSEL_DB_FILE) : 0;
    
    return $stats;
}

/**
 * Backup database
 */
function backupDatabase() {
    $backup_file = VESSEL_DB_BACKUP_DIR . '/vessel_backup_' . date('Y-m-d_H-i-s') . '.db';
    
    if (copy(VESSEL_DB_FILE, $backup_file)) {
        logMessage("Database backed up to: $backup_file");
        
        // Compress backup
        if (function_exists('gzopen')) {
            $compressed_file = $backup_file . '.gz';
            $source = file_get_contents($backup_file);
            $gz = gzopen($compressed_file, 'w9');
            gzwrite($gz, $source);
            gzclose($gz);
            unlink($backup_file);
            
            logMessage("Database backup compressed: $compressed_file");
            return $compressed_file;
        }
        
        return $backup_file;
    }
    
    logMessage("Database backup failed", 'ERROR');
    return false;
}

/**
 * Vacuum database (cleanup and optimize)
 */
function vacuumDatabase() {
    global $vessel_db;
    
    try {
        $vessel_db->exec('VACUUM');
        logMessage('Database vacuumed successfully');
        return true;
    } catch (PDOException $e) {
        logMessage('Database vacuum failed: ' . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Get setting value
 */
function getSetting($key, $default = null) {
    global $vessel_db;
    
    $stmt = $vessel_db->prepare("SELECT value FROM settings WHERE key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    
    return $result !== false ? $result : $default;
}

/**
 * Set setting value
 */
function setSetting($key, $value) {
    global $vessel_db;
    
    $stmt = $vessel_db->prepare("
        INSERT OR REPLACE INTO settings (key, value, updated_at) 
        VALUES (?, ?, CURRENT_TIMESTAMP)
    ");
    
    return $stmt->execute([$key, $value]);
}
?>
