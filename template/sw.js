/**
 * Service Worker for Vessel Logger Offline Access
 * Provides caching and offline functionality for maritime operations
 */

const CACHE_NAME = 'vessel-logger-v1.0.0';
const API_CACHE_NAME = 'vessel-api-v1.0.0';

// Static files to cache for offline access
const STATIC_CACHE_URLS = [
    // Core pages
    '/dashboard.php',
    '/vessels.php',
    '/change_password.php',
    '/index.php',
    
    // Assets
    '/assets/css/bootstrap.min.css',
    '/assets/js/app.js',
    '/assets/js/offline.js',
    '/assets/js/sync.js',
    '/assets/js/indexeddb.js',
    
    // External dependencies (CDN fallbacks)
    'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
    
    // Offline fallback page
    '/offline/offline.html'
];

// API endpoints that can be cached
const CACHEABLE_API_PATTERNS = [
    /\/api\/vessels/,
    /\/api\/engines/,
    /\/api\/equipment/,
    /\/api\/user_profile/
];

// Install event - Cache static resources
self.addEventListener('install', event => {
    console.log('[ServiceWorker] Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[ServiceWorker] Caching static files');
                return cache.addAll(STATIC_CACHE_URLS.map(url => {
                    // Handle relative URLs
                    return url.startsWith('/') ? url : new Request(url, { mode: 'cors' });
                }));
            })
            .then(() => {
                console.log('[ServiceWorker] Installation complete');
                // Skip waiting to activate immediately
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('[ServiceWorker] Installation failed:', error);
            })
    );
});

// Activate event - Clean up old caches
self.addEventListener('activate', event => {
    console.log('[ServiceWorker] Activating...');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        // Delete old caches
                        if (cacheName !== CACHE_NAME && cacheName !== API_CACHE_NAME) {
                            console.log('[ServiceWorker] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[ServiceWorker] Activation complete');
                // Take control of all pages immediately
                return self.clients.claim();
            })
    );
});

// Fetch event - Handle network requests with caching strategies
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip non-GET requests and chrome-extension requests
    if (request.method !== 'GET' || url.protocol === 'chrome-extension:') {
        return;
    }
    
    // Handle different types of requests
    if (isAPIRequest(request)) {
        event.respondWith(handleAPIRequest(request));
    } else if (isStaticAsset(request)) {
        event.respondWith(handleStaticAsset(request));
    } else if (isPageRequest(request)) {
        event.respondWith(handlePageRequest(request));
    }
});

// Background Sync - Handle data synchronization when connection returns
self.addEventListener('sync', event => {
    console.log('[ServiceWorker] Background sync triggered:', event.tag);
    
    switch (event.tag) {
        case 'sync-vessel-data':
            event.waitUntil(syncVesselData());
            break;
        case 'sync-log-entries':
            event.waitUntil(syncLogEntries());
            break;
        case 'sync-user-changes':
            event.waitUntil(syncUserChanges());
            break;
        default:
            console.log('[ServiceWorker] Unknown sync tag:', event.tag);
    }
});

// Message handling - Communication with main thread
self.addEventListener('message', event => {
    const { type, data } = event.data;
    
    switch (type) {
        case 'FORCE_SYNC':
            handleForceSync(data);
            break;
        case 'CLEAR_CACHE':
            handleClearCache();
            break;
        case 'GET_CACHE_STATUS':
            handleGetCacheStatus(event);
            break;
        default:
            console.log('[ServiceWorker] Unknown message type:', type);
    }
});

/**
 * Helper Functions
 */

function isAPIRequest(request) {
    const url = new URL(request.url);
    return url.pathname.startsWith('/api/') || 
           CACHEABLE_API_PATTERNS.some(pattern => pattern.test(url.pathname));
}

function isStaticAsset(request) {
    const url = new URL(request.url);
    return url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2)$/) ||
           url.hostname.includes('cdn.jsdelivr.net') ||
           url.hostname.includes('cdnjs.cloudflare.com');
}

