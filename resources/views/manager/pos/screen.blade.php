@extends('layouts.manager')
@section('title', 'POS')

@section('content')
@php $canApprove = \App\Support\Tenancy::can('pos.approve'); @endphp
<div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
    @include('manager.pos._nav')
    <div class="d-flex gap-2 small">
        <span id="netState" class="badge bg-secondary align-self-center" title="ইন্টারনেট অবস্থা"><i class="fa-solid fa-wifi"></i> <span></span></span>
        <button type="button" id="offlineBadge" class="btn btn-sm btn-warning d-none" title="নেট এলে নিজে থেকেই পাঠানো হবে — এখনই পাঠাতে ক্লিক করুন"><i class="fa-solid fa-cloud-arrow-up"></i> অফলাইন বিক্রি: <strong>0</strong></button>
        <span class="badge bg-success align-self-center">Session #{{ $session->id }} · {{ $session->warehouse->name }} · {{ $session->opened_at->format('h:i A') }}</span>
        <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#cashModal"><i class="fa-solid fa-money-bill-transfer"></i> Cash In/Out</button>
        <a href="{{ route('manager.pos.closing') }}" class="btn btn-sm btn-danger"><i class="fa-solid fa-lock"></i> Daily Closing</a>
    </div>
</div>

<div id="offlinePanel" class="alert alert-warning small py-2 d-none"></div>

<div class="row g-3" id="pos">
    {{-- left: search + cart --}}
    <div class="col-lg-8">
        <div class="panel mb-3"><div class="panel-body p-2 position-relative">
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-barcode"></i></span>
                <input id="posSearch" class="form-control form-control-lg" placeholder="বারকোড স্ক্যান করুন বা নাম/SKU লিখুন… (F2)" autocomplete="off" autofocus>
            </div>
            <div id="posResults" class="list-group position-absolute w-100 shadow" style="z-index:30;max-height:360px;overflow:auto;left:0;top:100%"></div>
        </div></div>
        <div class="panel">
            <div class="table-responsive"><table class="table mb-0 small align-middle">
                <thead class="table-light"><tr><th>Item</th><th style="width:130px">Price</th><th style="width:120px">Qty</th><th class="text-end" style="width:100px">Total</th><th style="width:30px"></th></tr></thead>
                <tbody id="cartRows"><tr class="empty"><td colspan="5" class="text-center text-muted py-5">কার্ট খালি — পণ্য স্ক্যান করুন</td></tr></tbody>
            </table></div>
        </div>
        <div class="panel mt-3"><div class="panel-head">Held carts <button class="btn btn-sm btn-outline-secondary" id="holdBtn"><i class="fa-solid fa-pause"></i> Hold current</button></div>
            <div class="panel-body p-2 d-flex flex-wrap gap-2" id="holdList"></div></div>
    </div>

    {{-- right: customer + totals + pay --}}
    <div class="col-lg-4">
        <div class="panel mb-3"><div class="panel-body p-2 small position-relative">
            <div id="custSel" class="d-flex justify-content-between align-items-center mb-1"><span><i class="fa-solid fa-user"></i> <strong id="custName">Walk-in Customer</strong></span><button class="btn btn-sm btn-link p-0 d-none" id="custClear">remove</button></div>
            <input id="custSearch" class="form-control form-control-sm" placeholder="Customer phone / name" autocomplete="off">
            <div id="custResults" class="list-group position-absolute shadow" style="z-index:30;left:8px;right:8px"></div>
            <details class="mt-1"><summary class="text-muted">+ New customer</summary>
                <div class="d-flex gap-1 mt-1"><input id="ncName" class="form-control form-control-sm" placeholder="Name"><input id="ncPhone" class="form-control form-control-sm" placeholder="01XXXXXXXXX"><button class="btn btn-sm btn-dark" id="ncSave">Add</button></div>
            </details>
        </div></div>

        <div class="panel"><div class="panel-body small">
            <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="tSub">0.00</strong></div>
            <div class="d-flex gap-1 align-items-center my-2"><span class="me-auto">Discount</span>
                <select id="discType" class="form-select form-select-sm" style="width:70px"><option value="fixed">৳</option><option value="percent">%</option></select>
                <input id="discVal" type="number" min="0" step="0.01" value="0" class="form-control form-control-sm" style="width:90px"></div>
            @unless ($canApprove)<div class="text-muted" style="font-size:11px">১০%-এর বেশি ডিসকাউন্ট ও দাম পরিবর্তনে ম্যানেজার অনুমতি লাগবে।</div>@endunless
            <div class="d-flex justify-content-between text-danger"><span>− Discount</span><span id="tDisc">0.00</span></div>
            <div class="d-flex justify-content-between text-muted"><span>+ Tax / VAT</span><span id="tTax">0.00</span></div>
            <div class="d-flex justify-content-between fs-4 fw-bold border-top mt-2 pt-2"><span>Payable</span><span id="tTotal">0.00</span></div>

            <div class="btn-group w-100 my-2 flex-wrap" id="methods">
                @foreach ($methods as $k => $v)<input type="radio" class="btn-check" name="pm" id="pm{{ $k }}" value="{{ $k }}" @checked($loop->first)><label class="btn btn-outline-dark btn-sm" for="pm{{ $k }}">{{ $v }}</label>@endforeach
            </div>
            <div id="cashBox"><label class="form-label mb-0">Cash received ৳</label><input id="tendered" type="number" min="0" step="0.01" class="form-control form-control-lg">
                <div class="d-flex justify-content-between mt-1"><span>Change</span><strong id="tChange">0.00</strong></div>
                <div class="d-flex flex-wrap gap-1 mt-1" id="quickCash"></div></div>
            <div id="refBox" class="d-none"><input id="payRef" class="form-control form-control-sm" placeholder="TrxID / Card last 4"></div>
            <input id="saleNote" class="form-control form-control-sm mt-2" placeholder="Note">
            <button class="btn btn-brand btn-lg w-100 mt-3" id="payBtn" disabled><i class="fa-solid fa-check"></i> Complete Sale (F9)</button>
            <button class="btn btn-sm btn-outline-danger w-100 mt-2" id="clearBtn">Clear cart</button>
        </div></div>
    </div>
