@extends('layouts.manager')
@section('title', 'Sales Report')

@section('content')
@include('manager.reports._nav')
@include('manager.reports._filter', ['fields' => ['group' => ['day' => 'Daily', 'month' => 'Monthly']]])

<div class="row g-3 mb-3">
    @foreach ([['Orders', number_format($summary['orders'])], ['Invoice total', '৳ '.number_format($summary['total'], 2)], ['Refunded', '৳ '.number_format($summary['refunded'], 2)], ['Net sales', '৳ '.number_format($summary['net'], 2)], ['Avg. order', '৳ '.number_format($summary['avg'], 2)], ['Discount given', '৳ '.number_format($summary['discount'], 2)], ['VAT / Tax', '৳ '.number_format($summary['tax'], 2)], ['Cancelled / failed', $summary['cancelled']]] as [$l, $v])
        <div class="col-6 col-lg-3"><div class="stat-card"><div><div class="v">{{ $v }}</div><div class="l">{{ $l }}</div></div></div></div>
    @endforeach
</div>

<div class="panel mb-3 d-print-none"><div class="panel-body"><canvas id="salesChart" height="90"></canvas></div></div>

<div class="row g-3 mb-3">
    @foreach (['By payment method' => [$byMethod, \App\Models\Order::METHOD_LABELS], 'By channel' => [$bySource, ['online' => 'Online store', 'pos' => 'POS (shop)']], 'By staff (POS)' => [$byStaff, []], 'By order status' => [$byStatus, collect(\App\Models\Order::STATUS_META)->map(fn ($m) => $m[0])->all()]] as $title => [$list, $labels])
        <div class="col-md-6 col-xl-3"><div class="panel h-100"><div class="panel-head">{{ $title }}</div>
            <table class="table table-sm mb-0 small">
                @forelse ($list as $r)<tr><td>{{ $labels[$r->k] ?? ucfirst(str_replace('_', ' ', $r->k)) }} <span class="text-muted">({{ $r->c }})</span></td><td class="text-end">{{ number_format($r->total - $r->ref, 2) }}</td></tr>
                @empty<tr><td class="text-muted">—</td></tr>@endforelse
            </table></div></div>
    @endforeach
</div>

<div class="panel">
    <div class="panel-head">{{ $group === 'month' ? 'Monthly' : 'Daily' }} sales · {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0 small">
        <thead class="table-light"><tr><th>{{ $group === 'month' ? 'Month' : 'Date' }}</th><th class="text-end">Orders</th><th class="text-end">Subtotal</th><th class="text-end">Discount</th><th class="text-end">Shipping</th><th class="text-end">Tax</th><th class="text-end">Total</th><th class="text-end">Refunded</th><th class="text-end">Net</th></tr></thead>
        @foreach ($rows->where('orders', '>', 0) as $r)
            <tr><td>{{ $r['period'] }}</td><td class="text-end">{{ $r['orders'] }}</td><td class="text-end">{{ number_format($r['subtotal'], 2) }}</td><td class="text-end">{{ number_format($r['discount'], 2) }}</td><td class="text-end">{{ number_format($r['shipping'], 2) }}</td><td class="text-end">{{ number_format($r['tax'], 2) }}</td><td class="text-end">{{ number_format($r['total'], 2) }}</td><td class="text-end text-danger">{{ $r['refunded'] ? number_format($r['refunded'], 2) : '' }}</td><td class="text-end fw-semibold">{{ number_format($r['net'], 2) }}</td></tr>
        @endforeach
        <tfoot><tr class="fw-bold"><td>Total</td><td class="text-end">{{ $rows->sum('orders') }}</td><td class="text-end">{{ number_format($rows->sum('subtotal'), 2) }}</td><td class="text-end">{{ number_format($rows->sum('discount'), 2) }}</td><td class="text-end">{{ number_format($rows->sum('shipping'), 2) }}</td><td class="text-end">{{ number_format($rows->sum('tax'), 2) }}</td><td class="text-end">{{ number_format($rows->sum('total'), 2) }}</td><td class="text-end">{{ number_format($rows->sum('refunded'), 2) }}</td><td class="text-end">{{ number_format($rows->sum('net'), 2) }}</td></tr></tfoot>
    </table></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {labels: @json($rows->pluck('period')), datasets: [
        {label: 'Net sales ৳', data: @json($rows->pluck('net')), borderColor: '#e41e26', backgroundColor: 'rgba(228,30,38,.12)', fill: true, tension: .3, pointRadius: 2},
        {label: 'Refunded ৳', data: @json($rows->pluck('refunded')), borderColor: '#64748b', tension: .3, pointRadius: 0},
    ]},
    options: {plugins: {legend: {position: 'bottom'}}, scales: {y: {beginAtZero: true}}},
});
</script>
@endpush
