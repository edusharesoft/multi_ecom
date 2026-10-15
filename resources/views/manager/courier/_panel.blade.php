@php
    $svc = app(\App\Services\Courier\ShipmentService::class);
    $shipment = $order->shipment;
    $couriers = \App\Support\Tenancy::branch()->courierAccounts()->where('is_active', true)->where('courier', '!=', 'manual')->get();
    $default = $couriers->firstWhere('is_default', true)?->courier ?? $couriers->first()?->courier ?? 'manual';
@endphp
<div class="panel mb-3">
    <div class="panel-head"><span><i class="fa-solid fa-truck-fast"></i> Courier / Delivery</span>
        @if ($order->customer_phone && $couriers->firstWhere('courier', 'steadfast'))<button type="button" class="btn btn-sm btn-light border" id="fraudBtn" title="Steadfast delivery history of this phone">Fraud check</button>@endif</div>
    <div class="panel-body small">
        <div id="fraudBox" class="mb-2"></div>
        @foreach ($order->shipments as $s)
            <div class="border rounded p-2 mb-2 {{ $s->isOpen() ? '' : 'bg-light' }}">
                <div class="d-flex justify-content-between"><strong>{{ $s->courierLabel() }}</strong><span class="badge bg-{{ \App\Models\Shipment::GROUPS[$s->status_group][1] }}">{{ \App\Models\Shipment::GROUPS[$s->status_group][0] }}</span></div>
                <div>Tracking: <strong>{{ $s->tracking_code ?: $s->consignment_id ?: '—' }}</strong>
                    @if ($url = $s->publicTrackingUrl())<a href="{{ $url }}" target="_blank" class="ms-1"><i class="fa-solid fa-up-right-from-square"></i></a>@endif</div>
                <div class="text-muted">Status: {{ $s->status }} · COD ৳{{ number_format($s->cod_amount, 0) }} @if($s->delivery_charge) · charge ৳{{ number_format($s->delivery_charge, 0) }} @endif
                    <br>{{ $s->created_at->format('d M, h:i A') }} by {{ $s->actor_name }} @if($s->last_synced_at)· synced {{ $s->last_synced_at->diffForHumans() }}@endif</div>
                @perm('orders.edit')
                @if ($s->isOpen())
                    <div class="d-flex gap-1 mt-1 flex-wrap">
                        @if ($s->courier !== 'manual')<form method="POST" action="{{ route('manager.shipments.sync', $s) }}">@csrf<button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-rotate"></i> Sync</button></form>@endif
                        <form method="POST" action="{{ route('manager.shipments.status', $s) }}" class="d-flex gap-1">@csrf @method('PATCH')
                            <select name="group" class="form-select form-select-sm" style="width:130px">@foreach (\App\Models\Shipment::GROUPS as $k => [$l])<option value="{{ $k }}" @selected($s->status_group === $k)>{{ $l }}</option>@endforeach</select>
                            <button class="btn btn-sm btn-light border">Set</button></form>
                    </div>
                @endif
                @endperm
            </div>
        @endforeach

        @perm('orders.edit')
        @if ($svc->canShip($order))
            <form method="POST" action="{{ route('manager.orders.ship', $order) }}" id="shipForm">@csrf
                <label class="form-label mb-0">Courier</label>
                <select name="courier" id="shipCourier" class="form-select form-select-sm mb-2">
                    @foreach ($couriers as $c)<option value="{{ $c->courier }}" @selected($default === $c->courier)>{{ $c->label() }}{{ $c->mode !== 'live' ? ' ('.$c->mode.')' : '' }}</option>@endforeach
                    <option value="manual" @selected($default === 'manual')>Manual / Other (Sundarban, own rider…)</option>
                </select>
                <div class="d-flex gap-2 mb-2">
                    <div class="flex-fill"><label class="form-label mb-0">COD ৳</label><input type="number" step="1" min="0" name="cod_amount" value="{{ (int) round($svc->codFor($order)) }}" class="form-control form-control-sm"></div>
                    <div class="flex-fill"><label class="form-label mb-0">Weight kg</label><input type="number" step="0.1" min="0.1" name="weight" value="0.5" class="form-control form-control-sm"></div>
                </div>
                <textarea name="address" rows="2" class="form-control form-control-sm mb-2" placeholder="Delivery address">{{ $order->shipping_address }}</textarea>
                <div class="ship-opt" data-for="redx">
                    <input id="redxSearch" class="form-control form-control-sm mb-1" placeholder="RedX area খুঁজুন (e.g. Mirpur)">
                    <select name="area_id" id="redxArea" class="form-select form-select-sm mb-2"><option value="">— area —</option></select>
                </div>
                <div class="ship-opt" data-for="manual">
                    <input name="courier_name" class="form-control form-control-sm mb-1" placeholder="Courier name *">
                    <input name="tracking" class="form-control form-control-sm mb-1" placeholder="Tracking / CN number">
                    <input name="tracking_url" class="form-control form-control-sm mb-1" placeholder="Tracking link (optional)">
                    <input type="number" step="1" min="0" name="charge" class="form-control form-control-sm mb-2" placeholder="Delivery charge ৳ (optional)">
                </div>
                <input name="note" class="form-control form-control-sm mb-2" placeholder="Note for rider (optional)">
                <button class="btn btn-sm btn-brand w-100"><i class="fa-solid fa-paper-plane"></i> Send to Courier</button>
                @if ($couriers->isEmpty())<p class="text-muted mt-2 mb-0">Steadfast / Pathao / RedX যুক্ত করতে <a href="{{ route('manager.courier.settings') }}">Courier Settings</a>।</p>@endif
            </form>
        @endif
        @endperm
    </div>
</div>

@push('scripts')
<script>
(() => {
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
    const sel = document.getElementById('shipCourier');
    if (sel) {
        const toggle = () => document.querySelectorAll('.ship-opt').forEach(el => el.style.display = el.dataset.for === sel.value ? '' : 'none');
        sel.addEventListener('change', toggle); toggle();
        const s = document.getElementById('redxSearch'), a = document.getElementById('redxArea');
        let t;
        s?.addEventListener('input', () => { clearTimeout(t); t = setTimeout(async () => {
            const r = await fetch(@json(route('manager.courier.areas')) + '?q=' + encodeURIComponent(s.value), {headers: {Accept: 'application/json'}});
            const list = await r.json();
            a.innerHTML = Array.isArray(list) ? list.map(x => `<option value="${esc(x.id)}">${esc(x.name)} — ${esc(x.district)}</option>`).join('') : `<option value="">${esc(list.message || 'error')}</option>`;
        }, 300); });
    }
    document.getElementById('fraudBtn')?.addEventListener('click', async () => {
        const box = document.getElementById('fraudBox');
        box.textContent = 'Checking…';
        const r = await fetch(@json(route('manager.courier.fraud')) + '?phone=' + encodeURIComponent(@json($order->customer_phone)), {headers: {Accept: 'application/json'}});
        const d = await r.json();
        if (d.total_parcels === undefined) { box.textContent = d.message || 'No data'; return; }
        const tp = Number(d.total_parcels) || 0, td = Number(d.total_delivered) || 0, tc = Number(d.total_cancelled) || 0;
        const rate = tp ? Math.round(td / tp * 100) : 0;
        box.innerHTML = `<div class="alert alert-${rate >= 80 || !tp ? 'success' : (rate >= 50 ? 'warning' : 'danger')} p-2 mb-0">Steadfast history: ${tp} parcels · ${td} delivered · ${tc} cancelled · success ${rate}%</div>`;
    });
})();
</script>
@endpush
