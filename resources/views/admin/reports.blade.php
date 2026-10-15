@extends('layouts.admin')
@section('title', 'Platform Reports')

@section('content')
<form class="d-flex flex-wrap gap-2 mb-3 align-items-center small" method="GET">
    From <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm" style="width:150px">
    To <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm" style="width:150px">
    <button class="btn btn-sm btn-dark">Show</button>
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-success ms-auto"><i class="fa-solid fa-file-csv"></i> CSV</a>
    <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-print"></i> Print</button>
</form>

<div class="row g-3 mb-4">
    @foreach ([
        ['fa-chart-line', 'MRR', '৳ '.number_format($mrr), '#dcfce7', '#15803d'],
        ['fa-chart-column', 'ARR', '৳ '.number_format($arr), '#f0fdf4', '#166534'],
        ['fa-sack-dollar', 'Subscription revenue (period)', '৳ '.number_format($months->sum('subscription_revenue')), '#dbeafe', '#1d4ed8'],
        ['fa-percent', 'Commission (period)', '৳ '.number_format($months->sum('commission')), '#fef3c7', '#b45309'],
        ['fa-store', 'Branches', $totals['branches'].' · '.$totals['active'].' paid · '.$totals['trial'].' trial', '#e0f2fe', '#0369a1'],
        ['fa-cart-shopping', 'GMV (period)', '৳ '.number_format($months->sum('gmv')), '#fae8ff', '#a21caf'],
        ['fa-user-xmark', 'Expired / cancelled', $totals['expired'], '#fee2e2', '#b91c1c'],
        ['fa-file-invoice', 'Unpaid invoices', '৳ '.number_format($totals['unpaid_amount']).' · '.$totals['pending_invoices'].' to verify', '#fef9c3', '#a16207'],
    ] as [$icon, $label, $value, $bg, $color])
        <div class="col-6 col-xl-3"><div class="stat-card"><div class="ic" style="background:{{ $bg }};color:{{ $color }}"><i class="fa-solid {{ $icon }}"></i></div><div><div class="v">{{ $value }}</div><div class="l">{{ $label }}</div></div></div></div>
    @endforeach
</div>

<div class="panel mb-4"><div class="panel-head">Monthly trend</div><div class="panel-body"><canvas id="platformChart" height="90"></canvas></div></div>

<div class="row g-4">
    <div class="col-xl-7">
        <div class="panel mb-4">
            <div class="panel-head">Month by month</div>
            <div class="table-responsive"><table class="table table-sm mb-0 small">
                <thead class="table-light"><tr><th>Month</th><th class="text-end">Paid invoices</th><th class="text-end">Subscription ৳</th><th class="text-end">Orders</th><th class="text-end">GMV ৳</th><th class="text-end">Commission ৳</th><th class="text-end">New branches</th></tr></thead>
                @foreach ($months as $m)
                    <tr><td>{{ \Carbon\Carbon::parse($m['month'].'-01')->format('M Y') }}</td><td class="text-end">{{ $m['invoices'] }}</td><td class="text-end">{{ number_format($m['subscription_revenue']) }}</td><td class="text-end">{{ $m['orders'] }}</td><td class="text-end">{{ number_format($m['gmv']) }}</td><td class="text-end">{{ number_format($m['commission']) }}</td><td class="text-end">{{ $m['new_branches'] }}</td></tr>
                @endforeach
                <tfoot><tr class="fw-bold"><td>Total</td><td class="text-end">{{ $months->sum('invoices') }}</td><td class="text-end">{{ number_format($months->sum('subscription_revenue')) }}</td><td class="text-end">{{ $months->sum('orders') }}</td><td class="text-end">{{ number_format($months->sum('gmv')) }}</td><td class="text-end">{{ number_format($months->sum('commission')) }}</td><td class="text-end">{{ $months->sum('new_branches') }}</td></tr></tfoot>
            </table></div>
        </div>
        <div class="panel">
            <div class="panel-head">Subscriptions by plan</div>
            <table class="table table-sm mb-0 small">
                <thead class="table-light"><tr><th>Plan</th>@foreach (\App\Models\Subscription::STATUSES as $st)<th class="text-end">{{ ucfirst($st) }}</th>@endforeach</tr></thead>
                @foreach ($plans as $p)
                    @php $g = ($byPlan[$p->id] ?? collect())->pluck('c', 'status'); @endphp
                    <tr><td>{{ $p->name }}</td>@foreach (\App\Models\Subscription::STATUSES as $st)<td class="text-end">{{ $g[$st] ?? '' }}</td>@endforeach</tr>
                @endforeach
            </table>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="panel">
            <div class="panel-head">Top branches by GMV (period)</div>
            <table class="table table-sm mb-0 small">
                <thead class="table-light"><tr><th>#</th><th>Branch</th><th class="text-end">Orders</th><th class="text-end">GMV</th><th class="text-end">Commission</th></tr></thead>
                @forelse ($leaders as $r)
                    <tr><td>{{ $loop->iteration }}</td><td>@if($r->branch)<a href="{{ route('admin.branches.show', $r->branch) }}">{{ $r->branch->name }}</a>@else — @endif</td><td class="text-end">{{ $r->orders }}</td><td class="text-end">{{ number_format($r->gmv) }}</td><td class="text-end">{{ number_format($r->commission) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No orders.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('platformChart'), {
    type: 'bar',
    data: {labels: @json($months->pluck('month')), datasets: [
        {label: 'Subscription ৳', data: @json($months->pluck('subscription_revenue')), backgroundColor: '#16a34a', borderRadius: 3},
        {label: 'Commission ৳', data: @json($months->pluck('commission')), backgroundColor: '#f59e0b', borderRadius: 3},
        {label: 'GMV ৳', data: @json($months->pluck('gmv')), type: 'line', yAxisID: 'y1', borderColor: '#a21caf', tension: .3, pointRadius: 2},
    ]},
    options: {plugins: {legend: {position: 'bottom'}}, scales: {y: {beginAtZero: true}, y1: {beginAtZero: true, position: 'right', grid: {display: false}}}},
});
</script>
@endpush
