/*
 * Phase 10 — offline POS helper (IndexedDB).
 *   • product catalogue copy  → instant search, works without internet
 *   • queue of sales made offline → sent to the server when the connection returns
 * Each sale carries a unique client_ref, so sending it twice never creates two orders.
 */
window.PosOffline = (() => {
    const DB_NAME = 'mb-pos';
    let dbPromise = null;

    const p = (req) => new Promise((resolve, reject) => { req.onsuccess = () => resolve(req.result); req.onerror = () => reject(req.error); });

    function db() {
        if (!('indexedDB' in window)) return Promise.reject(new Error('IndexedDB not supported'));
        dbPromise ||= new Promise((resolve, reject) => {
            const r = indexedDB.open(DB_NAME, 1);
            r.onupgradeneeded = () => {
                const d = r.result;
                if (!d.objectStoreNames.contains('kv')) d.createObjectStore('kv');
                if (!d.objectStoreNames.contains('queue')) d.createObjectStore('queue', { keyPath: 'id' });
            };
            r.onsuccess = () => resolve(r.result);
            r.onerror = () => reject(r.error);
        });
        return dbPromise;
    }
    async function os(name, mode) { return (await db()).transaction(name, mode).objectStore(name); }

    const uuid = () => (window.crypto && crypto.randomUUID)
        ? crypto.randomUUID()
        : 'x' + Date.now().toString(36) + Math.random().toString(36).slice(2, 12);

    /* ---------------- catalogue ---------------- */

    async function getCatalog(branch) {
        try { return (await p((await os('kv', 'readonly')).get('catalog:' + branch))) || null; } catch (e) { return null; }
    }
    async function saveCatalog(branch, data) {
        try { await p((await os('kv', 'readwrite')).put(data, 'catalog:' + branch)); } catch (e) { /* storage full / private mode */ }
    }

    /** same result shape as manager.inventory.lookup */
    function search(catalog, q, limit = 15) {
        const term = String(q || '').trim().toLowerCase();
        if (!term || !catalog || !catalog.items) return [];
        const out = [];
        const seen = new Set();
        const push = (i, exact) => {
            const k = i.p + '-' + i.v;
            if (seen.has(k) || out.length >= limit) return;
            seen.add(k);
            out.push({ product_id: i.p, variant_id: i.v, name: i.n, sku: i.s, barcode: i.b, price: i.pr, tax_rate: i.t, stock: i.st, physical: !!i.ph, exact });
        };
        for (const i of catalog.items) if ([i.b, i.s, i.c].some((x) => x && String(x).toLowerCase() === term)) push(i, true);
        for (const i of catalog.items) if (String(i.n).toLowerCase().includes(term)) push(i, false);
        return out;
    }

    /** lower the local stock after a sale so the next search shows the right number */
    function reduceStock(catalog, lines) {
        if (!catalog || !catalog.items) return;
        for (const l of lines) {
            const it = catalog.items.find((i) => i.p === l.product_id && i.v === (l.variant_id || 0));
            if (it && it.ph) it.st -= l.quantity;
        }
    }

    /* ---------------- offline sales queue ---------------- */

    async function queued(branch) {
        try { return (await p((await os('queue', 'readonly')).getAll())).filter((s) => s.branch === branch).sort((a, b) => a.created_at.localeCompare(b.created_at)); } catch (e) { return []; }
    }
    async function put(sale) { await p((await os('queue', 'readwrite')).put(sale)); }
    async function remove(id) { await p((await os('queue', 'readwrite')).delete(id)); }

    async function enqueue(branch, payload, receipt) {
        const sale = { id: payload.client_ref, branch, created_at: new Date().toISOString(), status: 'pending', error: null, payload, receipt };
        await put(sale);
        return sale;
    }

    async function getOne(id) { try { return await p((await os('queue', 'readonly')).get(id)); } catch (e) { return null; } }

    let syncing = false;
    /**
     * Send queued sales one by one. Stops at the first network / login problem (tried again later).
     * A sale the server refuses (e.g. register closed) is marked "error" and kept for the user to retry or discard.
     * csrf: function returning the current token; refreshToken: async function fetching a new one (after 419).
     * Only one tab / window syncs at a time (Web Locks), so a sale is never sent twice in parallel.
     */
    async function sync(branch, url, csrf, refreshToken) {
        if (syncing || !navigator.onLine) return { sent: 0, stopped: null };
        const run = async () => {
            syncing = true;
            let sent = 0;
            let stopped = null;
            try {
                for (const s of await queued(branch)) {
                    if (s.status === 'error') continue;
                    let res = null;
                    for (let attempt = 0; attempt < 2; attempt++) {
                        try {
                            res = await fetch(url, {
                                method: 'POST', credentials: 'same-origin',
                                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                                body: JSON.stringify(s.payload),
                            });
                        } catch (e) { res = null; break; }
                        if (res.status === 419 && attempt === 0 && refreshToken && await refreshToken()) continue;   // expired token → new one, once
                        break;
                    }
                    if (!res) { stopped = 'network'; break; }
                    const json = (res.headers.get('content-type') || '').includes('json');
                    if (res.status === 419 || res.status === 401 || res.redirected || !json) { stopped = 'login'; break; }
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.order_number) { await remove(s.id); sent++; continue; }
                    if (res.status >= 500 || res.status === 429 || res.ok) { stopped = 'server'; break; }
                    const current = await getOne(s.id);   // may have been sent / discarded meanwhile
                    if (!current) continue;
                    current.status = 'error'; current.error = data.message || ('Error ' + res.status);
                    await put(current);
                }
            } finally { syncing = false; }
            return { sent, stopped };
        };
        if (navigator.locks && navigator.locks.request) {
            return navigator.locks.request('mb-pos-sync-' + branch, { ifAvailable: true }, (lock) => (lock ? run() : { sent: 0, stopped: 'busy' }));
        }
        return run();
    }

    async function retry(id) {
        try {
            const s = await p((await os('queue', 'readonly')).get(id));
            if (s) { s.status = 'pending'; s.error = null; await put(s); }
        } catch (e) { /* ignore */ }
    }

    /** simple 80 mm receipt for a sale saved offline (the real invoice number comes after sync) */
    function printReceipt(sale, shop) {
        const r = sale.receipt || {};
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const m = (n) => (+n || 0).toFixed(2);
        const rows = (r.items || []).map((i) => `<tr><td>${esc(i.name)}<br>${i.qty} × ${m(i.price)}</td><td style="text-align:right">${m(i.qty * i.price)}</td></tr>`).join('');
        const w = window.open('', '_blank', 'width=380,height=600');
        if (!w) return;
        w.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Receipt</title><style>body{font:12px monospace;width:72mm;margin:0 auto;padding:6px}table{width:100%;border-collapse:collapse}td{padding:2px 0;vertical-align:top}.c{text-align:center}.t td{border-top:1px dashed #000;font-weight:bold}</style></head>
<body><div class="c"><strong>${esc(shop)}</strong><br>OFFLINE SALE<br>Ref: ${esc(sale.id.slice(0, 8).toUpperCase())}<br>${esc(new Date(sale.created_at).toLocaleString())}</div><hr>
<table>${rows}<tr class="t"><td>Subtotal</td><td style="text-align:right">${m(r.sub)}</td></tr>
${r.disc ? `<tr><td>Discount</td><td style="text-align:right">-${m(r.disc)}</td></tr>` : ''}${r.tax ? `<tr><td>Tax</td><td style="text-align:right">${m(r.tax)}</td></tr>` : ''}
<tr class="t"><td>TOTAL</td><td style="text-align:right">${m(r.total)}</td></tr><tr><td>${esc(r.method)}</td><td style="text-align:right">${m(r.tendered ?? r.total)}</td></tr>
${r.change > 0 ? `<tr><td>Change</td><td style="text-align:right">${m(r.change)}</td></tr>` : ''}</table>
<hr><div class="c">ধন্যবাদ! ইন্টারনেট ফিরে এলে বিক্রিটি সিস্টেমে যোগ হবে।</div><script>window.print()<\/script></body></html>`);
        w.document.close();
    }

    return { uuid, getOne, getCatalog, saveCatalog, search, reduceStock, queued, enqueue, sync, retry, remove, printReceipt };
})();
