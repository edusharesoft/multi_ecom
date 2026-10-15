@extends('layouts.admin')
@section('title', __('SaaS Dashboard'))

@section('content')
@php
    $tk = fn ($v) => '৳ '.number_format((float) $v, 0);
    $cards = [
        ['fa-store', __('Total Branches'), number_format($stats['branches']), __('suspended').': '.$stats['suspended'], '#bfd7fd', '#1d4ed8', route('admin.branches.index')],
        ['fa-circle-check', __('Active (paid)'), number_format($stats['active']), __('subscriptions'), '#fde6c2', '#c2410c', route('admin.subscriptions.index', ['status' => 'active'])],
        ['fa-flask', __('Trial Branches'), number_format($stats['trial']), __('on free trial'), '#c0ead7', '#047857', route('admin.subscriptions.index', ['status' => 'trial'])],
        ['fa-hourglass-end', __('Expired / Grace'), $stats['expired'].' / '.$stats['grace'], __('need renewal'), '#d4c5f2', '#6d28d9', route('admin.subscriptions.index', ['status' => 'expired'])],
        ['fa-chart-line', 'MRR', $tk($stats['mrr']), 'ARR '.$tk($stats['arr']), '#fbd0d0', '#b91c1c', route('admin.subscriptions.index')],
        ['fa-sack-dollar', __('Revenue this month'), $tk($stats['month_revenue']), now()->format('F Y'), '#f3c9f9', '#a21caf', route('admin.invoices.index', ['status' => 'paid'])],
        ['fa-coins', __('Revenue this year'), $tk($stats['year_revenue']), now()->format('Y'), '#f8bcd4', '#be185d', route('admin.invoices.index', ['status' => 'paid'])],
        ['fa-money-check-dollar', __('Pending Payments'), number_format($stats['pending_payments']), $tk($stats['pending_amount']).' '.__('due'), '#bee8fb', '#0369a1', route('admin.invoices.index', ['status' => 'pending_verification'])],
        ['fa-user-plus', __('New Registrations'), number_format($stats['new_registrations']), __('this month'), '#bfead6', '#15803d', route('admin.branches.index')],
        ['fa-rotate', __('Renewals'), number_format($stats['renewals']), __('this month'), '#fbd7f0', '#c026d3', route('admin.invoices.index', ['status' => 'paid'])],
        ['fa-cart-shopping', __('GMV this month'), $tk($stats['gmv_month']), __('all branch sales'), '#bfd5fb', '#1e40af', route('admin.orders.index')],
        ['fa-percent', __('Commission'), $tk($stats['commission_month']), __('this month').' · '.__('failed pay').': '.$stats['failed_payments'], '#d4c8f3', '#5b21b6', route('admin.commission')],
    ];
    $months = ['Jan', 'Feb', 'March', 'April', 'May', 'June', 'July', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    $plans = $popularPlans->map(fn ($p) => ['label' => $p->name, 'total' => (int) $p->subs])->filter(fn ($p) => $p['total'] > 0)->values();
@endphp

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
    {{-- New Order (all branches) --}}
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
                @forelse ($recentOrders as $o)
                    <a href="{{ route('admin.orders.index', ['q' => $o->order_number]) }}" class="row-o">
                        <span class="flex-grow-1 min-w-0"><span class="num d-block">{{ $o->order_number }} @if($o->source === 'pos')<span class="badge bg-dark" style="font-size:9px">POS</span>@endif</span>
                            <span class="who d-block text-truncate">{{ $o->customer_name }} <span class="text-muted small">· {{ $o->branch?->display_name }}</span></span></span>
                        <span class="d-none d-sm-block fw-semibold small">@money($o->total)</span>
                        <span class="when">{{ $o->created_at->format('d M, Y') }}<br>{{ $o->created_at->format('h:i A') }}</span>
                        <span class="stat"><span class="badge rounded-pill bg-{{ $o->status_color }}">{{ $o->status_label }}</span></span>
                    </a>
                @empty
                    <div class="text-center text-muted py-5"><i class="fa-regular fa-folder-open fs-2 d-block mb-2"></i>{{ __('No orders yet.') }}</div>
                @endforelse
                <div class="text-end mt-2"><a href="{{ route('admin.orders.index', array_filter(['status' => $os])) }}" class="small">{{ __('View all') }} <i class="fa-solid fa-arrow-right"></i></a></div>
            </div>
        </div>
    </div>

    {{-- Subscriptions by plan --}}
    <div class="col-lg-6">
        <div class="dp green">
            <div class="dp-head"><h6><i class="fa-solid fa-layer-group me-1 text-success"></i> {{ __('Subscriptions By Plan') }}</h6>
                <a href="{{ route('admin.plans.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Plans') }}</a></div>
            <div class="dp-body">
                @if ($plans->sum('total') > 0)
                    <div class="row align-items-center g-3">
                        <div class="col-sm-6"><div class="chart-box sm"><canvas id="chPlan"></canvas></div></div>
                        <div class="col-sm-6 small">
                            @foreach ($plans as $i => $p)
                                <div class="d-flex align-items-center gap-2 py-2 border-bottom">
                                    <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:var(--pc{{ $i % 8 }})"></span>
                                    <span class="flex-grow-1">{{ $p['label'] }}</span><strong>{{ $p['total'] }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="text-center text-muted py-5"><i class="fa-solid fa-chart-pie fs-2 d-block mb-2"></i>{{ __('No active subscriptions yet.') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Income --}}
<div class="dp pink mb-4">
    <div class="dp-head">
        <h6><i class="fa-solid fa-scale-balanced me-1 text-danger"></i> {{ __('Subscription Income VS Commission') }}</h6>
        <select class="form-select form-select-sm js-filter" data-key="y_ie">@foreach ($years as $y)<option @selected($y === $yIe)>{{ $y }}</option>@endforeach</select>
    </div>
    <div class="dp-body">
        <div class="chart-box"><canvas id="chIe"></canvas></div>
        <div class="small text-muted text-center mt-2">{{ $yIe }}: {{ __('paid invoices') }} ৳ {{ number_format(array_sum($charts['income'])) }} · {{ __('commission') }} ৳ {{ number_format(array_sum($charts['commission'])) }}</div>
    </div>
</div>

{{-- Sales ratio --}}
<div class="dp purple mb-4">
    <div class="dp-head">
        <h6><i class="fa-solid fa-chart-line me-1" style="color:#7c3aed"></i> {{ __('Sales Ratio') }}</h6>
        <select class="form-select form-select-sm js-filter" data-key="y_sr">@foreach ($years as $y)<option @selected($y === $ySr)>{{ $y }}</option>@endforeach</select>
    </div>
    <div class="dp-body"><div class="chart-box"><canvas id="chSr"></canvas></div></div>
</div>

{{-- action lists --}}
<div class="row g-4">
    <div class="col-lg-4">
        <div class="dp blue">
            <div class="dp-head"><h6 style="font-size:16px"><i class="fa-solid fa-money-check-dollar me-1 text-primary"></i> {{ __('Payments to Verify') }}</h6><a href="{{ route('admin.invoices.index', ['status' => 'pending_verification']) }}" class="small">{{ __('All') }}</a></div>
            <ul class="list-group list-group-flush small">
                @forelse ($pendingInvoices as $inv)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between"><strong>{{ $inv->branch->display_name }}</strong><span>৳ {{ number_format($inv->total) }}</span></div>
                        <div class="text-muted">{{ $inv->plan?->name }} · {{ $inv->payment_method }} · TrxID {{ $inv->transaction_id }}</div>
                        <form method="POST" action="{{ route('admin.invoices.approve', $inv) }}" class="mt-1">@csrf @method('PATCH')<button class="btn btn-sm btn-success py-0">Approve</button></form>
                    </li>
                @empty
                    <li class="list-group-item text-muted">{{ __('Nothing pending.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dp orange">
            <div class="dp-head"><h6 style="font-size:16px"><i class="fa-regular fa-clock me-1 text-warning"></i> {{ __('Expiring in 7 days') }}</h6></div>
            <ul class="list-group list-group-flush small">
                @forelse ($expiring as $s)
                    <li class="list-group-item d-flex justify-content-between"><span><a href="{{ route('admin.branches.show', $s->branch) }}">{{ $s->branch->display_name }}</a><br><span class="text-muted">{{ $s->plan->name }} · {{ $s->status_label }}</span></span><span class="text-danger">{{ $s->ends_at->format('d M') }}</span></li>
                @empty
                    <li class="list-group-item text-muted">{{ __('None.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="dp green">
            <div class="dp-head"><h6 style="font-size:16px"><i class="fa-solid fa-user-plus me-1 text-success"></i> {{ __('New Registrations') }}</h6></div>
            <ul class="list-group list-group-flush small">
                @forelse ($newBranches as $b)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><a href="{{ route('admin.branches.show', $b) }}" class="fw-semibold">{{ $b->display_name }}</a><br><span class="text-muted">/{{ $b->slug }} · {{ $b->created_at->diffForHumans() }}</span></span>
                        <span class="text-end">@include('admin.branches._state', ['b' => $b])<br><small class="text-muted">{{ $b->subscription?->plan?->name ?? 'No plan' }}</small></span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">{{ __('None.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>:root{--pc0:#4f46e5;--pc1:#f97316;--pc2:#10b981;--pc3:#ec4899;--pc4:#0ea5e9;--pc5:#eab308;--pc6:#8b5cf6;--pc7:#64748b}.min-w-0{min-width:0}</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    document.querySelectorAll('.js-filter').forEach(s => s.addEventListener('change', () => {
        const u = new URL(location.href);
        s.value ? u.searchParams.set(s.dataset.key, s.value) : u.searchParams.delete(s.dataset.key);
        location.href = u.toString();
    }));
    if (!window.Chart) return;

    const months = @json($months);
    const plans = @json($plans);
    const c = @json($charts);
    const colors = ['#4f46e5', '#f97316', '#10b981', '#ec4899', '#0ea5e9', '#eab308', '#8b5cf6', '#64748b'];
    Chart.defaults.font.family = "'Poppins','Hind Siliguri',sans-serif";
    Chart.defaults.color = '#4b5563';
    const tk = v => '৳ ' + Number(v).toLocaleString();
    const line = (color) => ({borderColor: color, backgroundColor: color + '22', pointBackgroundColor: '#fff', pointBorderColor: color, pointRadius: 4, pointHoverRadius: 6, borderWidth: 2.5, tension: .35, fill: true});
    const legend = {position: 'top', labels: {usePointStyle: true, pointStyle: 'circle', boxWidth: 10, padding: 18}};

    const pc = document.getElementById('chPlan');
    if (pc) new Chart(pc, {type: 'doughnut', data: {labels: plans.map(p => p.label), datasets: [{data: plans.map(p => p.total), backgroundColor: colors, borderWidth: 3, borderColor: '#fff', hoverOffset: 8}]},
        options: {maintainAspectRatio: false, cutout: '62%', plugins: {legend: {display: false}}}});

    new Chart(document.getElementById('chIe'), {type: 'line', data: {labels: months, datasets: [
            {label: 'Subscription income', data: c.income, ...line('#2d2de8')},
            {label: 'Commission', data: c.commission, ...line('#f4511e')},
        ]}, options: {maintainAspectRatio: false, interaction: {mode: 'index', intersect: false}, plugins: {legend, tooltip: {callbacks: {label: x => ' ' + x.dataset.label + ': ' + tk(x.raw)}}},
        scales: {y: {beginAtZero: true, ticks: {callback: v => Number(v).toLocaleString()}}, x: {grid: {display: false}}}}});

    new Chart(document.getElementById('chSr'), {type: 'line', data: {labels: months, datasets: [
            {label: 'Total Orders Qty: ' + c.orders.reduce((a, b) => a + b, 0), data: c.orders, ...line('#2d2de8')},
            {label: 'New Branches: ' + c.signups.reduce((a, b) => a + b, 0), data: c.signups, ...line('#10b981')},
        ]}, options: {maintainAspectRatio: false, interaction: {mode: 'index', intersect: false}, plugins: {legend},
        scales: {y: {beginAtZero: true, ticks: {precision: 0}}, x: {grid: {display: false}}}}});
})();
</script>
@endpush