function isPageRequest(request) {
    const url = new URL(request.url);
    return url.pathname.endsWith('.php') || url.pathname === '/';
}

async function handleAPIRequest(request) {
    const url = new URL(request.url);
    
    try {
        // Try network first for API requests
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache successful API responses
            const cache = await caches.open(API_CACHE_NAME);
            cache.put(request, networkResponse.clone());
            
            // Notify main thread that we're online
            notifyClients('ONLINE_STATUS', { online: true });
        }
        
        return networkResponse;
        
    } catch (error) {
        console.log('[ServiceWorker] Network failed for API request, trying cache:', url.pathname);
        
        // Fall back to cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            // Notify main thread that we're offline but serving from cache
            notifyClients('ONLINE_STATUS', { online: false, fromCache: true });
            return cachedResponse;
        }
        
        // Return offline response for uncached API requests
        return new Response(
            JSON.stringify({
                error: 'Offline',
                message: 'This data is not available offline. Please try again when connected.',
                offline: true
            }),
            {
                status: 503,
                statusText: 'Service Unavailable',
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

async function handleStaticAsset(request) {
    // Cache-first strategy for static assets
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.log('[ServiceWorker] Failed to fetch static asset:', request.url);
        // Return empty response for failed static assets
        return new Response('', { status: 404 });
    }
}

async function handlePageRequest(request) {
    try {
        // Network-first strategy for pages
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache successful page responses
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
            
            // Notify main thread that we're online
            notifyClients('ONLINE_STATUS', { online: true });
        }
        
        return networkResponse;
        
    } catch (error) {
        console.log('[ServiceWorker] Network failed for page request, trying cache:', request.url);
        
        // Fall back to cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            // Notify main thread that we're offline but serving from cache
            notifyClients('ONLINE_STATUS', { online: false, fromCache: true });
            return cachedResponse;
        }
        
        // Fall back to offline page
        const offlineResponse = await caches.match('/offline/offline.html');
        if (offlineResponse) {
            return offlineResponse;
        }
        
        // Last resort: basic offline message
        return new Response(
            `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Offline - Vessel Logger</title>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
            <body>
                <h1>You're Offline</h1>
                <p>This page isn't available offline. Please check your connection and try again.</p>
                <button onclick="window.location.reload()">Try Again</button>
            </body>
            </html>
            `,
            {
                status: 503,
                statusText: 'Service Unavailable',
                headers: { 'Content-Type': 'text/html' }
            }
        );
    }
}

/**
 * Sync Functions
 */

async function syncVesselData() {
    console.log('[ServiceWorker] Syncing vessel data...');
    
    try {
        // Get pending vessel changes from IndexedDB
        const db = await openOfflineDB();
        const syncQueue = await getSyncQueue(db, 'vessels');
        
        for (const item of syncQueue) {
            await syncSingleItem('vessels', item);
        }
        
        // Clear sync queue after successful sync
        await clearSyncQueue(db, 'vessels');
        
        // Notify main thread of successful sync
        notifyClients('SYNC_COMPLETE', { type: 'vessels', success: true });
        
    } catch (error) {
        console.error('[ServiceWorker] Failed to sync vessel data:', error);
        notifyClients('SYNC_ERROR', { type: 'vessels', error: error.message });
    }
}

async function syncLogEntries() {
    console.log('[ServiceWorker] Syncing log entries...');
    
    try {
        // Get pending log entries from IndexedDB
        const db = await openOfflineDB();
        const syncQueue = await getSyncQueue(db, 'logs');
        
        for (const item of syncQueue) {
            await syncSingleItem('logs', item);
        }
        
        // Clear sync queue after successful sync
        await clearSyncQueue(db, 'logs');
        
        // Notify main thread of successful sync
        notifyClients('SYNC_COMPLETE', { type: 'logs', success: true });
        
    } catch (error) {
        console.error('[ServiceWorker] Failed to sync log entries:', error);
        notifyClients('SYNC_ERROR', { type: 'logs', error: error.message });
    }
}

