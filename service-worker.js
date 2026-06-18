const CACHE_NAME  = 'latinshop-v1.1';
const OFFLINE_URL = '/offline';

const PRECACHE = [
    '/',
    '/frontend/assets/css/main.css',
    '/frontend/assets/css/header.css',
    '/assets/pwa/icon-192.png',
    '/assets/pwa/icon-512.png',
];

/* ───────────────── INSTALL ───────────────── */
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return Promise.allSettled(
                PRECACHE.map(url =>
                    cache.add(url).catch(e =>
                        console.warn('[SW] No se pudo cachear:', url, e)
                    )
                )
            );
        })
    );

    self.skipWaiting();
});

/* ───────────────── ACTIVATE ───────────────── */
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys
                    .filter(k => k !== CACHE_NAME)
                    .map(k => caches.delete(k))
            )
        )
    );

    self.clients.claim();
});

/* ───────────────── FETCH ───────────────── */
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // ❌ ignorar externos
    if (url.origin !== location.origin) return;

    // ❌ bloquear métodos no GET (AQUÍ ESTÁ TU FIX)
    if (request.method !== 'GET') return;

    // 📦 assets
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // ❌ AJAX/API fuera de cache
    if (url.pathname.includes('ajax') || url.searchParams.has('action')) {
        return;
    }

    // 🧭 navegación
    if (request.mode === 'navigate') {
        event.respondWith(networkFirstWithOffline(request));
        return;
    }

    // 🌐 fallback general
    event.respondWith(networkFirst(request));
});

/* ───────────────── HELPERS ───────────────── */
function isStaticAsset(path) {
    return /\.(css|js|woff2?|ttf|png|jpg|jpeg|webp|gif|svg|ico)$/.test(path);
}

/* ───────────────── CACHE FIRST ───────────────── */
async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);

        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }

        return response;
    } catch {
        return new Response('', { status: 408 });
    }
}

/* ───────────────── NETWORK FIRST ───────────────── */
async function networkFirst(request) {
    try {
        const response = await fetch(request);

        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }

        return response;
    } catch {
        const cached = await caches.match(request);
        return cached || new Response('', { status: 408 });
    }
}

/* ───────────────── NAVIGATION FALLBACK ───────────────── */
async function networkFirstWithOffline(request) {
    try {
        const response = await fetch(request);

        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }

        return response;
    } catch {
        const cached = await caches.match(request);
        if (cached) return cached;

        const offline = await caches.match(OFFLINE_URL);

        return offline || new Response('<h1>Sin conexión</h1>', {
            headers: { 'Content-Type': 'text/html' }
        });
    }
}

/* ───────────────── PUSH ───────────────── */
self.addEventListener('push', event => {
    if (!event.data) return;

    const data = event.data.json();

    self.registration.showNotification(data.title || 'Latin Shop', {
        body:  data.body  || '',
        icon:  '/assets/pwa/icon-192.png',
        badge: '/assets/pwa/icon-96.png',
        data:  { url: data.url || '/' },
    });
});

/* ───────────────── NOTIFICATION CLICK ───────────────── */
self.addEventListener('notificationclick', event => {
    event.notification.close();

    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});