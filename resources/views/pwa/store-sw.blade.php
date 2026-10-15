@include('pwa._sw-common')
@verbatim
/* ---------- storefront ---------- */
function isPrivate(url) {
    // links carry the store slug (/{slug}/cart); on a custom domain /cart works too — check both forms
    const paths = [url.pathname];
    if (CFG.base && CFG.base !== '/' && url.pathname.startsWith(CFG.base)) paths.push(url.pathname.slice(CFG.base.length) || '/');
    return paths.some((p) => (CFG.private || []).some((x) => p.startsWith(x)));
}

async function fallback(c) {
    return CFG.start ? c.match(pageKey(new URL(CFG.start))) : null;
}

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);

    if (req.mode === 'navigate') {
        if (inScope(url)) e.respondWith(page(req, url, !isPrivate(url)));
        return;
    }
    if (isAsset(req, url)) e.respondWith(asset(e, req));
});
@endverbatim
