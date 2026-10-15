@extends('layouts.manager')
@section('title', 'Purchase Report')

@section('content')
@include('manager.reports._nav')
@include('manager.reports._filter')
<div class="row g-4">
    <div class="col-xl-7">
        <div class="panel">
            <div class="panel-head">Purchases by supplier · {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0 small">
                <thead class="table-light"><tr><th>Supplier</th><th class="text-end">Bills</th><th class="text-end">Total</th><th class="text-end">Returned</th><th class="text-end">Paid</th><th class="text-end">Due</th></tr></thead>
                @forelse ($rows as $r)
                    <tr><td><a href="{{ route('manager.suppliers.show', $r->id) }}">{{ $r->name }}</a></td><td class="text-end">{{ $r->c }}</td><td class="text-end">{{ number_format($r->total, 2) }}</td><td class="text-end">{{ number_format($r->returned, 2) }}</td><td class="text-end">{{ number_format($r->paid, 2) }}</td><td class="text-end fw-semibold {{ $r->due > 0 ? 'text-danger' : '' }}">{{ number_format($r->due, 2) }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">এই সময়ে কোনো ক্রয় নেই।</td></tr>
                @endforelse
                @if ($rows->isNotEmpty())
                    <tfoot><tr class="fw-bold"><td>Total</td><td class="text-end">{{ $rows->sum('c') }}</td><td class="text-end">{{ number_format($rows->sum('total'), 2) }}</td><td class="text-end">{{ number_format($rows->sum('returned'), 2) }}</td><td class="text-end">{{ number_format($rows->sum('paid'), 2) }}</td><td class="text-end">{{ number_format($rows->sum('due'), 2) }}</td></tr></tfoot>
                @endif
            </table></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="panel">
            <div class="panel-head">Supplier dues (all time) <strong class="text-danger">@money($dues->where('due_amount', '>', 0)->sum('due_amount'))</strong></div>
            <table class="table table-sm mb-0 small">
                @forelse ($dues as $s)
                    <tr><td><a href="{{ route('manager.suppliers.show', $s) }}">{{ $s->name }}</a> <span class="text-muted">{{ $s->phone }}</span></td><td class="text-end {{ $s->due_amount > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($s->due_amount, 2) }}</td></tr>
                @empty
                    <tr><td class="text-muted text-center py-4">কোনো বকেয়া নেই।</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection
