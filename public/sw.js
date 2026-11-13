/* StockWatch service worker.
 * Pages are always fetched from the network (they are per-user and change on every
 * write); only when the network fails do we show the offline page. Static assets are
 * served cache-first and refreshed in the background. Bump VERSION to drop old caches. */
const VERSION = 'sw-v5';
const STATIC_CACHE = 'stockwatch-static-' + VERSION;
const PRECACHE = [
  '/offline.html',
  '/assets/css/style.css',
  '/assets/js/app.js',
  '/assets/favicon.svg',
  '/assets/icons/icon.svg',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
  '/manifest.webmanifest'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k !== STATIC_CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);

  if (req.mode === 'navigate') {
    event.respondWith(fetch(req).catch(() => caches.match('/offline.html')));
    return;
  }

  if (url.origin === location.origin && /\.(css|js|svg|png|webmanifest|woff2?)$/.test(url.pathname)) {
    event.respondWith(
      caches.open(STATIC_CACHE).then(async (cache) => {
        const cached = await cache.match(req);
        const network = fetch(req)
          .then((res) => { if (res.ok) cache.put(req, res.clone()); return res; })
          .catch(() => cached);
        return cached || network;
      })
    );
  }
});
