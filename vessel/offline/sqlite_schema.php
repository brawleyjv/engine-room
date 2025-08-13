<?php
/**
 * SQLite Schema Generator for Offline Vessel Operations
 * Creates a local SQLite database that mirrors the MariaDB structure
 */

class OfflineSchemaGenerator {
    private $sqlite_path;
    private $mariadb_conn;
    
    public function __construct($sqlite_path, $mariadb_conn = null) {
        $this->sqlite_path = $sqlite_path;
        $this->mariadb_conn = $mariadb_conn;
    }
    
    public function generateSchema() {
        // Create SQLite database
        $sqlite = new SQLite3($this->sqlite_path);
        
        // Enable foreign keys
        $sqlite->exec('PRAGMA foreign_keys = ON;');
        
        // Create tables that mirror MariaDB structure
        $this->createVesselsTable($sqlite);
        $this->createUsersTable($sqlite);
        $this->createMainEnginesTable($sqlite);
        $this->createGeneratorsTable($sqlite);
        $this->createGearsTable($sqlite);
        $this->createSyncQueueTable($sqlite);
        $this->createOfflineMetadataTable($sqlite);
        
        return $sqlite;
    }
    
    private function createVesselsTable($sqlite) {
        $sql = "
        CREATE TABLE IF NOT EXISTS vessels (
            VesselID INTEGER PRIMARY KEY,
            VesselName TEXT NOT NULL,
            VesselType TEXT,
            EngineConfig TEXT DEFAULT 'standard',
            CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            IsActive INTEGER DEFAULT 1,
            LastSync DATETIME,
            SyncStatus TEXT DEFAULT 'pending'
        )";
        $sqlite->exec($sql);
    }
    