</div>

{{-- cash in/out --}}
<div class="modal fade" id="cashModal" tabindex="-1"><div class="modal-dialog modal-sm"><form method="POST" action="{{ route('manager.pos.cash') }}" class="modal-content">@csrf
    <div class="modal-header py-2"><h6 class="modal-title">Cash In / Out</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body small">
        <select name="type" class="form-select form-select-sm mb-2"><option value="in">Cash In (ড্রয়ারে যোগ)</option><option value="out">Cash Out (খরচ/তোলা)</option></select>
        <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm mb-2" placeholder="Amount" required>
        <input name="note" class="form-control form-control-sm" placeholder="কারণ *" required>
    </div>
    <div class="modal-footer py-2"><button class="btn btn-sm btn-brand">Save</button></div>
</form></div></div>

{{-- sale done --}}
<div class="modal fade" id="doneModal" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content text-center">
    <div class="modal-body py-4">
        <i class="fa-solid fa-circle-check text-success" style="font-size:48px"></i>
        <h5 class="mt-2 mb-0" id="doneNo"></h5>
        <div class="fs-4 fw-bold" id="doneTotal"></div>
        <div class="text-muted" id="doneChange"></div>
        <div class="alert alert-warning small py-1 mt-2 mb-0 d-none" id="doneOffline"><i class="fa-solid fa-cloud-arrow-up"></i> ইন্টারনেট নেই — বিক্রিটি এই ডিভাইসে সেভ হয়েছে, নেট এলে নিজে থেকেই সিস্টেমে যোগ হবে।</div>
        <div class="d-flex gap-2 justify-content-center mt-3"><a href="#" target="_blank" id="doneReceipt" class="btn btn-dark btn-sm"><i class="fa-solid fa-print"></i> Receipt</a><button class="btn btn-brand btn-sm" data-bs-dismiss="modal" id="doneNext">New Sale (Enter)</button></div>
    </div>
</div></div></div>
@endsection

