/* FreshRoute service worker.
 * - Shell: the offline page and built assets are cached; hashed assets are cache-first at runtime.
 * - Pages: network-first for navigations; on failure serve the last cached copy, else /offline.
 * - Never cached: API, photos, account data export, MFA, admin, login/register (private or sensitive).
 * - Writes made offline are NOT handled here: they are queued in IndexedDB by the page (see resources/js).
 * - Cached pages are cleared on sign-out (message 'clear').
 */
const VERSION = 'fr-v1';
const PAGES = 'fr-pages-' + VERSION;
const ASSETS = 'fr-assets-' + VERSION;
const SKIP = [/^\/api\//, /^\/photos\//, /^\/my-data/, /^\/mfa/, /^\/admin/, /^\/login/, /^\/register/, /^\/forgot-password/, /^\/reset-password/, /^\/logout/, /^\/health/, /^\/up/];

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const pages = await caches.open(PAGES);
        const assets = await caches.open(ASSETS);
        const res = await fetch('/offline', { credentials: 'same-origin', headers: { 'X-FR-Warm': '1' } });
        const html = await res.clone().text();
        await pages.put('/offline', res);
        const urls = [...html.matchAll(/(?:href|src)="([^"]*\/build\/assets\/[^"]+)"/g)].map((m) => new URL(m[1], self.location.origin).pathname);
        await assets.addAll([...new Set(urls)].concat(['/icons/logo.svg', '/theme-init.js']));
        self.skipWaiting();
    })());
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        for (const k of await caches.keys()) if (![PAGES, ASSETS].includes(k)) await caches.delete(k);
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    const d = event.data || {};
    if (d.type === 'clear') {
        event.waitUntil((async () => { await caches.delete(PAGES); const c = await caches.open(PAGES); await fetch('/offline', { headers: { 'X-FR-Warm': '1' } }).then((r) => c.put('/offline', r)).catch(() => {}); })());
    }
    if (d.type === 'warm') {
        event.waitUntil((async () => {
            const c = await caches.open(PAGES);
            for (const u of d.urls || []) {
                try { const r = await fetch(u, { credentials: 'same-origin', redirect: 'manual', headers: { 'X-FR-Warm': '1' } }); if (r.status === 200) await c.put(u, r); } catch (e) { /* offline */ }
            }
        })());
    }
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    const url = new URL(req.url);
    if (req.method !== 'GET' || url.origin !== self.location.origin) return;

    if (url.pathname.startsWith('/build/assets/') || url.pathname.startsWith('/icons/') || url.pathname === '/theme-init.js' || url.pathname === '/manifest.webmanifest') {
        event.respondWith(caches.open(ASSETS).then(async (c) => {
            const hit = await c.match(req);
            if (hit) return hit;
            const res = await fetch(req);
            if (res.ok) c.put(req, res.clone());
            return res;
        }));
        return;
    }

    if (SKIP.some((re) => re.test(url.pathname))) return;

    if (req.mode === 'navigate') {
        event.respondWith((async () => {
            const c = await caches.open(PAGES);
            try {
                const res = await fetch(req);
                if (res.ok && res.status === 200 && !res.redirected) c.put(url.pathname + url.search, res.clone());
                return res;
            } catch (e) {
                return (await c.match(url.pathname + url.search, { ignoreVary: true })) || (await c.match(url.pathname, { ignoreVary: true })) || (await c.match('/offline', { ignoreVary: true }));
            }
        })());
    }
});