    private function createUsersTable($sqlite) {
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            UserID INTEGER PRIMARY KEY,
            FirstName TEXT NOT NULL,
            LastName TEXT NOT NULL,
            Email TEXT UNIQUE NOT NULL,
            Password TEXT NOT NULL,
            IsAdmin INTEGER DEFAULT 0,
            IsActive INTEGER DEFAULT 1,
            CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            LastSync DATETIME,
            SyncStatus TEXT DEFAULT 'pending'
        )";
        $sqlite->exec($sql);
    }
    
    private function createMainEnginesTable($sqlite) {
        $sql = "
        CREATE TABLE IF NOT EXISTS mainengines (
            EntryID INTEGER PRIMARY KEY,
            VesselID INTEGER NOT NULL,
            EntryDate DATE NOT NULL,
            Side TEXT NOT NULL,
            RPM INTEGER,
            MainHrs REAL,
            OilPressure INTEGER,
            OilTemp INTEGER,
            FuelPress INTEGER,
            WaterTemp INTEGER,
            RecordedBy INTEGER,
            Notes TEXT,
            Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            LastSync DATETIME,
            SyncStatus TEXT DEFAULT 'pending',
            LocalID TEXT UNIQUE, -- UUID for offline tracking
            FOREIGN KEY (VesselID) REFERENCES vessels(VesselID),
            FOREIGN KEY (RecordedBy) REFERENCES users(UserID)
        )";
        $sqlite->exec($sql);
    }
    
    private function createGeneratorsTable($sqlite) {
        $sql = "
        CREATE TABLE IF NOT EXISTS generators (
            GenID INTEGER PRIMARY KEY,
            VesselID INTEGER NOT NULL,
            EntryDate DATE NOT NULL,
            Side TEXT NOT NULL,
            GenHrs REAL,
            OilPress INTEGER,
            FuelPress INTEGER,
            WaterTemp INTEGER,
            RecordedBy INTEGER,
            Notes TEXT,
            Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            LastSync DATETIME,
            SyncStatus TEXT DEFAULT 'pending',
            LocalID TEXT UNIQUE,
            FOREIGN KEY (VesselID) REFERENCES vessels(VesselID),
            FOREIGN KEY (RecordedBy) REFERENCES users(UserID)
        )";
        $sqlite->exec($sql);
    }
    
    private function createGearsTable($sqlite) {
        $sql = "
        CREATE TABLE IF NOT EXISTS gears (
            GearID INTEGER PRIMARY KEY,
            VesselID INTEGER NOT NULL,
            EntryDate DATE NOT NULL,
            Side TEXT NOT NULL,
            GearHrs REAL,
            OilPress INTEGER,
            Temp INTEGER,
            RecordedBy INTEGER,
            Notes TEXT,
            Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            LastSync DATETIME,
            SyncStatus TEXT DEFAULT 'pending',
            LocalID TEXT UNIQUE,
            FOREIGN KEY (VesselID) REFERENCES vessels(VesselID),
            FOREIGN KEY (RecordedBy) REFERENCES users(UserID)
        )";
        $sqlite->exec($sql);
    }
    
    private function createSyncQueueTable($sqlite) {
        $sql = "
        CREATE TABLE IF NOT EXISTS sync_queue (
            QueueID INTEGER PRIMARY KEY AUTOINCREMENT,
            TableName TEXT NOT NULL,
            RecordID TEXT NOT NULL, -- LocalID for new records, actual ID for updates
            Operation TEXT NOT NULL, -- 'INSERT', 'UPDATE', 'DELETE'
            Data TEXT, -- JSON data for the operation
            Priority INTEGER DEFAULT 5, -- 1=highest, 10=lowest
            Attempts INTEGER DEFAULT 0,
            LastAttempt DATETIME,
            Status TEXT DEFAULT 'pending', -- 'pending', 'processing', 'completed', 'failed'
            ErrorMessage TEXT,
            CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $sqlite->exec($sql);
    }
    
    private function createOfflineMetadataTable($sqlite) {
        $sql = "
        CREATE TABLE IF NOT EXISTS offline_metadata (
            MetaID INTEGER PRIMARY KEY AUTOINCREMENT,
            LastFullSync DATETIME,
            LastPartialSync DATETIME,
            VesselID INTEGER,
            UserID INTEGER,
            AppVersion TEXT,
            DatabaseVersion INTEGER DEFAULT 1,
            Status TEXT DEFAULT 'initialized', -- 'initialized', 'syncing', 'offline', 'online'
            PendingChanges INTEGER DEFAULT 0,
            CreatedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
            UpdatedDate DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $sqlite->exec($sql);
    }
    
    public function seedInitialData($sqlite, $vessel_id, $user_id) {
        // Skip seeding if no MariaDB connection
        if (!$this->mariadb_conn) {
            // Insert basic metadata for offline-only mode
            $insert_meta = "INSERT INTO offline_metadata 
                           (LastFullSync, VesselID, UserID, AppVersion, Status) 
                           VALUES (datetime('now'), ?, ?, '2.0.0', 'offline_only')";
            $stmt = $sqlite->prepare($insert_meta);
            $stmt->bindValue(1, $vessel_id);
            $stmt->bindValue(2, $user_id);
            $stmt->execute();
            return;
        }
        
        // Insert current vessel data
        $vessel_query = "SELECT * FROM vessels WHERE VesselID = ? AND IsActive = 1";
        $stmt = $this->mariadb_conn->prepare($vessel_query);
        $stmt->bind_param('i', $vessel_id);
        $stmt->execute();
        $vessel = $stmt->get_result()->fetch_assoc();
        
        if ($vessel) {
            $insert_vessel = "INSERT OR REPLACE INTO vessels 
                             (VesselID, VesselName, VesselType, EngineConfig, CreatedDate, IsActive, LastSync, SyncStatus) 
                             VALUES (?, ?, ?, ?, ?, ?, datetime('now'), 'synced')";
            $stmt = $sqlite->prepare($insert_vessel);
            $stmt->bindValue(1, $vessel['VesselID']);
            $stmt->bindValue(2, $vessel['VesselName']);
            $stmt->bindValue(3, $vessel['VesselType']);
            $stmt->bindValue(4, $vessel['EngineConfig']);
            $stmt->bindValue(5, $vessel['CreatedDate']);
            $stmt->bindValue(6, $vessel['IsActive']);
            $stmt->execute();
        }
        
        // Insert current user data
        $user_query = "SELECT * FROM users WHERE UserID = ? AND IsActive = 1";
        $stmt = $this->mariadb_conn->prepare($user_query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
            $insert_user = "INSERT OR REPLACE INTO users 
                           (UserID, FirstName, LastName, Email, Password, IsAdmin, IsActive, CreatedDate, LastSync, SyncStatus) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), 'synced')";
            $stmt = $sqlite->prepare($insert_user);
            $stmt->bindValue(1, $user['UserID']);
            $stmt->bindValue(2, $user['FirstName']);
            $stmt->bindValue(3, $user['LastName']);
            $stmt->bindValue(4, $user['Email']);
            $stmt->bindValue(5, $user['Password']);
            $stmt->bindValue(6, $user['IsAdmin']);
            $stmt->bindValue(7, $user['IsActive']);
            $stmt->bindValue(8, $user['CreatedDate']);
            $stmt->execute();
        }
        
        // Insert recent equipment data (last 30 days)
        $this->seedEquipmentData($sqlite, $vessel_id, 'mainengines');
        $this->seedEquipmentData($sqlite, $vessel_id, 'generators');
        $this->seedEquipmentData($sqlite, $vessel_id, 'gears');
        
        // Insert metadata
        $insert_meta = "INSERT INTO offline_metadata 
                       (LastFullSync, VesselID, UserID, AppVersion, Status) 
                       VALUES (datetime('now'), ?, ?, '2.0.0', 'online')";
        $stmt = $sqlite->prepare($insert_meta);
        $stmt->bindValue(1, $vessel_id);
        $stmt->bindValue(2, $user_id);
        $stmt->execute();
    }
    
    private function seedEquipmentData($sqlite, $vessel_id, $table) {
        // Get recent data from MariaDB
        $query = "SELECT * FROM $table WHERE VesselID = ? AND EntryDate >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY EntryDate DESC";
        $stmt = $this->mariadb_conn->prepare($query);
        $stmt->bind_param('i', $vessel_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $id_field = $table === 'mainengines' ? 'EntryID' : ($table === 'generators' ? 'GenID' : 'GearID');
        $hrs_field = $table === 'mainengines' ? 'MainHrs' : ($table === 'generators' ? 'GenHrs' : 'GearHrs');
        
        while ($row = $result->fetch_assoc()) {
            $uuid = $this->generateUUID();
            
            if ($table === 'mainengines') {
                $insert = "INSERT OR REPLACE INTO mainengines 
                          (EntryID, VesselID, EntryDate, Side, RPM, MainHrs, OilPressure, OilTemp, FuelPress, WaterTemp, 
                           RecordedBy, Notes, Timestamp, CreatedDate, LastSync, SyncStatus, LocalID) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), 'synced', ?)";
                $stmt = $sqlite->prepare($insert);
                $stmt->bindValue(1, $row['EntryID']);
                $stmt->bindValue(2, $row['VesselID']);
                $stmt->bindValue(3, $row['EntryDate']);
                $stmt->bindValue(4, $row['Side']);
                $stmt->bindValue(5, $row['RPM']);
                $stmt->bindValue(6, $row['MainHrs']);
                $stmt->bindValue(7, $row['OilPressure']);
                $stmt->bindValue(8, $row['OilTemp']);
                $stmt->bindValue(9, $row['FuelPress']);
                $stmt->bindValue(10, $row['WaterTemp']);
                $stmt->bindValue(11, $row['RecordedBy']);
                $stmt->bindValue(12, $row['Notes']);
                $stmt->bindValue(13, $row['Timestamp']);
                $stmt->bindValue(14, $row['CreatedDate']);
                $stmt->bindValue(15, $uuid);
                $stmt->execute();
            }
            // Similar for generators and gears...
        }
    }
    
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>
