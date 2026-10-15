@extends('layouts.manager')
@section('title', 'VAT / Tax Report')

@section('content')
@include('manager.reports._nav')
@include('manager.reports._filter')
<div class="panel">
    <div class="panel-head">VAT / Tax collected (monthly) <span class="small text-muted fw-normal">Current rate: {{ $rate }}% · Settings থেকে পরিবর্তন করা যায়</span></div>
    <div class="table-responsive"><table class="table table-sm mb-0 small">
        <thead class="table-light"><tr><th>Month</th><th class="text-end">Invoices</th><th class="text-end">Taxable amount</th><th class="text-end">Tax collected</th></tr></thead>
        @forelse ($rows as $r)
            <tr><td>{{ \Carbon\Carbon::parse($r->p.'-01')->format('F Y') }}</td><td class="text-end">{{ $r->c }}</td><td class="text-end">{{ number_format($r->taxable, 2) }}</td><td class="text-end fw-semibold">{{ number_format($r->tax, 2) }}</td></tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-4">No sales.</td></tr>
        @endforelse
        @if ($rows->isNotEmpty())
            <tfoot><tr class="fw-bold"><td>Total</td><td class="text-end">{{ $rows->sum('c') }}</td><td class="text-end">{{ number_format($rows->sum('taxable'), 2) }}</td><td class="text-end">{{ number_format($rows->sum('tax'), 2) }}</td></tr></tfoot>
        @endif
    </table></div>
    <div class="panel-body small text-muted">বাতিল/ব্যর্থ অর্ডার বাদ। সরকারকে পরিশোধিত VAT খরচ হিসেবে "Tax & VAT Paid" ক্যাটাগরিতে এন্ট্রি দিন।</div>
</div>
@endsection
