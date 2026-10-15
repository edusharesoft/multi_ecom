{{--
    Product/variant line picker (search by name / SKU / barcode, scanner friendly).
    params: $withCost (bool), $warehouseSelect (CSS selector of the warehouse <select>, optional), $old (old('items'))
--}}
@php($pickerId = 'picker'.uniqid())
<div id="{{ $pickerId }}" class="item-picker">
    <div class="position-relative mb-2">
        <input type="text" class="form-control picker-search" placeholder="🔍 পণ্যের নাম, SKU বা বারকোড স্ক্যান করুন..." autocomplete="off">
        <div class="list-group position-absolute w-100 shadow picker-results" style="z-index:50;max-height:320px;overflow:auto"></div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm small align-middle">
            <thead class="table-light"><tr><th>Product</th><th style="width:110px">Stock</th><th style="width:110px">Qty</th>@if($withCost)<th style="width:130px">Unit cost ৳</th><th style="width:120px" class="text-end">Total</th>@endif<th style="width:40px"></th></tr></thead>
            <tbody class="picker-rows"></tbody>
            @if ($withCost)<tfoot><tr><th colspan="4" class="text-end">Subtotal</th><th class="text-end picker-subtotal">0.00</th><th></th></tr></tfoot>@endif
        </table>
    </div>
</div>
@push('scripts')
<script>
(() => {
    const root = document.getElementById(@json($pickerId));
    const withCost = @json((bool) $withCost);
    const whSel = @json($warehouseSelect ?? null);
    const search = root.querySelector('.picker-search'), results = root.querySelector('.picker-results'), rows = root.querySelector('.picker-rows');
    const url = @json(route('manager.inventory.lookup'));
    let idx = 0, timer;
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    const recalc = () => {
        if (!withCost) return;
        let sum = 0;
        rows.querySelectorAll('tr').forEach(tr => {
            const t = (+tr.querySelector('.q').value || 0) * (+tr.querySelector('.c').value || 0);
            tr.querySelector('.t').textContent = t.toFixed(2); sum += t;
        });
        root.querySelector('.picker-subtotal').textContent = sum.toFixed(2);
        root.dispatchEvent(new CustomEvent('subtotal', {detail: sum, bubbles: true}));
    };
    const add = (it, qty = 1, cost = null) => {
        const existing = rows.querySelector(`tr[data-key="${it.product_id}-${it.variant_id}"]`);
        if (existing) { const q = existing.querySelector('.q'); q.value = +q.value + qty; recalc(); return; }
        const i = idx++;
        const tr = document.createElement('tr');
        tr.dataset.key = `${it.product_id}-${it.variant_id}`;
        tr.innerHTML = `<td>${esc(it.name)}<br><span class="text-muted">${esc(it.sku)} ${esc(it.barcode)}</span>
                <input type="hidden" name="items[${i}][product_id]" value="${it.product_id}"><input type="hidden" name="items[${i}][variant_id]" value="${it.variant_id}"></td>
            <td class="${it.stock <= 0 ? 'text-danger' : ''}">${it.stock}</td>
            <td><input type="number" min="1" name="items[${i}][quantity]" value="${qty}" class="form-control form-control-sm q" required></td>
            ${withCost ? `<td><input type="number" step="0.01" min="0" name="items[${i}][unit_cost]" value="${cost ?? it.cost}" class="form-control form-control-sm c" required></td><td class="text-end t">0.00</td>` : ''}
            <td><button type="button" class="btn btn-sm btn-light text-danger rm">×</button></td>`;
        rows.appendChild(tr);
        recalc();
    };
    const lookup = async (q, exactAdd = false) => {
        const wh = whSel ? document.querySelector(whSel)?.value : '';
        const r = await fetch(`${url}?q=${encodeURIComponent(q)}&warehouse=${wh || ''}`, {headers: {Accept: 'application/json'}});
        const list = await r.json();
        if (exactAdd && list.length && (list[0].exact || list.length === 1)) { add(list[0]); search.value = ''; results.innerHTML = ''; return; }
        results.innerHTML = list.map((it, n) => `<button type="button" class="list-group-item list-group-item-action small" data-n="${n}">${esc(it.name)} <span class="text-muted">· ${esc(it.sku)} · stock ${it.stock} · ৳${(+it.cost).toFixed(2)}</span></button>`).join('') || '<div class="list-group-item small text-muted">কিছু পাওয়া যায়নি</div>';
        results.querySelectorAll('button').forEach(b => b.addEventListener('click', () => { add(list[b.dataset.n]); search.value = ''; results.innerHTML = ''; search.focus(); }));
    };
    search.addEventListener('input', () => { clearTimeout(timer); if (search.value.trim().length < 2) { results.innerHTML = ''; return; } timer = setTimeout(() => lookup(search.value.trim()), 250); });
    search.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); if (search.value.trim()) lookup(search.value.trim(), true); } });
    rows.addEventListener('input', recalc);
    rows.addEventListener('click', e => { if (e.target.closest('.rm')) { e.target.closest('tr').remove(); recalc(); } });
    document.addEventListener('click', e => { if (!root.contains(e.target)) results.innerHTML = ''; });
})();
</script>
@endpush
