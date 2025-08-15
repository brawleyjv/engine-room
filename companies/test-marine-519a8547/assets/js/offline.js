/**
 * Offline Manager for Vessel Logger
 * Handles offline detection, data caching, and user notifications
 */

class OfflineManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.db = null;
        this.syncInProgress = false;
        this.pendingChanges = 0;
        this.lastSyncTime = null;
        
        // UI elements
        this.statusIndicator = null;
        this.syncButton = null;
        this.pendingBadge = null;
        
        // Event listeners
        this.onlineHandlers = [];
        this.offlineHandlers = [];
        this.syncHandlers = [];
        
        this.init();
    }

    async init() {
        console.log('Initializing Offline Manager...');
        
        try {
            // Initialize IndexedDB
            this.db = await initOfflineDB();
            
            // Set up event listeners
            this.setupEventListeners();
            
            // Initialize UI
            this.initializeUI();
            
            // Register service worker
            await this.registerServiceWorker();
            
            // Check initial sync status
            await this.updateSyncStatus();
            
            console.log('Offline Manager initialized successfully');
            
        } catch (error) {
            console.error('Failed to initialize Offline Manager:', error);
            this.showNotification('Offline features unavailable', 'error');
        }
    }

    setupEventListeners() {
        // Network status events
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Service worker messages
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                this.handleServiceWorkerMessage(event);
            });
        }
        
        // Page visibility changes (for sync when page becomes visible)
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && this.isOnline) {
                this.scheduleSync();
            }
        });
        
        // Beforeunload (save any pending data)
        window.addEventListener('beforeunload', () => {
            this.handlePageUnload();
        });
    }

    initializeUI() {
        // Create status indicator if it doesn't exist
        if (!document.getElementById('connection-status')) {
            this.createStatusIndicator();
        }
        
        // Get references to UI elements
        this.statusIndicator = document.getElementById('connection-status');
        this.syncButton = document.getElementById('sync-button');
        this.pendingBadge = document.getElementById('pending-badge');
        
        // Add sync button if it doesn't exist
        if (!this.syncButton) {
            this.createSyncButton();
        }
        
        // Update initial status
        this.updateConnectionStatus();
    }

    createStatusIndicator() {
        const statusBar = document.createElement('div');
        statusBar.id = 'connection-status';
        statusBar.className = 'connection-status';
        statusBar.innerHTML = `
            <i class="fas fa-wifi" id="status-icon"></i>
            <span id="status-text">Checking connection...</span>
        `;
        
        // Add to top of page
        document.body.insertBefore(statusBar, document.body.firstChild);
        
        // Add CSS
        this.addStatusBarCSS();
    }

    createSyncButton() {
        // Look for navigation area to add sync button
        const navbar = document.querySelector('.navbar-nav');
        if (navbar) {
            const syncItem = document.createElement('li');
            syncItem.className = 'nav-item';
            syncItem.innerHTML = `
                <button class="btn btn-outline-light btn-sm ms-2" id="sync-button" title="Sync offline changes">
                    <i class="fas fa-sync-alt"></i>
                    <span id="pending-badge" class="badge bg-warning ms-1" style="display: none;">0</span>
                </button>
            `;
            
            navbar.appendChild(syncItem);
            
            // Add event listener
            const syncButton = document.getElementById('sync-button');
            syncButton.addEventListener('click', () => this.forcSync());
        }
    }

    addStatusBarCSS() {
        const style = document.createElement('style');
        style.textContent = `
            .connection-status {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #28a745;
                color: white;
                text-align: center;
                padding: 5px 10px;
                font-size: 0.9rem;
                z-index: 9999;
                transition: all 0.3s ease;
            }
            
            .connection-status.offline {
                background: #dc3545;
            }
            
            .connection-status.syncing {
                background: #ffc107;
                color: #000;
            }
            
            .connection-status.hidden {
                transform: translateY(-100%);
            }
            
            body {
                padding-top: 35px;
            }
            
            @media (max-width: 768px) {
                .connection-status {
                    font-size: 0.8rem;
                    padding: 3px 8px;
                }
                
                body {
                    padding-top: 30px;
                }
            }
        `;
        
        document.head.appendChild(style);
    }

    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('Service Worker registered:', registration);
                
                // Handle service worker updates
                registration.addEventListener('updatefound', () => {
                    console.log('Service Worker update found');
                    this.showNotification('App update available. Refresh to update.', 'info');
                });
                
                return registration;
                
            } catch (error) {
                console.error('Service Worker registration failed:', error);
                throw error;
            }
        } else {
            throw new Error('Service Worker not supported');
        }
    }

    handleOnline() {
        console.log('Connection restored');
        this.isOnline = true;
        this.updateConnectionStatus();
        this.showNotification('Connection restored', 'success');
        
        // Trigger sync after a short delay
        setTimeout(() => this.scheduleSync(), 1000);
        
        // Notify handlers
        this.onlineHandlers.forEach(handler => handler());
    }

    handleOffline() {
        console.log('Connection lost');
        this.isOnline = false;
        this.updateConnectionStatus();
        this.showNotification('Working offline - changes saved locally', 'warning');
        
        // Notify handlers
        this.offlineHandlers.forEach(handler => handler());
    }

    handleServiceWorkerMessage(event) {
        const { type, data } = event.data;
        
        switch (type) {
            case 'ONLINE_STATUS':
                this.handleOnlineStatusUpdate(data);
                break;
            case 'SYNC_COMPLETE':
                this.handleSyncComplete(data);
                break;
            case 'SYNC_ERROR':
                this.handleSyncError(data);
                break;
            case 'CACHE_CLEARED':
                this.showNotification('Cache cleared successfully', 'success');
                break;
        }
    }

    handleOnlineStatusUpdate(data) {
        if (data.fromCache && !this.isOnline) {
            this.showNotification('Loading from cache', 'info');
        }
    }

    handleSyncComplete(data) {
        console.log('Sync completed:', data);
        this.syncInProgress = false;
        this.updateSyncStatus();
        this.showNotification(`${data.type} synced successfully`, 'success');
        
        // Notify handlers
        this.syncHandlers.forEach(handler => handler({ type: 'complete', data }));
    }

    handleSyncError(data) {
        console.error('Sync error:', data);
        this.syncInProgress = false;
        this.showNotification(`Sync failed: ${data.error}`, 'error');
        
        // Notify handlers
        this.syncHandlers.forEach(handler => handler({ type: 'error', data }));
    }

    updateConnectionStatus() {
        if (!this.statusIndicator) return;
        
        const icon = this.statusIndicator.querySelector('#status-icon');
        const text = this.statusIndicator.querySelector('#status-text');
        
        if (this.syncInProgress) {
            this.statusIndicator.className = 'connection-status syncing';
            icon.className = 'fas fa-sync-alt fa-spin';
            text.textContent = 'Syncing...';
        } else if (this.isOnline) {
            this.statusIndicator.className = 'connection-status';
            icon.className = 'fas fa-wifi';
            text.textContent = 'Online';
            
            // Hide status bar after 3 seconds if online
            setTimeout(() => {
                if (this.isOnline && !this.syncInProgress) {
                    this.statusIndicator.classList.add('hidden');
                }
            }, 3000);
        } else {
            this.statusIndicator.className = 'connection-status offline';
            this.statusIndicator.classList.remove('hidden');
            icon.className = 'fas fa-wifi-slash';
            text.textContent = 'Offline - changes saved locally';
        }
    }

    async updateSyncStatus() {
        try {
            this.pendingChanges = await this.db.getPendingSyncCount();
            
            if (this.pendingBadge) {
                if (this.pendingChanges > 0) {
                    this.pendingBadge.textContent = this.pendingChanges;
                    this.pendingBadge.style.display = 'inline';
                } else {
                    this.pendingBadge.style.display = 'none';
                }
            }
            
            // Update last sync time
            const status = await this.db.getOfflineStatus();
            if (status) {
                this.lastSyncTime = status.last_sync;
            }
            
        } catch (error) {
            console.error('Failed to update sync status:', error);
        }
    }

    async scheduleSync() {
        if (this.syncInProgress || !this.isOnline) {
            return;
        }
        
        try {
            await this.performSync();
        } catch (error) {
            console.error('Scheduled sync failed:', error);
        }
    }

    async forcSync() {
        if (this.syncInProgress) {
            this.showNotification('Sync already in progress', 'info');
            return;
        }
        
        if (!this.isOnline) {
            this.showNotification('Cannot sync while offline', 'warning');
            return;
        }
        
        try {
            await this.performSync(true);
        } catch (error) {
            console.error('Force sync failed:', error);
        }
    }

    async performSync(isManual = false) {
        this.syncInProgress = true;
        this.updateConnectionStatus();
        
        if (isManual) {
            this.showNotification('Starting sync...', 'info');
        }
        
        try {
            // Register background sync with service worker
            if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('sync-vessel-data');
                await registration.sync.register('sync-log-entries');
                await registration.sync.register('sync-user-changes');
            } else {
                // Fallback: direct sync
                await this.directSync();
            }
            
            // Update sync status
            await this.db.setOfflineStatus({
                online: this.isOnline,
                last_sync: new Date().toISOString(),
                pending_changes: 0
            });
            
        } catch (error) {
            console.error('Sync failed:', error);
            this.showNotification('Sync failed', 'error');
        } finally {
            this.syncInProgress = false;
            this.updateConnectionStatus();
            await this.updateSyncStatus();
        }
    }

    async directSync() {
        // Get pending sync items
        const syncQueue = await this.db.getSyncQueue();
        
        for (const item of syncQueue) {
            try {
                await this.syncItem(item);
                await this.db.markSyncItemComplete(item.id);
            } catch (error) {
                await this.db.markSyncItemFailed(item.id, error.message);
                throw error;
            }
        }
        
        // Clear completed items
        await this.db.clearCompletedSyncItems();
    }

    async syncItem(item) {
        const response = await fetch('/api/sync.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                type: item.type,
                action: item.action,
                data: item.data,
                timestamp: item.timestamp
            })
        });
        
        if (!response.ok) {
            throw new Error(`Sync failed: ${response.statusText}`);
        }
        
        return response.json();
    }

    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existing = document.querySelectorAll('.offline-notification');
        existing.forEach(el => el.remove());
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `offline-notification alert alert-${this.getBootstrapClass(type)} alert-dismissible fade show`;
        notification.style.cssText = `
            position: fixed;
            top: 60px;
            right: 20px;
            z-index: 10000;
            min-width: 300px;
            max-width: 400px;
        `;
        
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    getBootstrapClass(type) {
        const typeMap = {
            'success': 'success',
            'error': 'danger',
            'warning': 'warning',
            'info': 'info'
        };
        
        return typeMap[type] || 'info';
    }

    handlePageUnload() {
        // Save any pending form data before page unload
        this.saveCurrentFormData();
    }

    saveCurrentFormData() {
        // Find any forms with unsaved data
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            if (Object.keys(data).length > 0) {
                // Save to localStorage as backup
                localStorage.setItem(`form_backup_${form.id || 'unnamed'}`, JSON.stringify({
                    data: data,
                    timestamp: new Date().toISOString()
                }));
            }
        });
    }

    restoreFormData(formId) {
        const backup = localStorage.getItem(`form_backup_${formId}`);
        if (backup) {
            try {
                const { data, timestamp } = JSON.parse(backup);
                const backupAge = Date.now() - new Date(timestamp).getTime();
                
                // Only restore if backup is less than 1 hour old
                if (backupAge < 3600000) {
                    return data;
                }
            } catch (error) {
                console.error('Failed to restore form data:', error);
            }
            
            // Clean up old backup
            localStorage.removeItem(`form_backup_${formId}`);
        }
        
        return null;
    }

    // Event subscription methods
    onOnline(handler) {
        this.onlineHandlers.push(handler);
    }

    onOffline(handler) {
        this.offlineHandlers.push(handler);
    }

    onSync(handler) {
        this.syncHandlers.push(handler);
    }

    // Public API methods
    async addVessel(vesselData) {
        return this.db.addVessel(vesselData);
    }

    async updateVessel(vesselData) {
        return this.db.updateVessel(vesselData);
    }

    async deleteVessel(vesselId) {
        return this.db.deleteVessel(vesselId);
    }

    async getVessels() {
        return this.db.getVessels();
    }

    async addLog(logData) {
        return this.db.addLog(logData);
    }

    async getLogsByVessel(vesselId, limit) {
        return this.db.getLogsByVessel(vesselId, limit);
    }

    getConnectionStatus() {
        return {
            online: this.isOnline,
            syncInProgress: this.syncInProgress,
            pendingChanges: this.pendingChanges,
            lastSyncTime: this.lastSyncTime
        };
    }
}

// Global instance
let offlineManager = null;

// Initialize offline manager when DOM is ready
function initOfflineManager() {
    if (!offlineManager) {
        offlineManager = new OfflineManager();
    }
    return offlineManager;
}

// Auto-initialize when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOfflineManager);
} else {
    initOfflineManager();
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { OfflineManager, initOfflineManager };
} else {
    window.OfflineManager = OfflineManager;
    window.initOfflineManager = initOfflineManager;
}

console.log('Offline module loaded');