@push('scripts')
<script src="{{ asset('js/pos-offline.js') }}?v={{ \App\Http\Controllers\PwaController::VERSION }}"></script>
<script>
(() => {
    const R = {
        lookup: @json(route('manager.inventory.lookup')),
        customers: @json(route('manager.pos.customers')),
        customerStore: @json(route('manager.pos.customers.store')),
        checkout: @json(route('manager.pos.checkout')),
        holds: @json(route('manager.pos.holds')),
        holdStore: @json(route('manager.pos.holds.store')),
        holdDel: @json(route('manager.pos.holds.destroy', ['hold' => '__ID__'])),
        catalog: @json(route('manager.pos.catalog')),
    };
    const BRANCH = {{ (int) $session->branch_id }};
    const SHOP = @json(\App\Support\Tenancy::branch()->display_name);
    const PO = window.PosOffline;
    let catalog = null;     // local copy of all products (instant + offline search)
    const WAREHOUSE = {{ $session->warehouse_id }};
    const CAN_APPROVE = @json($canApprove);
    let CSRF = document.querySelector('meta[name=csrf-token]')?.content || @json(csrf_token());
    // after a 419 (token changed / page came from the offline copy) → read a fresh token from the POS page
    const refreshToken = async () => {
        try {
            const res = await fetch(location.pathname, {credentials: 'same-origin', cache: 'no-store'});
            if (!res.ok || res.redirected) return false;
            const m = (await res.text()).match(/<meta name="csrf-token" content="([^"]+)"/);
            if (m) { CSRF = m[1]; return true; }
        } catch (e) {}
        return false;
    };
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const money = n => (+n || 0).toFixed(2);
    const api = async (url, opts = {}) => {
        const res = await fetch(url, {headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest'}, ...opts});
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || ('Error ' + res.status));
        return data;
    };

    let cart = [];          // {key, product_id, variant_id, name, price, base, qty, stock, physical}
    let customer = null;
    let holdId = null;

    /* ---------- product search ---------- */
    const search = $('posSearch'), results = $('posResults');
    let timer, lastList = [];
    const addItem = it => {
        const key = it.product_id + '-' + it.variant_id;
        const row = cart.find(c => c.key === key);
        if (row) row.qty++;
        else cart.push({key, product_id: it.product_id, variant_id: it.variant_id, name: it.name, price: +it.price, base: +it.price, qty: 1, stock: it.stock, physical: it.physical, tax_rate: +it.tax_rate || 0});
        results.innerHTML = ''; search.value = ''; search.focus(); render();
    };
    const doSearch = async (enter) => {
        const q = search.value.trim();
        if (!q) { results.innerHTML = ''; return; }
        lastList = catalog && catalog.items && catalog.items.length ? PO.search(catalog, q) : [];   // instant, works offline
        if (!lastList.length && navigator.onLine) {   // not in the saved copy (e.g. product added a moment ago) → ask the server
            try { lastList = await api(R.lookup + '?q=' + encodeURIComponent(q) + '&warehouse=' + WAREHOUSE); } catch (e) { lastList = []; }
        }
        const exact = lastList.filter(i => i.exact);
        if (enter && (exact.length === 1 || lastList.length === 1)) return addItem(exact[0] || lastList[0]);
        results.innerHTML = lastList.length ? lastList.map((i, n) => `<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between small" data-n="${n}">
            <span>${esc(i.name)} <span class="text-muted">${esc(i.sku || '')}</span></span><span>৳${money(i.price)} · <span class="${i.physical && i.stock <= 0 ? 'text-danger' : 'text-success'}">${i.physical ? i.stock : '∞'}</span></span></button>`).join('')
            : '<div class="list-group-item small text-muted">কিছু পাওয়া যায়নি</div>';
    };
    search.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => doSearch(false), 250); });
    search.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); doSearch(true); } if (e.key === 'Escape') results.innerHTML = ''; });
    results.addEventListener('click', e => { const b = e.target.closest('[data-n]'); if (b) addItem(lastList[b.dataset.n]); });

    /* ---------- cart ---------- */
    const rows = $('cartRows');
    const totals = () => {
        const sub = cart.reduce((s, c) => s + c.price * c.qty, 0);
        const dv = +$('discVal').value || 0;
        const r2 = n => Math.round(n * 100) / 100;
        const disc = r2(Math.min(sub, $('discType').value === 'percent' ? sub * Math.min(100, dv) / 100 : dv));
        // mirror PosService::checkout — tax on the discounted line value
        const ratio = sub > 0 ? (sub - disc) / sub : 0;
        const tax = r2(cart.reduce((s, c) => s + c.price * c.qty * ratio * (c.tax_rate || 0) / 100, 0));
        return {sub, disc, tax, total: Math.max(0, r2(sub - disc + tax))};
    };
    const render = () => {
        rows.innerHTML = cart.length ? cart.map((c, i) => `<tr data-i="${i}">
            <td>${esc(c.name)}${c.physical && c.qty > c.stock ? '<br><span class="text-danger" style="font-size:11px">stock: ' + c.stock + '</span>' : ''}</td>
            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm pr" value="${c.price}" ${CAN_APPROVE ? '' : 'readonly'}></td>
            <td><div class="input-group input-group-sm"><button class="btn btn-outline-secondary dec" type="button">−</button><input type="number" min="1" class="form-control text-center qt" value="${c.qty}"><button class="btn btn-outline-secondary inc" type="button">+</button></div></td>
            <td class="text-end">${money(c.price * c.qty)}</td>
            <td><button class="btn btn-sm text-danger rm" type="button"><i class="fa-solid fa-xmark"></i></button></td></tr>`).join('')
            : '<tr><td colspan="5" class="text-center text-muted py-5">কার্ট খালি — পণ্য স্ক্যান করুন</td></tr>';
        const t = totals();
        $('tSub').textContent = money(t.sub); $('tDisc').textContent = money(t.disc); $('tTax').textContent = money(t.tax); $('tTotal').textContent = money(t.total);
        $('payBtn').disabled = !cart.length;
        quick(t.total); change();
    };
    rows.addEventListener('click', e => {
        const tr = e.target.closest('tr[data-i]'); if (!tr) return;
        const c = cart[tr.dataset.i];
        if (e.target.closest('.inc')) c.qty++;
        else if (e.target.closest('.dec')) c.qty = Math.max(1, c.qty - 1);
        else if (e.target.closest('.rm')) cart.splice(tr.dataset.i, 1);
        else return;
        render();
    });
    rows.addEventListener('change', e => {
        const tr = e.target.closest('tr[data-i]'); if (!tr) return;
        const c = cart[tr.dataset.i];
        if (e.target.classList.contains('qt')) c.qty = Math.max(1, parseInt(e.target.value) || 1);
        if (e.target.classList.contains('pr')) c.price = Math.max(0, +e.target.value || 0);
        render();
    });
    ['discVal', 'discType'].forEach(id => $(id).addEventListener('input', render));
    $('clearBtn').addEventListener('click', () => { if (cart.length && confirm('কার্ট খালি করবেন?')) reset(); });

    /* ---------- payment ---------- */
    const method = () => document.querySelector('input[name=pm]:checked').value;
    const change = () => { const t = totals().total, g = +$('tendered').value || 0; $('tChange').textContent = money(Math.max(0, g - t)); };
    const quick = total => {
        const opts = [...new Set([Math.ceil(total), Math.ceil(total / 100) * 100, Math.ceil(total / 500) * 500, Math.ceil(total / 1000) * 1000])].filter(v => v > 0).slice(0, 4);
        $('quickCash').innerHTML = opts.map(v => `<button type="button" class="btn btn-sm btn-light border" data-v="${v}">${v}</button>`).join('');
    };
    $('quickCash').addEventListener('click', e => { const b = e.target.closest('[data-v]'); if (b) { $('tendered').value = b.dataset.v; change(); } });
    $('tendered').addEventListener('input', change);
    $('methods').addEventListener('change', () => { const cash = method() === 'cash'; $('cashBox').classList.toggle('d-none', !cash); $('refBox').classList.toggle('d-none', cash); });

    const done = new bootstrap.Modal($('doneModal'));
    const pay = async () => {
        if (!cart.length || $('payBtn').disabled) return;
        const t = totals();
        if (method() === 'cash' && $('tendered').value !== '' && +$('tendered').value < t.total - 0.001) { alert('প্রাপ্ত টাকা কম।'); return; }
        $('payBtn').disabled = true;
        const tendered = method() === 'cash' && $('tendered').value !== '' ? +$('tendered').value : null;
        const payload = {
            seller: @json(\App\Support\Tenancy::actorName()),
            client_total: t.total,
            items: cart.map(c => ({product_id: c.product_id, variant_id: c.variant_id || null, quantity: c.qty, ...(c.price !== c.base ? {price: c.price} : {})})),
            customer_id: customer?.id || null,
            discount_type: $('discType').value, discount_value: +$('discVal').value || 0,
            method: method(), tendered,
            reference: $('payRef').value || null, note: $('saleNote').value || null, hold_id: holdId,
            client_ref: PO.uuid(),          // same id on every retry → never a double sale
        };
        const receipt = {items: cart.map(c => ({name: c.name, qty: c.qty, price: c.price})), sub: t.sub, disc: t.disc, tax: t.tax, total: t.total,
            method: method(), tendered, change: tendered !== null ? Math.max(0, tendered - t.total) : 0};

        let r = null, offline = !navigator.onLine;
        const fail = msg => { alert(msg); $('payBtn').disabled = false; };
        if (!offline) {
            const ctrl = new AbortController();
            const timer = setTimeout(() => ctrl.abort(), 12000);   // weak network: stop waiting, keep the sale offline
            try {
                const send = () => fetch(R.checkout, {method: 'POST', credentials: 'same-origin', body: JSON.stringify(payload), signal: ctrl.signal,
                    headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest'}});
                let res = await send();
                if (res.status === 419 && await refreshToken()) res = await send();
                clearTimeout(timer);
                const isJson = (res.headers.get('content-type') || '').includes('json');
                const data = isJson ? await res.json().catch(() => ({})) : {};
                if ([502, 503, 504].includes(res.status)) offline = true;               // server unreachable → offline queue
                else if (res.status === 419 || res.redirected || !isJson) return fail('সেশন শেষ বা অ্যাক্সেস নেই — পাতাটি রিফ্রেশ করে আবার লগইন করুন। বিক্রিটি হয়নি।');
                else if (!res.ok) return fail(data.message || ('Error ' + res.status));
                else if (!data.order_number) return fail('সার্ভার থেকে সঠিক উত্তর আসেনি — Orders পাতায় বিক্রিটি হয়েছে কিনা দেখে নিন।');
                else r = data;
            } catch (e) { clearTimeout(timer); offline = true; }      // no connection / timeout → keep the sale on this device (client_ref prevents doubles)
        }

        let sale = null;
        if (offline) {
            try {
                sale = await PO.enqueue(BRANCH, {...payload, offline: true, offline_at: new Date().toISOString()}, receipt);
            } catch (e) { alert('অফলাইনে সেভ করা যায়নি: ' + e.message); $('payBtn').disabled = false; return; }
        }
        PO.reduceStock(catalog, payload.items);
        if (catalog) PO.saveCatalog(BRANCH, catalog);

        $('doneOffline').classList.toggle('d-none', !offline);
        $('doneNo').textContent = offline ? 'OFFLINE · ' + sale.id.slice(0, 8).toUpperCase() : r.order_number;
        $('doneTotal').textContent = '৳ ' + money(offline ? t.total : r.total);
        const ch = offline ? receipt.change : r.change;
        $('doneChange').textContent = ch > 0 ? 'Change: ৳ ' + money(ch) : '';
        $('doneReceipt').href = offline ? '#' : r.receipt_url;
        $('doneReceipt').onclick = offline ? (ev => { ev.preventDefault(); PO.printReceipt(sale, SHOP); }) : null;
        reset(); loadHolds(); done.show();
        refreshQueue();
    };
    $('payBtn').addEventListener('click', pay);
    $('doneModal').addEventListener('hidden.bs.modal', () => search.focus());
    $('doneModal').addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); done.hide(); } });

    const reset = () => {
        cart = []; holdId = null; setCustomer(null);
        $('discVal').value = 0; $('tendered').value = ''; $('payRef').value = ''; $('saleNote').value = '';
        render();
    };

    /* ---------- customer ---------- */
    const setCustomer = c => {
        customer = c;
        $('custName').textContent = c ? `${c.name} (${c.phone})` + (c.loyalty_points ? ` · ${c.loyalty_points} pts` : '') : 'Walk-in Customer';
        $('custClear').classList.toggle('d-none', !c);
        $('custSearch').value = ''; $('custResults').innerHTML = '';
    };
    let cTimer, cList = [];
    $('custSearch').addEventListener('input', e => {
        clearTimeout(cTimer);
        cTimer = setTimeout(async () => {
            const q = e.target.value.trim();
            if (q.length < 2) { $('custResults').innerHTML = ''; return; }
            cList = await api(R.customers + '?q=' + encodeURIComponent(q)).catch(() => []);
            $('custResults').innerHTML = cList.map((c, n) => `<button type="button" class="list-group-item list-group-item-action small" data-n="${n}">${esc(c.name)} — ${esc(c.phone)}</button>`).join('');
        }, 250);
    });
    $('custResults').addEventListener('click', e => { const b = e.target.closest('[data-n]'); if (b) setCustomer(cList[b.dataset.n]); });
    $('custClear').addEventListener('click', () => setCustomer(null));
    $('ncSave').addEventListener('click', async () => {
        try { setCustomer(await api(R.customerStore, {method: 'POST', body: JSON.stringify({name: $('ncName').value, phone: $('ncPhone').value})})); $('ncName').value = $('ncPhone').value = ''; }
        catch (e) { alert(e.message); }
    });

    /* ---------- hold / resume ---------- */
    const loadHolds = async () => {
        const list = await api(R.holds).catch(() => []);
        $('holdList').innerHTML = list.length ? list.map(h => `<span class="btn-group btn-group-sm"><button type="button" class="btn btn-outline-primary resume" data-id="${h.id}">${esc(h.label)} <span class="text-muted">(${(h.cart.items || []).length})</span></button><button type="button" class="btn btn-outline-danger del" data-id="${h.id}">×</button></span>`).join('')
            : '<span class="small text-muted">No held carts.</span>';
        $('holdList')._data = list;
    };
    $('holdBtn').addEventListener('click', async () => {
        if (!cart.length) return;
        const label = prompt('Hold label (e.g. customer name)', customer?.name || 'Cart ' + new Date().toLocaleTimeString());
        if (!label) return;
        try {
            await api(R.holdStore, {method: 'POST', body: JSON.stringify({label, cart: {items: cart, customer, disc_type: $('discType').value, disc_val: $('discVal').value}})});
            if (holdId) await api(R.holdDel.replace('__ID__', holdId), {method: 'DELETE'}).catch(() => {});
            reset(); loadHolds();
        } catch (e) { alert(e.message); }
    });
    $('holdList').addEventListener('click', async e => {
        const b = e.target.closest('[data-id]'); if (!b) return;
        const h = ($('holdList')._data || []).find(x => x.id == b.dataset.id); if (!h) return;
        if (b.classList.contains('del')) {
            if (!confirm('Delete held cart?')) return;
            await api(R.holdDel.replace('__ID__', h.id), {method: 'DELETE'}).catch(() => {});
            if (holdId === h.id) holdId = null;
            return loadHolds();
        }
        if (cart.length && !confirm('বর্তমান কার্ট বাদ দিয়ে held কার্ট খুলবেন?')) return;
        cart = (h.cart.items || []).map(c => ({...c}));
        setCustomer(h.cart.customer || null);
        $('discType').value = h.cart.disc_type || 'fixed'; $('discVal').value = h.cart.disc_val || 0;
        holdId = h.id; render();
    });

    /* ---------- offline: catalogue + queued sales (Phase 10) ---------- */
    const esc2 = esc;
    const netState = () => {
        const on = navigator.onLine, b = $('netState');
        b.className = 'badge align-self-center ' + (on ? 'bg-success' : 'bg-danger');
        b.querySelector('span').textContent = on ? 'Online' : 'Offline';
    };
    const loadCatalog = async () => {
        if (!navigator.onLine) return;
        try {
            const res = await fetch(R.catalog, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
            if (!res.ok || !(res.headers.get('content-type') || '').includes('json')) return;
            const data = await res.json();
            if (data && Array.isArray(data.items) && data.warehouse === WAREHOUSE) { catalog = data; PO.saveCatalog(BRANCH, data); }
        } catch (e) { /* keep the saved copy */ }
    };
    const refreshQueue = async () => {
        const list = await PO.queued(BRANCH);
        const badge = $('offlineBadge');
        badge.classList.toggle('d-none', !list.length);
        badge.querySelector('strong').textContent = list.length;
        const bad = list.filter(s => s.status === 'error');
        const panel = $('offlinePanel');
        panel.classList.toggle('d-none', !bad.length);
        panel.innerHTML = bad.length ? '<strong><i class="fa-solid fa-triangle-exclamation"></i> এই অফলাইন বিক্রিগুলো সিস্টেমে যোগ করা যায়নি:</strong>' + bad.map(s =>
            `<div class="d-flex flex-wrap gap-2 align-items-center mt-1"><span>${esc2(new Date(s.created_at).toLocaleString())} · ৳ ${money(s.receipt?.total)} — <em>${esc2(s.error)}</em></span>
             <button type="button" class="btn btn-sm btn-dark py-0 q-retry" data-id="${esc2(s.id)}">আবার চেষ্টা</button>
             <button type="button" class="btn btn-sm btn-outline-secondary py-0 q-print" data-id="${esc2(s.id)}">রসিদ</button>
             <button type="button" class="btn btn-sm btn-outline-danger py-0 q-del" data-id="${esc2(s.id)}">বাতিল</button></div>`).join('') : '';
        panel._list = bad;
    };
    let syncTimer = null;
    const syncNow = async (manual) => {
        const r = await PO.sync(BRANCH, R.checkout, () => CSRF, refreshToken);
        const badge = $('offlineBadge');
        badge.classList.toggle('btn-danger', r.stopped === 'login');
        badge.classList.toggle('btn-warning', r.stopped !== 'login');
        badge.title = r.stopped === 'login' ? 'সেশন শেষ — আবার লগইন করুন, তারপর নিজে থেকেই পাঠানো হবে' : 'নেট এলে নিজে থেকেই পাঠানো হবে — এখনই পাঠাতে ক্লিক করুন';
        if (r.stopped === 'login' && manual) alert('সেশন শেষ হয়ে গেছে — আবার লগইন করুন, তারপর বিক্রিগুলো নিজে থেকেই পাঠানো হবে।');
        if (r.sent) { loadCatalog(); loadHolds(); }
        refreshQueue();
    };
    $('offlineBadge').addEventListener('click', () => syncNow(true));
    $('offlinePanel').addEventListener('click', async e => {
        const b = e.target.closest('[data-id]'); if (!b) return;
        const id = b.dataset.id;
        if (b.classList.contains('q-retry')) { await PO.retry(id); return syncNow(true); }
        if (b.classList.contains('q-print')) { const s = ($('offlinePanel')._list || []).find(x => x.id === id); if (s) PO.printReceipt(s, SHOP); return; }
        if (b.classList.contains('q-del') && confirm('এই অফলাইন বিক্রিটি বাতিল করবেন? এটি আর সিস্টেমে যোগ হবে না।')) { await PO.remove(id); refreshQueue(); }
    });
    addEventListener('online', () => { netState(); syncNow(false); });
    addEventListener('offline', netState);
    (async () => {
        netState();
        catalog = await PO.getCatalog(BRANCH);
        if (catalog && catalog.warehouse !== null && catalog.warehouse !== WAREHOUSE) catalog = null;   // copy of another register's warehouse
        await loadCatalog();
        refreshQueue(); syncNow(false);
        setInterval(loadCatalog, 15 * 60 * 1000);
        syncTimer = setInterval(() => syncNow(false), 60 * 1000);
    })();

    /* ---------- keyboard ---------- */
    document.addEventListener('keydown', e => {
        if (e.key === 'F2') { e.preventDefault(); search.focus(); }
        if (e.key === 'F9') { e.preventDefault(); pay(); }
    });
    document.addEventListener('click', e => { if (!e.target.closest('#posResults, #posSearch')) results.innerHTML = ''; });

    render(); loadHolds();
})();
</script>
@endpush
