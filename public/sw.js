// Ultra-Fast Network-Only Service Worker for Navdhya PWA
// Zero caching, instant offline detection
// Version: 4.0.3

const VERSION = '4.0.3';

// Check if we're in development mode
const isDevelopment = location.hostname === 'localhost' || 
                     location.hostname === '127.0.0.1' || 
                     location.hostname === '[::1]' ||
                     location.hostname.includes('.local');

// Critical assets to cache ONLY for offline page
const CRITICAL_ASSETS = [
  '/offline.html',
  '/navdhya-logo.png'
];

// ======================================================================
// SERVICE WORKER LIFECYCLE EVENTS
// ======================================================================

// Installation Event - Minimal caching for offline page only
self.addEventListener('install', event => {
  // Skip installation in development
  if (isDevelopment) {
    return;
  }
  
  event.waitUntil(
    (async () => {
      try {
        const cache = await caches.open(`navdhya-offline-v${VERSION}`);
        
        // ONLY cache offline page and logo
        const cachePromises = CRITICAL_ASSETS.map(async (url) => {
          try {
            const response = await fetch(url);
            if (response.ok) {
              await cache.put(url, response.clone());
              return true;
            }
          } catch (error) {
            console.error(`[SW] ✗ Failed to cache ${url}:`, error);
          }
          return false;
        });
        
        await Promise.all(cachePromises);
        // Skip waiting to activate immediately
        self.skipWaiting();
      } catch (error) {
        console.error('[SW] ✗ Installation failed:', error);
      }
    })()
  );
});

// Activation Event - Clean up old caches
self.addEventListener('activate', event => {
  // Skip activation in development
  if (isDevelopment) {
    return;
  }
  
  event.waitUntil(
    (async () => {
      // Clean up old caches
      const cacheNames = await caches.keys();
      
      const deletePromises = cacheNames
        .filter(cacheName => cacheName.startsWith('navdhya'))
        .filter(cacheName => cacheName !== `navdhya-offline-v${VERSION}`)
        .map(cacheName => {
          return caches.delete(cacheName);
        });
      
      await Promise.all(deletePromises);
      
      // Claim clients
      await self.clients.claim();
    })()
  );
});

// Fetch Event - Network Only with Smart Offline Fallback
self.addEventListener('fetch', event => {
  // Skip fetch handling in development
  if (isDevelopment) {
    return;
  }
  
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-HTTP requests
  if (!request.url.startsWith('http')) {
    return;
  }
  
  // Handle navigation requests - Network Only with reasonable timeout
  if (request.mode === 'navigate') {
    event.respondWith(
      // Attempt network request with reasonable timeout
      Promise.race([
        fetch(request).catch(error => {
          // If fetch fails, throw the error to be caught below
          throw error;
        }),
        new Promise((_, reject) => 
          setTimeout(() => reject(new Error('NETWORK_TIMEOUT')), 5000) // 5 second timeout
        )
      ])
      .then(response => {
        // If successful, return the response
        return response;
      })
      .catch(async (error) => {
        // Only serve offline page if it's a network error
        if (error.message === 'NETWORK_TIMEOUT' || 
            error.message === 'Failed to fetch' || 
            error instanceof TypeError) {
          const cache = await caches.open(`navdhya-offline-v${VERSION}`);
          const offlinePage = await cache.match('/offline.html');
          return offlinePage || new Response(
            '<!DOCTYPE html><html><head><title>Offline</title></head><body><h1>Offline</h1><p>Please check your connection</p></body></html>',
            { headers: { 'Content-Type': 'text/html' }, status: 503 }
          );
        } else {
          // For other errors, re-throw them
          throw error;
        }
      })
    );
    return;
  }
  
  // Handle requests for critical assets (logo, offline page)
  if (CRITICAL_ASSETS.includes(url.pathname)) {
    event.respondWith(
      fetch(request)
        .then(response => {
          // If successful, return the response
          return response;
        })
        .catch(async () => {
          // If network fails, try to serve from cache
          const cache = await caches.open(`navdhya-offline-v${VERSION}`);
          const cachedResponse = await cache.match(request);
          return cachedResponse || new Response('', { status: 404 });
        })
    );
    return;
  }
  
  // Handle all other requests - Network Only
  event.respondWith(
    // Network request with reasonable timeout
    Promise.race([
      fetch(request),
      new Promise((_, reject) => 
        setTimeout(() => reject(new Error('NETWORK_TIMEOUT')), 5000) // 5 second timeout
      )
    ])
    .then(response => {
      // If successful, return the response
      return response;
    })
    .catch((error) => {
      // Only fail silently for network errors
      if (error.message === 'NETWORK_TIMEOUT' || 
          error.message === 'Failed to fetch' || 
          error instanceof TypeError) {
        return new Response('', { status: 503, statusText: 'Network Error' });
      } else {
        // For other errors, re-throw them
        throw error;
      }
    })
  );
});

// Online/Offline Events - Notify clients
self.addEventListener('online', async () => {
  // Skip in development
  if (isDevelopment) {
    return;
  }
  
  // Notify all clients that we're back online
  const clients = await self.clients.matchAll();
  clients.forEach(client => {
    client.postMessage({ type: 'ONLINE' });
  });
});

self.addEventListener('offline', async () => {
  // Skip in development
  if (isDevelopment) {
    return;
  }
  
  // Notify all clients that we're offline
  const clients = await self.clients.matchAll();
  clients.forEach(client => {
    client.postMessage({ type: 'OFFLINE' });
  });
});