async function syncUserChanges() {
    console.log('[ServiceWorker] Syncing user changes...');
    
    try {
        // Get pending user changes from IndexedDB
        const db = await openOfflineDB();
        const syncQueue = await getSyncQueue(db, 'user_changes');
        
        for (const item of syncQueue) {
            await syncSingleItem('user_changes', item);
        }
        
        // Clear sync queue after successful sync
        await clearSyncQueue(db, 'user_changes');
        
        // Notify main thread of successful sync
        notifyClients('SYNC_COMPLETE', { type: 'user_changes', success: true });
        
    } catch (error) {
        console.error('[ServiceWorker] Failed to sync user changes:', error);
        notifyClients('SYNC_ERROR', { type: 'user_changes', error: error.message });
    }
}

async function syncSingleItem(type, item) {
    const endpoint = `/api/sync.php`;
    
    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            type: type,
            action: item.action,
            data: item.data,
            timestamp: item.timestamp
        })
    });
    
    if (!response.ok) {
        throw new Error(`Failed to sync ${type}: ${response.statusText}`);
    }
    
    return response.json();
}

/**
 * Utility Functions
 */

function notifyClients(type, data) {
    self.clients.matchAll().then(clients => {
        clients.forEach(client => {
            client.postMessage({ type, data });
        });
    });
}

async function handleForceSync(data) {
    console.log('[ServiceWorker] Force sync requested:', data);
    
    try {
        switch (data.type) {
            case 'vessels':
                await syncVesselData();
                break;
            case 'logs':
                await syncLogEntries();
                break;
            case 'all':
                await Promise.all([
                    syncVesselData(),
                    syncLogEntries(),
                    syncUserChanges()
                ]);
                break;
        }
    } catch (error) {
        console.error('[ServiceWorker] Force sync failed:', error);
    }
}

async function handleClearCache() {
    console.log('[ServiceWorker] Clearing caches...');
    
    const cacheNames = await caches.keys();
    await Promise.all(
        cacheNames.map(cacheName => caches.delete(cacheName))
    );
    
    notifyClients('CACHE_CLEARED', { success: true });
}

function handleGetCacheStatus(event) {
    caches.keys().then(cacheNames => {
        const status = {
            caches: cacheNames,
            version: CACHE_NAME,
            timestamp: new Date().toISOString()
        };
        
        event.ports[0].postMessage(status);
    });
}

/**
 * IndexedDB Helper Functions
 */

function openOfflineDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('VesselLoggerOfflineDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = event => {
            const db = event.target.result;
            
            // Create object stores
            if (!db.objectStoreNames.contains('sync_queue')) {
                const store = db.createObjectStore('sync_queue', { keyPath: 'id', autoIncrement: true });
                store.createIndex('type', 'type', { unique: false });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
        };
    });
}

async function getSyncQueue(db, type) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['sync_queue'], 'readonly');
        const store = transaction.objectStore('sync_queue');
        const index = store.index('type');
        const request = index.getAll(type);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
    });
}

async function clearSyncQueue(db, type) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['sync_queue'], 'readwrite');
        const store = transaction.objectStore('sync_queue');
        const index = store.index('type');
        const request = index.getAllKeys(type);
        
        request.onsuccess = () => {
            const keys = request.result;
            const deletePromises = keys.map(key => {
                return new Promise((deleteResolve, deleteReject) => {
                    const deleteRequest = store.delete(key);
                    deleteRequest.onsuccess = () => deleteResolve();
                    deleteRequest.onerror = () => deleteReject(deleteRequest.error);
                });
            });
            
            Promise.all(deletePromises)
                .then(() => resolve())
                .catch(reject);
        };
        
        request.onerror = () => reject(request.error);
    });
}

console.log('[ServiceWorker] Service Worker script loaded');
