<?php
/**
 * Offline Sync Manager for Vessel Logger
 * Handles bidirectional synchronization between SQLite (offline) and MariaDB (online)
 */

require_once __DIR__ . '/sqlite_schema.php';

class OfflineSyncManager {
    private $mariadb_conn;
    private $sqlite_path;
    private $sqlite_conn;
    private $vessel_id;
    private $user_id;
    private $sync_log = [];
    
    public function __construct($mariadb_conn, $sqlite_path, $vessel_id, $user_id) {
        $this->mariadb_conn = $mariadb_conn;
        $this->sqlite_path = $sqlite_path;
        $this->vessel_id = $vessel_id;
        $this->user_id = $user_id;
        
        $this->initializeSQLite();
    }
    
    private function initializeSQLite() {
        if (!file_exists($this->sqlite_path)) {
            $generator = new OfflineSchemaGenerator($this->sqlite_path, $this->mariadb_conn);
            $this->sqlite_conn = $generator->generateSchema();
            $generator->seedInitialData($this->sqlite_conn, $this->vessel_id, $this->user_id);
        } else {
            $this->sqlite_conn = new SQLite3($this->sqlite_path);
            $this->sqlite_conn->exec('PRAGMA foreign_keys = ON;');
        }
    }
    
    public function getSQLiteConnection() {
        return $this->sqlite_conn;
    }
    
    /**
     * Perform full bidirectional sync
     */
    public function performFullSync() {
        $this->log("Starting full sync");
        
        try {
            // Update metadata to syncing status
            $this->updateSyncStatus('syncing');
            
            // First, push local changes to server
            $push_result = $this->pushLocalChanges();
            
            // Then, pull server changes to local
            $pull_result = $this->pullServerChanges();
            
            // Update sync metadata
            $this->updateSyncMetadata();
            
            $this->updateSyncStatus('online');
            $this->log("Full sync completed successfully");
            
            return [
                'success' => true,
                'pushed' => $push_result,
                'pulled' => $pull_result,
                'log' => $this->sync_log
            ];
            
        } catch (Exception $e) {
            $this->updateSyncStatus('offline');
            $this->log("Sync failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'log' => $this->sync_log
            ];
        }
    }
    
    /**
     * Push local changes to MariaDB
     */
    private function pushLocalChanges() {
        $this->log("Pushing local changes to server");
        
        $queue_query = "SELECT * FROM sync_queue WHERE Status = 'pending' ORDER BY Priority ASC, CreatedDate ASC";
        $result = $this->sqlite_conn->query($queue_query);
        
        $pushed_count = 0;
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            try {
                $this->processQueueItem($row);
                
                // Mark as completed
                $update_queue = "UPDATE sync_queue SET Status = 'completed', LastAttempt = datetime('now') WHERE QueueID = ?";
                $stmt = $this->sqlite_conn->prepare($update_queue);
                $stmt->bindValue(1, $row['QueueID']);
                $stmt->execute();
                
                $pushed_count++;
                $this->log("Pushed {$row['Operation']} for {$row['TableName']} ID {$row['RecordID']}");
                
            } catch (Exception $e) {
                // Mark as failed and increment attempts
                $update_queue = "UPDATE sync_queue SET Status = 'failed', Attempts = Attempts + 1, 
                                ErrorMessage = ?, LastAttempt = datetime('now') WHERE QueueID = ?";
                $stmt = $this->sqlite_conn->prepare($update_queue);
                $stmt->bindValue(1, $e->getMessage());
                $stmt->bindValue(2, $row['QueueID']);
                $stmt->execute();
                
                $this->log("Failed to push {$row['Operation']} for {$row['TableName']}: " . $e->getMessage());
            }
        }
        
