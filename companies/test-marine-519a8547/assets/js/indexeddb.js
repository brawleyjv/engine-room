/**
 * IndexedDB Manager for Vessel Logger Offline Access
 * Handles local data storage and synchronization queue
 */

class OfflineDB {
    constructor() {
        this.dbName = 'VesselLoggerOfflineDB';
        this.version = 1;
        this.db = null;
    }

    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => {
                console.error('Failed to open IndexedDB:', request.error);
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                console.log('IndexedDB initialized successfully');
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                this.createStores(db);
            };
        });
    }

    createStores(db) {
        console.log('Creating IndexedDB object stores...');

        // Vessels store
        if (!db.objectStoreNames.contains('vessels')) {
            const vesselStore = db.createObjectStore('vessels', { keyPath: 'id' });
            vesselStore.createIndex('name', 'name', { unique: false });
            vesselStore.createIndex('type', 'type', { unique: false });
            vesselStore.createIndex('last_sync', 'last_sync', { unique: false });
            vesselStore.createIndex('dirty', 'dirty', { unique: false });
        }

        // Engines store
        if (!db.objectStoreNames.contains('engines')) {
            const engineStore = db.createObjectStore('engines', { keyPath: 'id' });
            engineStore.createIndex('vessel_id', 'vessel_id', { unique: false });
            engineStore.createIndex('name', 'name', { unique: false });
            engineStore.createIndex('type', 'type', { unique: false });
        }

        // Logs store
        if (!db.objectStoreNames.contains('logs')) {
            const logStore = db.createObjectStore('logs', { keyPath: 'id' });
            logStore.createIndex('vessel_id', 'vessel_id', { unique: false });
            logStore.createIndex('engine_id', 'engine_id', { unique: false });
            logStore.createIndex('timestamp', 'timestamp', { unique: false });
            logStore.createIndex('synced', 'synced', { unique: false });
            logStore.createIndex('created_offline', 'created_offline', { unique: false });
        }

        // Sync queue store
        if (!db.objectStoreNames.contains('sync_queue')) {
            const syncStore = db.createObjectStore('sync_queue', { keyPath: 'id', autoIncrement: true });
            syncStore.createIndex('type', 'type', { unique: false });
            syncStore.createIndex('action', 'action', { unique: false });
            syncStore.createIndex('timestamp', 'timestamp', { unique: false });
            syncStore.createIndex('priority', 'priority', { unique: false });
        }

        // User settings store
        if (!db.objectStoreNames.contains('user_settings')) {
            const settingsStore = db.createObjectStore('user_settings', { keyPath: 'key' });
        }

        // Offline status store
        if (!db.objectStoreNames.contains('offline_status')) {
            const statusStore = db.createObjectStore('offline_status', { keyPath: 'id' });
        }
    }

    // Vessel Operations
    async addVessel(vessel) {
        vessel.last_sync = new Date().toISOString();
        vessel.dirty = false;
        vessel.created_offline = !navigator.onLine;

        return this.add('vessels', vessel);
    }

    async updateVessel(vessel) {
        vessel.last_sync = new Date().toISOString();
        vessel.dirty = true;

        const result = await this.update('vessels', vessel);
        
        // Add to sync queue if online or queue for later
        await this.addToSyncQueue('vessels', 'UPDATE', vessel);
        
        return result;
    }

    async deleteVessel(vesselId) {
        const result = await this.delete('vessels', vesselId);
        
        // Add to sync queue
        await this.addToSyncQueue('vessels', 'DELETE', { id: vesselId });
        
        return result;
    }

    async getVessels() {
        return this.getAll('vessels');
    }

    async getVessel(id) {
        return this.get('vessels', id);
    }

    // Engine Operations
    async addEngine(engine) {
        engine.last_sync = new Date().toISOString();
        engine.created_offline = !navigator.onLine;

        const result = await this.add('engines', engine);
        await this.addToSyncQueue('engines', 'CREATE', engine);
        
        return result;
    }

    async updateEngine(engine) {
        engine.last_sync = new Date().toISOString();

        const result = await this.update('engines', engine);
        await this.addToSyncQueue('engines', 'UPDATE', engine);
        
        return result;
    }

    async getEnginesByVessel(vesselId) {
        return this.getByIndex('engines', 'vessel_id', vesselId);
    }

    // Log Operations
    async addLog(log) {
        // Generate temporary ID for offline logs
        if (!log.id) {
            log.id = 'temp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        log.timestamp = log.timestamp || new Date().toISOString();
        log.synced = false;
        log.created_offline = !navigator.onLine;

        const result = await this.add('logs', log);
        
        // Add to sync queue
        await this.addToSyncQueue('logs', 'CREATE', log, 'high');
        
        return result;
    }

    async updateLog(log) {
        log.synced = false;
        
        const result = await this.update('logs', log);
        await this.addToSyncQueue('logs', 'UPDATE', log, 'high');
        
        return result;
    }

    async deleteLog(logId) {
        const result = await this.delete('logs', logId);
        await this.addToSyncQueue('logs', 'DELETE', { id: logId }, 'high');
        
        return result;
    }

    async getLogsByVessel(vesselId, limit = 50) {
        const logs = await this.getByIndex('logs', 'vessel_id', vesselId);
        
        // Sort by timestamp descending and limit results
        return logs
            .sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp))
            .slice(0, limit);
    }

    async getUnsyncedLogs() {
        return this.getByIndex('logs', 'synced', false);
    }

    // Sync Queue Operations
    async addToSyncQueue(type, action, data, priority = 'normal') {
        const queueItem = {
            type: type,
            action: action,
            data: data,
            timestamp: new Date().toISOString(),
            priority: priority,
            retry_count: 0,
            status: 'pending'
        };

        return this.add('sync_queue', queueItem);
    }

    async getSyncQueue(type = null) {
        if (type) {
            return this.getByIndex('sync_queue', 'type', type);
        }
        return this.getAll('sync_queue');
    }

    async markSyncItemComplete(id) {
        const item = await this.get('sync_queue', id);
        if (item) {
            item.status = 'completed';
            item.completed_at = new Date().toISOString();
            await this.update('sync_queue', item);
        }
    }

    async markSyncItemFailed(id, error) {
        const item = await this.get('sync_queue', id);
        if (item) {
            item.status = 'failed';
            item.retry_count = (item.retry_count || 0) + 1;
            item.last_error = error;
            item.last_retry = new Date().toISOString();
            await this.update('sync_queue', item);
        }
    }

    async clearCompletedSyncItems() {
        const completed = await this.getByIndex('sync_queue', 'status', 'completed');
        for (const item of completed) {
            await this.delete('sync_queue', item.id);
        }
    }

    async getPendingSyncCount() {
        const pending = await this.getByIndex('sync_queue', 'status', 'pending');
        return pending.length;
    }

    // User Settings Operations
    async setSetting(key, value) {
        return this.add('user_settings', { key, value, updated_at: new Date().toISOString() });
    }

    async getSetting(key) {
        const setting = await this.get('user_settings', key);
        return setting ? setting.value : null;
    }

    // Offline Status Operations
    async setOfflineStatus(status) {
        return this.add('offline_status', {
            id: 'current',
            online: status.online,
            last_sync: status.last_sync,
            pending_changes: status.pending_changes,
            updated_at: new Date().toISOString()
        });
    }

    async getOfflineStatus() {
        return this.get('offline_status', 'current');
    }

    // Generic Database Operations
    async add(storeName, data) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.add(data);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async update(storeName, data) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.put(data);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async get(storeName, key) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.get(key);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getAll(storeName) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const request = store.getAll();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getByIndex(storeName, indexName, value) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readonly');
            const store = transaction.objectStore(storeName);
            const index = store.index(indexName);
            const request = index.getAll(value);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async delete(storeName, key) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.delete(key);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async clear(storeName) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.clear();

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Utility Methods
    async getStorageUsage() {
        if ('storage' in navigator && 'estimate' in navigator.storage) {
            return navigator.storage.estimate();
        }
        return null;
    }

    async exportData() {
        const data = {};
        const storeNames = ['vessels', 'engines', 'logs', 'user_settings'];
        
        for (const storeName of storeNames) {
            data[storeName] = await this.getAll(storeName);
        }
        
        return {
            version: this.version,
            exported_at: new Date().toISOString(),
            data: data
        };
    }

    async importData(exportedData) {
        if (exportedData.version !== this.version) {
            throw new Error('Version mismatch: cannot import data from different version');
        }
        
        for (const [storeName, records] of Object.entries(exportedData.data)) {
            await this.clear(storeName);
            for (const record of records) {
                await this.add(storeName, record);
            }
        }
    }
}

// Global instance
let offlineDB = null;

// Initialize offline database
async function initOfflineDB() {
    if (!offlineDB) {
        offlineDB = new OfflineDB();
        await offlineDB.init();
    }
    return offlineDB;
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { OfflineDB, initOfflineDB };
} else {
    window.OfflineDB = OfflineDB;
    window.initOfflineDB = initOfflineDB;
}

console.log('IndexedDB module loaded');
