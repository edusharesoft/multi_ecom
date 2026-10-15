@extends('layouts.manager')
@section('title', 'Reports')

@section('content')
@include('manager.reports._nav')
<div class="row g-3 mb-4">
    @foreach ([
        ['fa-calendar-day', "আজকের বিক্রি", '৳ '.number_format($today['total'], 2), $today['orders'].' orders', '#dbeafe', '#1d4ed8'],
        ['fa-chart-line', 'এই মাসের বিক্রি', '৳ '.number_format($month['total'], 2), $month['orders'].' orders · avg ৳'.number_format($month['avg']), '#fef3c7', '#b45309'],
        ['fa-rotate-left', 'এই মাসে রিফান্ড', '৳ '.number_format($month['refunded'], 2), $month['cancelled'].' cancelled', '#fee2e2', '#b91c1c'],
        $pl ? ['fa-sack-dollar', 'এই মাসের নিট লাভ', '৳ '.number_format($pl['net'], 2), 'margin '.$pl['margin'].'%', $pl['net'] >= 0 ? '#dcfce7' : '#fee2e2', $pl['net'] >= 0 ? '#15803d' : '#b91c1c']
            : ['fa-percent', 'এই মাসে VAT/Tax', '৳ '.number_format($month['tax'], 2), 'collected', '#f3e8ff', '#7e22ce'],
    ] as [$icon, $label, $value, $sub, $bg, $color])
        <div class="col-6 col-xl-3"><div class="stat-card"><div class="ic" style="background:{{ $bg }};color:{{ $color }}"><i class="fa-solid {{ $icon }}"></i></div>
            <div><div class="v">{{ $value }}</div><div class="l">{{ $label }} <span class="text-muted">· {{ $sub }}</span></div></div></div></div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel h-100"><div class="panel-head">Sales — last 30 days</div>
            <div class="panel-body"><canvas id="trend" height="120"></canvas></div></div>
    </div>
    <div class="col-xl-4">
        <div class="panel h-100"><div class="panel-head">এই মাসের সারাংশ</div>
            <table class="table table-sm mb-0 small">
                <tr><td>Gross sales (subtotal)</td><td class="text-end">@money($month['subtotal'])</td></tr>
                <tr><td>Discount</td><td class="text-end text-danger">− @money($month['discount'])</td></tr>
                <tr><td>Shipping charge</td><td class="text-end">@money($month['shipping'])</td></tr>
                <tr><td>VAT / Tax</td><td class="text-end">@money($month['tax'])</td></tr>
                <tr class="fw-semibold"><td>Invoice total</td><td class="text-end">@money($month['total'])</td></tr>
                <tr><td>Refunded</td><td class="text-end text-danger">− @money($month['refunded'])</td></tr>
                <tr class="fw-bold"><td>Net sales</td><td class="text-end">@money($month['net'])</td></tr>
                <tr><td>Platform commission</td><td class="text-end">@money($month['commission'])</td></tr>
                @if ($pl)
                    <tr><td>Cost of goods sold</td><td class="text-end">@money($pl['cogs'])</td></tr>
                    <tr><td>Expenses</td><td class="text-end">@money($pl['expenses']->sum())</td></tr>
                    <tr class="fw-bold {{ $pl['net'] >= 0 ? 'text-success' : 'text-danger' }}"><td>Net profit</td><td class="text-end">@money($pl['net'])</td></tr>
                @endif
            </table>
            @unless ($hasAccounting)<div class="panel-body small text-muted"><i class="fa-solid fa-lock"></i> লাভ-ক্ষতি, খরচ ও ক্যাশ বুক দেখতে <a href="{{ route('manager.billing.plans') }}">প্ল্যান আপগ্রেড</a> করুন।</div>@endunless
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('trend'), {
    type: 'bar',
    data: {
        labels: @json($trend->pluck('period')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d M'))),
        datasets: [
            {label: 'Net sales ৳', data: @json($trend->pluck('net')), backgroundColor: '#e41e26', borderRadius: 3},
            {label: 'Orders', data: @json($trend->pluck('orders')), type: 'line', yAxisID: 'y1', borderColor: '#1d4ed8', backgroundColor: '#1d4ed8', tension: .3, pointRadius: 2},
        ],
    },
    options: {plugins: {legend: {position: 'bottom'}}, scales: {y: {beginAtZero: true}, y1: {beginAtZero: true, position: 'right', grid: {display: false}}}},
});
</script>
@endpush
