@include('pwa._sw-common')
@verbatim
/* ---------- manager / POS ----------
 * Only the POS screen is kept for offline use. Every other back-office page and all JSON
 * endpoints always go to the network (no private data is stored by the worker).
 */
async function fallback() {
    return null;
}

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);

    if (req.mode === 'navigate') {
        if (!inScope(url)) return;
        const isPos = url.pathname === CFG.pos || url.pathname === CFG.pos + '/';
        e.respondWith(isPos
            ? page(req, url, true)
            : fetch(req).catch(() => offlinePage(`<br><a class="b" href="${esc(CFG.pos)}">অফলাইন POS খুলুন</a>`)));
        return;
    }
    if (isAsset(req, url)) e.respondWith(asset(e, req));
});
@endverbatim