        return $pushed_count;
    }
    
    private function processQueueItem($queue_item) {
        $table = $queue_item['TableName'];
        $operation = $queue_item['Operation'];
        $data = json_decode($queue_item['Data'], true);
        
        switch ($operation) {
            case 'INSERT':
                $this->insertToMariaDB($table, $data, $queue_item['RecordID']);
                break;
                
            case 'UPDATE':
                $this->updateMariaDB($table, $data, $queue_item['RecordID']);
                break;
                
            case 'DELETE':
                $this->deleteFromMariaDB($table, $queue_item['RecordID']);
                break;
        }
    }
    
    private function insertToMariaDB($table, $data, $local_id) {
        $id_field = $this->getIdField($table);
        $fields = array_keys($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        // Remove LocalID and SyncStatus from data for MariaDB
        unset($data['LocalID']);
        unset($data['SyncStatus']);
        unset($data['LastSync']);
        
        $sql = "INSERT INTO $table (" . implode(',', array_keys($data)) . ") VALUES ($placeholders)";
        $stmt = $this->mariadb_conn->prepare($sql);
        
        $values = array_values($data);
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        $new_id = $this->mariadb_conn->insert_id;
        
        // Update local record with MariaDB ID
        $update_local = "UPDATE $table SET $id_field = ?, SyncStatus = 'synced', LastSync = datetime('now') WHERE LocalID = ?";
        $stmt = $this->sqlite_conn->prepare($update_local);
        $stmt->bindValue(1, $new_id);
        $stmt->bindValue(2, $local_id);
        $stmt->execute();
        
        return $new_id;
    }
    
    private function updateMariaDB($table, $data, $record_id) {
        $id_field = $this->getIdField($table);
        
        // Remove LocalID and SyncStatus from data for MariaDB
        unset($data['LocalID']);
        unset($data['SyncStatus']);
        unset($data['LastSync']);
        unset($data[$id_field]); // Don't update the ID field
        
        $set_clause = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = "UPDATE $table SET $set_clause WHERE $id_field = ?";
        
        $stmt = $this->mariadb_conn->prepare($sql);
        $values = array_values($data);
        $values[] = $record_id; // Add ID for WHERE clause
        $types = str_repeat('s', count($values));
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        // Update local sync status
        $update_local = "UPDATE $table SET SyncStatus = 'synced', LastSync = datetime('now') WHERE $id_field = ?";
        $stmt = $this->sqlite_conn->prepare($update_local);
        $stmt->bindValue(1, $record_id);
        $stmt->execute();
    }
    
    private function deleteFromMariaDB($table, $record_id) {
        $id_field = $this->getIdField($table);
        
        $sql = "DELETE FROM $table WHERE $id_field = ?";
        $stmt = $this->mariadb_conn->prepare($sql);
        $stmt->bind_param('i', $record_id);
        $stmt->execute();
        
        // Remove from local database
        $delete_local = "DELETE FROM $table WHERE $id_field = ?";
        $stmt = $this->sqlite_conn->prepare($delete_local);
        $stmt->bindValue(1, $record_id);
        $stmt->execute();
    }
    
    /**
     * Pull server changes to local SQLite
     */
    private function pullServerChanges() {
        $this->log("Pulling server changes to local database");
        
        $pulled_count = 0;
        $tables = ['vessels', 'users', 'mainengines', 'generators', 'gears'];
        
        foreach ($tables as $table) {
            $count = $this->pullTableData($table);
            $pulled_count += $count;
            $this->log("Pulled $count records from $table");
        }
        
        return $pulled_count;
    }
    
    private function pullTableData($table) {
        $id_field = $this->getIdField($table);
        $count = 0;
        
        // Get last sync time for this table
        $last_sync = $this->getLastSyncTime($table);
        
        // Query for changes since last sync
        $where_clause = $table === 'vessels' || $table === 'users' ? 
            "WHERE VesselID = ? OR 1=1" : "WHERE VesselID = ?";
        
        if ($last_sync) {
            $where_clause .= " AND (CreatedDate > ? OR UpdatedDate > ?)";
        }
        
        $sql = "SELECT * FROM $table $where_clause ORDER BY $id_field";
        $stmt = $this->mariadb_conn->prepare($sql);
        
        if ($last_sync) {
            $stmt->bind_param('iss', $this->vessel_id, $last_sync, $last_sync);
        } else {
            $stmt->bind_param('i', $this->vessel_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $this->upsertLocalRecord($table, $row);
            $count++;
        }
        
        return $count;
    }
    
    private function upsertLocalRecord($table, $data) {
        $id_field = $this->getIdField($table);
        $id_value = $data[$id_field];
        
        // Add sync metadata
        $data['LastSync'] = date('Y-m-d H:i:s');
        $data['SyncStatus'] = 'synced';
        
        // Generate LocalID if not exists
        if (!isset($data['LocalID'])) {
            $data['LocalID'] = $this->generateUUID();
        }
        
        // Check if record exists
        $check_sql = "SELECT COUNT(*) FROM $table WHERE $id_field = ?";
        $stmt = $this->sqlite_conn->prepare($check_sql);
        $stmt->bindValue(1, $id_value);
        $result = $stmt->execute();
        $exists = $result->fetchArray()[0] > 0;
        
        if ($exists) {
            // Update existing record
            $fields = array_keys($data);
            $set_clause = implode(' = ?, ', $fields) . ' = ?';
            $sql = "UPDATE $table SET $set_clause WHERE $id_field = ?";
            $stmt = $this->sqlite_conn->prepare($sql);
            
            $values = array_values($data);
            $values[] = $id_value;
            
            foreach ($values as $i => $value) {
                $stmt->bindValue($i + 1, $value);
            }
            $stmt->execute();
        } else {
            // Insert new record
            $fields = array_keys($data);
            $placeholders = str_repeat('?,', count($fields) - 1) . '?';
            $sql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES ($placeholders)";
            $stmt = $this->sqlite_conn->prepare($sql);
            
            $values = array_values($data);
            foreach ($values as $i => $value) {
                $stmt->bindValue($i + 1, $value);
            }
            $stmt->execute();
        }
    }
    
    /**
     * Add operation to sync queue
     */
    public function queueOperation($table, $record_id, $operation, $data = null, $priority = 5) {
        $insert_queue = "INSERT INTO sync_queue (TableName, RecordID, Operation, Data, Priority) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->sqlite_conn->prepare($insert_queue);
        $stmt->bindValue(1, $table);
        $stmt->bindValue(2, $record_id);
        $stmt->bindValue(3, $operation);
        $stmt->bindValue(4, $data ? json_encode($data) : null);
        $stmt->bindValue(5, $priority);
        $stmt->execute();
        
        // Update pending changes count
        $this->updatePendingChangesCount();
    }
    
    private function updatePendingChangesCount() {
        $count_query = "SELECT COUNT(*) FROM sync_queue WHERE Status = 'pending'";
        $result = $this->sqlite_conn->query($count_query);
        $count = $result->fetchArray()[0];
        
        $update_meta = "UPDATE offline_metadata SET PendingChanges = ?, UpdatedDate = datetime('now')";
        $stmt = $this->sqlite_conn->prepare($update_meta);
        $stmt->bindValue(1, $count);
        $stmt->execute();
    }
    
    private function updateSyncStatus($status) {
        $update_meta = "UPDATE offline_metadata SET Status = ?, UpdatedDate = datetime('now')";
        $stmt = $this->sqlite_conn->prepare($update_meta);
        $stmt->bindValue(1, $status);
        $stmt->execute();
    }
    
    private function updateSyncMetadata() {
        $update_meta = "UPDATE offline_metadata SET 
                       LastFullSync = datetime('now'), 
                       LastPartialSync = datetime('now'),
                       UpdatedDate = datetime('now')";
        $this->sqlite_conn->exec($update_meta);
    }
    
    private function getLastSyncTime($table) {
        $query = "SELECT LastPartialSync FROM offline_metadata LIMIT 1";
        $result = $this->sqlite_conn->query($query);
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['LastPartialSync'] : null;
    }
    
    private function getIdField($table) {
        $id_fields = [
            'vessels' => 'VesselID',
            'users' => 'UserID',
            'mainengines' => 'EntryID',
            'generators' => 'GenID',
            'gears' => 'GearID'
        ];
        return $id_fields[$table] ?? 'ID';
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
    
    private function log($message) {
        $this->sync_log[] = date('Y-m-d H:i:s') . ': ' . $message;
    }
    
    public function getOfflineStatus() {
        $query = "SELECT * FROM offline_metadata LIMIT 1";
        $result = $this->sqlite_conn->query($query);
        $metadata = $result->fetchArray(SQLITE3_ASSOC);
        
        $pending_query = "SELECT COUNT(*) as pending FROM sync_queue WHERE Status = 'pending'";
        $result = $this->sqlite_conn->query($pending_query);
        $pending = $result->fetchArray(SQLITE3_ASSOC)['pending'];
        
        return [
            'status' => $metadata['Status'] ?? 'unknown',
            'last_sync' => $metadata['LastFullSync'] ?? null,
            'pending_changes' => $pending,
            'vessel_id' => $metadata['VesselID'] ?? null,
            'user_id' => $metadata['UserID'] ?? null
        ];
    }
}
?>
