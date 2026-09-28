const CACHE_NAME = 'workshift-v1';
const ASSETS_LOCALES = [
  'vista/img/icono-192.png',
  'vista/img/icono-512.png',
  'vista/css/reporte.css'
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS_LOCALES))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
    )
  );
});

self.addEventListener('fetch', (e) => {
  // Las llamadas a base de datos y scripts PHP se atienden directo de red
  e.respondWith(fetch(e.request).catch(() => caches.match(e.request)));
});