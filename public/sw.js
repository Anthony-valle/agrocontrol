const CACHE_NAME = 'agrocontrol-shell-v4';
const STATIC_ASSETS = [
  '/manifest.webmanifest?v=20260602-1',
  '/NiceAdmin/assets/js/main.js',
  '/NiceAdmin/assets/js/offline-sync.js?v=20260602-1',
  '/NiceAdmin/assets/css/style.css',
  '/NiceAdmin/assets/css/agro-theme.css',
  '/NiceAdmin/assets/img/agrocontrol.png',
  '/login'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)).catch(() => Promise.resolve())
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  if (request.method !== 'GET' || url.origin !== self.location.origin) {
    return;
  }

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response && response.ok) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, clone)).catch(() => Promise.resolve());
          }
          return response;
        })
        .catch(() => caches.match(request).then((cached) => cached || caches.match('/login')))
    );
    return;
  }

  if (['style', 'script', 'image', 'font', 'manifest'].includes(request.destination)) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone)).catch(() => Promise.resolve());
          return response;
        })
        .catch(() => caches.match(request))
    );
  }
});