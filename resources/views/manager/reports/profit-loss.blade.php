@extends('layouts.manager')
@section('title', 'Profit & Loss')

@section('content')
@include('manager.reports._nav')
@include('manager.reports._filter', ['noExport' => true])
@php $b = \App\Support\Tenancy::branch(); @endphp
<div class="row justify-content-center">
<div class="col-xl-8">
<div class="panel">
    <div class="panel-body">
        <div class="text-center mb-3">
            <h5 class="fw-bold mb-0">{{ $b->store_name ?: $b->name }}</h5>
            <div>Profit & Loss Statement (লাভ-ক্ষতির হিসাব)</div>
            <div class="small text-muted">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</div>
        </div>
        <table class="table table-sm small mb-0">
            <tr class="table-light fw-bold"><td colspan="2">Revenue (আয়)</td></tr>
            <tr><td class="ps-4">Gross sales ({{ $pl['s']['orders'] }} orders)</td><td class="text-end">{{ number_format($pl['s']['subtotal'], 2) }}</td></tr>
            <tr><td class="ps-4">Less: discounts</td><td class="text-end">({{ number_format($pl['s']['discount'], 2) }})</td></tr>
            <tr><td class="ps-4">Shipping / delivery charge</td><td class="text-end">{{ number_format($pl['s']['shipping'], 2) }}</td></tr>
            <tr><td class="ps-4">Less: refunds & returns</td><td class="text-end">({{ number_format($pl['refunds'] + $pl['walletRefunds'], 2) }})</td></tr>
            <tr class="fw-semibold"><td>Net revenue</td><td class="text-end">{{ number_format($pl['revenue'], 2) }}</td></tr>

            <tr class="table-light fw-bold"><td colspan="2">Cost of goods sold</td></tr>
            <tr><td class="ps-4">COGS (sold items at purchase cost, net of returns)</td><td class="text-end">({{ number_format($pl['cogs'], 2) }})</td></tr>
            <tr class="fw-bold"><td>Gross profit</td><td class="text-end">{{ number_format($pl['gross'], 2) }}</td></tr>

            <tr class="table-light fw-bold"><td colspan="2">Operating expenses (খরচ)</td></tr>
            @foreach ($pl['expenses'] as $name => $amt)
                <tr><td class="ps-4">{{ $name }}</td><td class="text-end">({{ number_format($amt, 2) }})</td></tr>
            @endforeach
            @if ($pl['commission'] > 0)<tr><td class="ps-4">Platform commission</td><td class="text-end">({{ number_format($pl['commission'], 2) }})</td></tr>@endif
            @if ($pl['stockLoss'] != 0)<tr><td class="ps-4">Stock adjustments / damage (net)</td><td class="text-end">({{ number_format($pl['stockLoss'], 2) }})</td></tr>@endif
            <tr class="fw-semibold"><td>Total expenses</td><td class="text-end">({{ number_format($pl['opex'], 2) }})</td></tr>

            @if ($pl['income']->isNotEmpty())
                <tr class="table-light fw-bold"><td colspan="2">Other income</td></tr>
                @foreach ($pl['income'] as $name => $amt)<tr><td class="ps-4">{{ $name }}</td><td class="text-end">{{ number_format($amt, 2) }}</td></tr>@endforeach
            @endif

            <tr class="fw-bold fs-6 {{ $pl['net'] >= 0 ? 'table-success' : 'table-danger' }}"><td>Net {{ $pl['net'] >= 0 ? 'profit (নিট লাভ)' : 'loss (নিট ক্ষতি)' }}</td><td class="text-end">৳ {{ number_format($pl['net'], 2) }}</td></tr>
            <tr><td class="text-muted">Net margin</td><td class="text-end text-muted">{{ $pl['margin'] }}%</td></tr>
        </table>
        <p class="small text-muted mt-3 mb-0">
            VAT/Tax collected ৳ {{ number_format($pl['tax'], 2) }} আয়ের মধ্যে ধরা হয়নি (সরকারের পাওনা)। বাতিল/ব্যর্থ অর্ডার বাদ। রিফান্ড যে তারিখে দেওয়া হয়েছে সেই সময়ের হিসাবে কমানো হয়েছে।
            খরচ যোগ করুন <a href="{{ route('manager.vouchers.index') }}" class="d-print-none">Expenses</a> পাতায়।
        </p>
    </div>
</div>
</div>
</div>
@endsection
