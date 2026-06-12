/**
 * service-worker.js — Latin Shop PWA
 * Cache-first para assets, network-first para páginas PHP
 */

const CACHE_NAME  = 'latinshop-v4';
const OFFLINE_URL = '/latinshop/offline';

const PRECACHE = [
    '/latinshop/',
    '/latinshop/frontend/assets/css/main.css',
    '/latinshop/frontend/assets/css/header.css',
    '/latinshop/assets/pwa/icon-192.png',
    '/latinshop/assets/pwa/icon-512.png',
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return Promise.allSettled(
                PRECACHE.map(url => cache.add(url).catch(e => console.warn('[SW] No se pudo cachear:', url, e)))
            );
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    if (url.origin !== location.origin) return;

    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (url.pathname.includes('ajax') || url.searchParams.has('action')) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(networkFirstWithOffline(request));
        return;
    }

    event.respondWith(networkFirst(request));
});

function isStaticAsset(path) {
    return /\.(css|js|woff2?|ttf|png|jpg|jpeg|webp|gif|svg|ico)$/.test(path);
}

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
        return caches.match(OFFLINE_URL) || new Response('<h1>Sin conexión</h1>', {
            headers: { 'Content-Type': 'text/html' }
        });
    }
}

self.addEventListener('push', event => {
    if (!event.data) return;
    const data = event.data.json();
    self.registration.showNotification(data.title || 'Latin Shop', {
        body:  data.body  || '',
        icon:  '/latinshop/assets/images/pwa/icon-192.png',
        badge: '/latinshop/assets/images/pwa/icon-96.png',
        data:  { url: data.url || '/latinshop/' },
    });
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url));
});