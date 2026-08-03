const CACHE_VERSION = 'aves-jakarta-pwa-v6';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const APP_BASE = new URL('./', self.registration.scope).pathname;

const PRECACHE_ASSETS = [
    `${APP_BASE}index.html`,
    `${APP_BASE}offline.html`,
    `${APP_BASE}manifest.json`,
    `${APP_BASE}assets/pwa-register.js`,
    `${APP_BASE}assets/user-account.css`,
    `${APP_BASE}assets/disease-education.css`,
    `${APP_BASE}assets/education/bird-disease-atlas.png`,
    `${APP_BASE}assets/education/veterinary-treatment-atlas.png`,
    `${APP_BASE}admin/assets/admin-theme.css`,
    `${APP_BASE}assets/branding/aves-blue.png`,
    `${APP_BASE}assets/icons/aves-blue-192.png`,
    `${APP_BASE}assets/icons/aves-blue-512.png`,
    `${APP_BASE}assets/icons/aves-blue-maskable-512.png`
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE_ASSETS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => Promise.all(
                cacheNames
                    .filter((cacheName) => cacheName.startsWith('aves-jakarta-pwa-') && cacheName !== STATIC_CACHE)
                    .map((cacheName) => caches.delete(cacheName))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    const isPhpPage = url.pathname.endsWith('.php');
    const isAdminNavigation = url.pathname.includes('/admin/') && request.mode === 'navigate';

    // Data akun, diagnosis, dan admin harus selalu berasal dari server.
    if (isPhpPage || isAdminNavigation) {
        event.respondWith(
            fetch(request).catch(() => caches.match(`${APP_BASE}offline.html`))
        );
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(`${APP_BASE}offline.html`))
        );
        return;
    }

    const isStaticAsset = /\.(?:css|js|png|jpg|jpeg|webp|gif|ico|svg|woff2?)$/i.test(url.pathname);
    if (!isStaticAsset) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(request).then((networkResponse) => {
                if (networkResponse && networkResponse.ok) {
                    const responseCopy = networkResponse.clone();
                    caches.open(STATIC_CACHE).then((cache) => cache.put(request, responseCopy));
                }
                return networkResponse;
            });
        })
    );
});
