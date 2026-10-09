const CACHE_VERSION = '1';
const PRECACHE_NAME = `pwa-precache-v${CACHE_VERSION}`;
const STATIC_NAME = `pwa-static-v${CACHE_VERSION}`;

const PRECACHE_URLS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];

const STATIC_PREFIXES = ['/build/', '/icons/', '/js/', '/css/', '/fonts/'];

function isStaticAssetPath(pathname) {
    return STATIC_PREFIXES.some((prefix) => pathname.startsWith(prefix));
}

function isNavigationRequest(request) {
    return request.mode === 'navigate';
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(PRECACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS)),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter(
                        (key) =>
                            (key.startsWith('pwa-precache-') || key.startsWith('pwa-static-'))
                            && key !== PRECACHE_NAME
                            && key !== STATIC_NAME,
                    )
                    .map((key) => caches.delete(key)),
            ),
        ),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (isNavigationRequest(request)) {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html')),
        );

        return;
    }

    if (isStaticAssetPath(url.pathname)) {
        event.respondWith(cacheFirstThenNetwork(request));

        return;
    }
});

function cacheFirstThenNetwork(request) {
    return caches.open(STATIC_NAME).then(async (cache) => {
        const cached = await cache.match(request);

        const networkPromise = fetch(request)
            .then((response) => {
                if (response.ok) {
                    cache.put(request, response.clone());
                }

                return response;
            })
            .catch(() => null);

        if (cached) {
            networkPromise.catch(() => {});

            return cached;
        }

        const networkResponse = await networkPromise;

        if (networkResponse) {
            return networkResponse;
        }

        return new Response('', { status: 504, statusText: 'Gateway Timeout' });
    });
}
