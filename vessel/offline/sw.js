// Service Worker for Vessel Logger Offline Functionality
const CACHE_NAME = 'vessel-logger-v2.0.0';
const urlsToCache = [
    '/',
    '/vessel/engineroom/add_log_offline.php',
    '/vessel/engineroom/dashboard.php',
    '/vessel/engineroom/view_logs.php',
    '/style.css',
    '/vessel/offline/manifest.json',
    // Add more critical files as needed
];

// Install event - cache resources
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
    );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // Return cached version if available
                if (response) {
                    return response;
                }
                
                // Otherwise, fetch from network
                return fetch(event.request).then(function(response) {
                    // Don't cache if not a valid response
                    if (!response || response.status !== 200 || response.type !== 'basic') {
                        return response;
                    }
                    
                    // Clone the response
                    const responseToCache = response.clone();
                    
                    caches.open(CACHE_NAME)
                        .then(function(cache) {
                            cache.put(event.request, responseToCache);
                        });
                    
                    return response;
                });
            })
            .catch(function() {
                // If both cache and network fail, return offline page
                if (event.request.destination === 'document') {
                    return caches.match('/vessel/offline/offline.html');
                }
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Background sync for offline data
self.addEventListener('sync', function(event) {
    if (event.tag === 'vessel-data-sync') {
        event.waitUntil(syncVesselData());
    }
});

function syncVesselData() {
    // This would be called when connection is restored
    return fetch('/vessel/offline/sync_endpoint.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'sync_pending_data'
        })
    }).then(function(response) {
        if (response.ok) {
            console.log('Data synced successfully');
            // Notify main thread about successful sync
            self.clients.matchAll().then(clients => {
                clients.forEach(client => {
                    client.postMessage({
                        type: 'SYNC_SUCCESS',
                        message: 'Data synchronized successfully'
                    });
                });
            });
        }
        return response;
    }).catch(function(error) {
        console.log('Sync failed:', error);
        throw error;
    });
}

// Listen for messages from main thread
self.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
