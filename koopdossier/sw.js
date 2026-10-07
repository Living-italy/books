/* Koopdossier Italië: offline gebruik.
 * App en inhoud: eerst het netwerk (zodat updates direct binnenkomen), bij geen verbinding de cache.
 * Lettertypen en iconen: eerst de cache.
 * De app meldt bij elke start de versie uit content.nl.json ({ type: 'version' }). Wijkt die af van de
 * versie die de service worker heeft bewaard, dan haalt hij alle bestanden opnieuw op.
 * Verhoog CACHE alleen als de lijst met bestanden verandert.
 */
const CACHE = 'koopdossier-v1';
const SHELL = [
  './',
  'index.html',
  'content.nl.json',
  'manifest.webmanifest',
  'fonts/dmsans-latin.woff2',
  'fonts/dmsans-italic-latin.woff2',
  'fonts/playfair-latin.woff2',
  'fonts/playfair-italic-latin.woff2',
  'icons/icon-192.png',
  'icons/icon-512.png',
  'icons/apple-touch-icon.png'
];
const NETWORK_FIRST = ['index.html', 'content.nl.json', 'manifest.webmanifest'];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((c) => c.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((k) => k.startsWith('koopdossier-') && k !== CACHE).map((k) => caches.delete(k))))
      .then(() => self.clients.claim())
  );
});

function scoped(path) { return new URL(path, self.registration.scope).href; }

// Netwerk eerst, na 4 seconden of bij geen verbinding de cache.
function networkFirst(request, cacheKey) {
  const net = fetch(request).then((res) => {
    if (res && res.ok) { const copy = res.clone(); caches.open(CACHE).then((c) => c.put(cacheKey, copy)); }
    return res;
  });
  const timeout = new Promise((resolve) => setTimeout(resolve, 4000));
  return Promise.race([net.catch(() => null), timeout])
    .then((res) => res || caches.match(cacheKey).then((hit) => hit || net));
}

function cacheFirst(request) {
  return caches.match(request, { ignoreSearch: true }).then((hit) => hit || fetch(request).then((res) => {
    if (res && res.ok) { const copy = res.clone(); caches.open(CACHE).then((c) => c.put(request, copy)); }
    return res;
  }));
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);
  if (req.method !== 'GET' || url.origin !== self.location.origin) return;   // geen analytics, geen api.php-posts
  if (url.pathname.endsWith('/api.php') || url.pathname.endsWith('/sw.js')) return;
  if (req.mode === 'navigate') { event.respondWith(networkFirst(req, scoped('index.html'))); return; }
  const file = NETWORK_FIRST.find((f) => url.pathname.endsWith('/' + f));
  if (file) { event.respondWith(networkFirst(req, scoped(file))); return; }
  event.respondWith(cacheFirst(req));
});

function refreshAll(c) {
  return Promise.all(SHELL.map((path) =>
    fetch(scoped(path), { cache: 'reload' }).then((res) => (res.ok ? c.put(scoped(path), res) : null)).catch(() => null)
  ));
}

self.addEventListener('message', (event) => {
  const data = event.data || {};
  if (data.type !== 'version' || !data.version) return;
  const key = scoped('__inhoudsversie');
  event.waitUntil(caches.open(CACHE).then((c) => c.match(key)
    .then((res) => (res ? res.text() : ''))
    .then((known) => (known === data.version ? null
      : refreshAll(c).then(() => c.put(key, new Response(data.version)))))));
});
