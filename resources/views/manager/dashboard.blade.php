@extends('layouts.manager')
@section('title', __('Dashboard'))

@section('content')
@php
    $tk = fn ($v) => '৳ '.number_format((float) $v, 0);
    $cards = [
        ['fa-sack-dollar', __('Total Revenue'), $tk($stats['revenue']), __('paid orders'), '#bfd7fd', '#1d4ed8', route('manager.orders.index', ['payment' => 'paid'])],
        ['fa-calendar-day', __("Today's Sales"), $tk($stats['today']), now()->format('d M Y'), '#fde6c2', '#c2410c', route('manager.orders.index', ['from' => today()->toDateString()])],
        ['fa-chart-line', __('This Month'), $tk($stats['month']), now()->format('F Y'), '#c0ead7', '#047857', route('manager.orders.index', ['from' => now()->startOfMonth()->toDateString()])],
        ['fa-receipt', __('Total Orders'), number_format($stats['orders']), __('all time'), '#d4c5f2', '#6d28d9', route('manager.orders.index')],
        ['fa-hourglass-half', __('Pending Orders'), number_format($stats['pending']), __('need action'), '#fbd0d0', '#b91c1c', route('manager.orders.index', ['status' => 'pending'])],
        ['fa-money-check-dollar', __('Payments to Verify'), number_format($stats['to_verify']), 'bKash / Nagad', '#f3c9f9', '#a21caf', route('manager.orders.index', ['payment' => 'pending_verification'])],
        ['fa-circle-check', __('Delivered'), number_format($stats['completed']), __('delivered / completed'), '#f8bcd4', '#be185d', route('manager.orders.index', ['status' => 'delivered'])],
        ['fa-ban', __('Cancelled'), number_format($stats['cancelled']), __('cancelled / failed'), '#bee8fb', '#0369a1', route('manager.orders.index', ['status' => 'cancelled'])],
        ['fa-boxes-stacked', __('Products'), number_format($stats['products']), __('in catalogue'), '#bfead6', '#15803d', route('manager.products.index')],
        ['fa-triangle-exclamation', __('Low Stock'), number_format($stats['low_stock']), __('at or below minimum'), '#fbd7f0', '#c026d3', route('manager.products.index', ['stock' => 'low'])],
        ['fa-users', __('Customers'), number_format($stats['customers']), 'CRM', '#bfd5fb', '#1e40af', route('manager.customers.index')],
        ['fa-rotate-left', __('Open Returns'), number_format($stats['returns']), __('waiting'), '#d4c8f3', '#5b21b6', route('manager.returns.index')],
    ];
    $months = ['Jan', 'Feb', 'March', 'April', 'May', 'June', 'July', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 small">
    <span class="text-muted"><i class="fa-solid fa-globe text-primary"></i> {{ __('Your store is live') }}: <a href="{{ $branch->url }}" target="_blank"><code>{{ preg_replace('#^https?://#', '', $branch->url) }}</code></a></span>
    <button class="btn btn-sm btn-outline-primary" onclick="navigator.clipboard.writeText(@js($branch->url));this.textContent='Copied!'"><i class="fa-regular fa-copy"></i> Copy link</button>
</div>

{{-- 12 statistic cards --}}
<div class="row g-4 mb-4">
    @foreach ($cards as [$icon, $label, $value, $sub, $bg, $color, $link])
        <div class="col-6 col-md-4 col-xl-2">
            <a href="{{ $link }}" class="st-card" style="background:{{ $bg }}">
                <span class="st-l">{{ $label }}</span>
                <span class="st-ic" style="color:{{ $color }}"><i class="fa-solid {{ $icon }}"></i></span>
                <span class="st-v" style="color:{{ $color }}">{{ $value }}</span>
                <span class="st-s">{{ $sub }}</span>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    {{-- New Order --}}
    <div class="col-lg-6">
        <div class="dp yellow">
            <div class="dp-head">
                <h6><i class="fa-solid fa-bag-shopping me-1 text-warning"></i> {{ __('New Order') }}</h6>
                <select class="form-select form-select-sm js-filter" data-key="os">
                    <option value="">{{ __('Status') }}: {{ __('All') }}</option>
                    @foreach (\App\Models\Order::STATUS_META as $k => [$l])<option value="{{ $k }}" @selected($os === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div class="dp-body no-list">
                @forelse ($recent as $o)
                    <a href="{{ route('manager.orders.show', $o) }}" class="row-o">
                        <span class="flex-grow-1 min-w-0"><span class="num d-block">{{ $o->order_number }} @if($o->source === 'pos')<span class="badge bg-dark" style="font-size:9px">POS</span>@endif</span><span class="who d-block text-truncate">{{ $o->customer_name }}</span></span>
                        <span class="d-none d-sm-block fw-semibold small">@money($o->total)</span>
                        <span class="when">{{ $o->created_at->format('d M, Y') }}<br>{{ $o->created_at->format('h:i A') }}</span>
                        <span class="stat"><span class="badge rounded-pill bg-{{ $o->status_color }}">{{ $o->status_label }}</span></span>
                    </a>
                @empty
                    <div class="text-center text-muted py-5"><i class="fa-regular fa-folder-open fs-2 d-block mb-2"></i>{{ __('No orders yet.') }}</div>
                @endforelse
                <div class="text-end mt-2"><a href="{{ route('manager.orders.index', array_filter(['status' => $os])) }}" class="small">{{ __('View all') }} <i class="fa-solid fa-arrow-right"></i></a></div>
            </div>
        </div>
    </div>

    {{-- Sales by payment method --}}
    <div class="col-lg-6">
        <div class="dp green">
            <div class="dp-head">
                <h6><i class="fa-solid fa-wallet me-1 text-success"></i> {{ __('Sales By Payment Method') }}</h6>
                <select class="form-select form-select-sm js-filter" data-key="y_pay">@foreach ($years as $y)<option @selected($y === $yPay)>{{ $y }}</option>@endforeach</select>
            </div>
            <div class="dp-body">
                @if (collect($charts['payment'])->sum('total') > 0)
                    <div class="row align-items-center g-3">
                        <div class="col-sm-6"><div class="chart-box sm"><canvas id="chPay"></canvas></div></div>
                        <div class="col-sm-6 small">
                            @foreach ($charts['payment'] as $i => $p)
                                <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                                    <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:var(--pc{{ $i % 8 }})"></span>
                                    <span class="flex-grow-1">{{ $p['label'] }} <span class="text-muted">({{ $p['count'] }})</span></span>
                                    <strong>@money($p['total'])</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center text-muted py-5"><i class="fa-solid fa-chart-pie fs-2 d-block mb-2"></i>{{ $yPay }} — {{ __('no sales yet') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Income vs Expense --}}
<div class="dp pink mb-4">
    <div class="dp-head">
        <h6><i class="fa-solid fa-scale-balanced me-1 text-danger"></i> {{ __('Income VS Expense') }}</h6>
        <select class="form-select form-select-sm js-filter" data-key="y_ie">@foreach ($years as $y)<option @selected($y === $yIe)>{{ $y }}</option>@endforeach</select>
    </div>
    <div class="dp-body">
        <div class="chart-box"><canvas id="chIe"></canvas></div>
        <div class="small text-muted text-center mt-2">Income = {{ __('sales (without cancelled)') }} · Expense = {{ __('expense vouchers (Accounts)') }} · {{ $yIe }}: ৳ {{ number_format(array_sum($charts['income'])) }} / ৳ {{ number_format(array_sum($charts['expense'])) }}</div>
    </div>
</div>

{{-- Sales ratio --}}
<div class="dp purple mb-2">
    <div class="dp-head">
        <h6><i class="fa-solid fa-chart-line me-1" style="color:#7c3aed"></i> {{ __('Sales Ratio') }}</h6>
        <select class="form-select form-select-sm js-filter" data-key="y_sr">@foreach ($years as $y)<option @selected($y === $ySr)>{{ $y }}</option>@endforeach</select>
    </div>
    <div class="dp-body"><div class="chart-box"><canvas id="chSr"></canvas></div></div>
</div>
@endsection

@push('scripts')
<style>:root{--pc0:#4f46e5;--pc1:#f97316;--pc2:#10b981;--pc3:#ec4899;--pc4:#0ea5e9;--pc5:#eab308;--pc6:#8b5cf6;--pc7:#64748b}.min-w-0{min-width:0}</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    // year / status dropdowns → reload with that one parameter changed
    document.querySelectorAll('.js-filter').forEach(s => s.addEventListener('change', () => {
        const u = new URL(location.href);
        s.value ? u.searchParams.set(s.dataset.key, s.value) : u.searchParams.delete(s.dataset.key);
        location.href = u.toString();
    }));
    if (!window.Chart) return;   // CDN not reachable (offline) — cards and list still work

    const months = @json($months);
    const pay = @json($charts['payment']);
    const income = @json($charts['income']), expense = @json($charts['expense']), orders = @json($charts['orders']);
    const colors = ['#4f46e5', '#f97316', '#10b981', '#ec4899', '#0ea5e9', '#eab308', '#8b5cf6', '#64748b'];
    Chart.defaults.font.family = "'Poppins','Hind Siliguri',sans-serif";
    Chart.defaults.color = '#4b5563';
    const tk = v => '৳ ' + Number(v).toLocaleString();
    const line = (color) => ({borderColor: color, backgroundColor: color + '22', pointBackgroundColor: '#fff', pointBorderColor: color, pointRadius: 4, pointHoverRadius: 6, borderWidth: 2.5, tension: .35, fill: true});
    const legend = {position: 'top', labels: {usePointStyle: true, pointStyle: 'circle', boxWidth: 10, padding: 18}};

    const pc = document.getElementById('chPay');
    if (pc) new Chart(pc, {type: 'doughnut', data: {labels: pay.map(p => p.label), datasets: [{data: pay.map(p => p.total), backgroundColor: colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 8}]},
        options: {maintainAspectRatio: false, cutout: '62%', plugins: {legend: {display: false}, tooltip: {callbacks: {label: c => ' ' + c.label + ': ' + tk(c.raw)}}}}});

    new Chart(document.getElementById('chIe'), {type: 'line', data: {labels: months, datasets: [
            {label: 'Income', data: income, ...line('#2d2de8')},
            {label: 'Expense', data: expense, ...line('#f4511e')},
        ]}, options: {maintainAspectRatio: false, interaction: {mode: 'index', intersect: false}, plugins: {legend, tooltip: {callbacks: {label: c => ' ' + c.dataset.label + ': ' + tk(c.raw)}}},
        scales: {y: {beginAtZero: true, ticks: {callback: v => Number(v).toLocaleString()}}, x: {grid: {display: false}}}}});

    new Chart(document.getElementById('chSr'), {type: 'line', data: {labels: months, datasets: [
            {label: 'Total Orders Qty: ' + orders.reduce((a, b) => a + b, 0), data: orders, ...line('#2d2de8')},
        ]}, options: {maintainAspectRatio: false, interaction: {mode: 'index', intersect: false}, plugins: {legend},
        scales: {y: {beginAtZero: true, ticks: {precision: 0}}, x: {grid: {display: false}}}}});
})();
</script>
@endpush
