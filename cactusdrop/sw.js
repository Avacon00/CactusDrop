// Service Worker für CactusDrop
// Version 1.0

const CACHE_NAME = 'cactusdrop-cache-v1';
// Wir cachen nur die Startseite, da alles andere dynamisch ist.
const urlsToCache = [
  '/',
  '/index.html' 
];

// Installation des Service Workers und Caching der App-Shell
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('CactusDrop cache opened');
        return cache.addAll(urlsToCache);
      })
  );
  self.skipWaiting();
});

// Anfragen abfangen und aus dem Cache bedienen (Cache-First-Strategie)
self.addEventListener('fetch', event => {
  // Wir cachen nur GET-Anfragen. Uploads (POST) gehen immer ans Netzwerk.
  if (event.request.method !== 'GET') {
    return;
  }

  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Wenn die angeforderte Ressource im Cache ist, gib sie von dort zurück.
        if (response) {
          return response;
        }
        // Ansonsten, frage sie normal vom Netzwerk an.
        return fetch(event.request);
      }
    )
  );
});

// Alte Caches bei Aktivierung einer neuen Version löschen
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
