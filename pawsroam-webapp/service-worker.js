// service-worker.js
const CACHE_NAME = 'pawsroam-v1.0.1'; // Incremented version
const urlsToCache = [
    '/',
    '/index.php', // Assuming index.php serves the homepage or is the entry point
    '/assets/css/main.css',
    '/assets/css/responsive.css', // If used
    // Add other global CSS files if any, e.g., components.css
    '/assets/js/app.js', // Main frontend JS
    '/assets/js/maps.js', // Google Maps specific JS
    '/assets/js/utils.js', // If you have common utils
    // '/assets/images/logos/logo_main.png', // Replace with actual path to main logo
    // '/assets/images/icons/icon-192x192.png', // From manifest, ensure path is correct
    // '/assets/images/icons/icon-512x512.png', // From manifest, ensure path is correct
    // '/assets/fonts/your-main-font.woff2', // Cache primary fonts
    '/pages/home.php', // Or the specific file that serves the home content if not index.php
    '/pages/search.php',
    // Add other key pages if they are static enough or crucial for offline UX
    '/manifest.json', // Cache the PWA manifest
    // '/offline.html' // A dedicated offline fallback page
];

const API_CACHE_NAME = 'pawsroam-api-cache-v1.0.1'; // Separate cache for API data

self.addEventListener('install', event => {
    console.log('[ServiceWorker] Attempting to install version:', CACHE_NAME);
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[ServiceWorker] Caching application shell:', urlsToCache);
                return cache.addAll(urlsToCache);
            })
            .then(() => {
                console.log('[ServiceWorker] Installation complete, activating immediately.');
                return self.skipWaiting(); // Force activation of new SW
            })
            .catch(error => {
                console.error('[ServiceWorker] Installation failed:', error);
            })
    );
});

self.addEventListener('activate', event => {
    console.log('[ServiceWorker] Activating version:', CACHE_NAME);
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME && cacheName !== API_CACHE_NAME) {
                        console.log('[ServiceWorker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => {
            console.log('[ServiceWorker] Old caches deleted, claiming clients.');
            return self.clients.claim(); // Take control of all open PawsRoam pages
        })
    );
});

self.addEventListener('fetch', event => {
    const requestUrl = new URL(event.request.url);

    // API requests: Network first, then cache for GET requests
    if (requestUrl.pathname.startsWith('/api/')) {
        event.respondWith(
            networkFirstWithApiCache(event.request, API_CACHE_NAME)
        );
        return;
    }

    // Navigation requests (HTML pages): Network first, then cache, then offline fallback
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(async () => { // Changed to async to await caches.match
                    console.warn(`[ServiceWorker] Network fetch failed for navigation: ${event.request.url}. Trying cache.`);
                    const cachedResponse = await caches.match(event.request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Fallback to offline page if it's cached
                    // const offlinePage = await caches.match('/offline.html');
                    // if (offlinePage) return offlinePage;

                    // Generic fallback if no offline.html
                    return new Response("You are currently offline and this page isn't cached. Please check your connection.", {
                        status: 503,
                        statusText: "Service Unavailable",
                        headers: { 'Content-Type': 'text/html' } // Serve as HTML
                    });
                })
        );
        return;
    }

    // Static assets (CSS, JS, images, fonts): Cache first, then network
    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then(networkResponse => {
                    if (networkResponse && networkResponse.status === 200) {
                        // Cache non-opaque responses for GET requests
                        if (networkResponse.type !== 'opaque' && event.request.method === 'GET') {
                            const responseToCache = networkResponse.clone();
                            caches.open(CACHE_NAME).then(cache => {
                                cache.put(event.request, responseToCache);
                            });
                        }
                    }
                    return networkResponse;
                }).catch(error => {
                    console.error('[ServiceWorker] Fetch failed for static asset:', event.request.url, error);
                    // For images, could return a placeholder if one is cached.
                    // if (event.request.destination === 'image') {
                    // return caches.match('/assets/images/placeholders/offline-image.png');
                    // }
                    // For other assets, let the browser handle the error.
                });
            })
    );
});

async function networkFirstWithApiCache(request, cacheName) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok && request.method === 'GET') { // Only cache successful GET requests
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.warn(`[ServiceWorker] API network request for ${request.url} failed, trying cache. Error: ${error}`);
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        console.error(`[ServiceWorker] API request for ${request.url} failed: not in cache and network error.`);
        return new Response(JSON.stringify({
            success: false,
            error: 'offline',
            message: 'The requested data could not be fetched. You appear to be offline and the data is not available in cache.'
        }), {
            status: 503, // Service Unavailable
            statusText: 'Service Unavailable - Offline',
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        console.log('[ServiceWorker] Received SKIP_WAITING message, calling self.skipWaiting().');
        self.skipWaiting();
    }
});
