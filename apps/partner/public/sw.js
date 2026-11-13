/* Navdhya Partner Courtyard Service Worker (T-AP05-05)
 * Security invariant:
 * - Caches ONLY non-sensitive public app shell assets & read-only schedule shell.
 * - NEVER caches /api/* responses, session cookies, tokens, PII, or earnings ledger payloads.
 * - All non-GET mutations are network-only (no background replay queue for state changes).
 */
const SHELL_CACHE = 'navdhya-partner-shell-v1';
const STATIC_ASSETS = ['/manifest.webmanifest'];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(SHELL_CACHE).then((cache) => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting()),
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== SHELL_CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) {
    return;
  }

  // Strictly bypass API routes, auth callbacks, and dynamic financial/PII data.
  if (
    url.pathname.startsWith('/api/') ||
    url.pathname.startsWith('/earnings') ||
    url.pathname.startsWith('/review') ||
    url.pathname.startsWith('/onboarding')
  ) {
    return;
  }

  // Cache-first only for explicit immutable static shell assets.
  if (STATIC_ASSETS.includes(url.pathname) || url.pathname.startsWith('/_next/static/')) {
    event.respondWith(
      caches.match(req).then((cached) => {
        if (cached) return cached;
        return fetch(req).then((res) => {
          if (res.ok && res.type === 'basic') {
            const copy = res.clone();
            void caches.open(SHELL_CACHE).then((c) => c.put(req, copy));
          }
          return res;
        });
      }),
    );
  }
});

