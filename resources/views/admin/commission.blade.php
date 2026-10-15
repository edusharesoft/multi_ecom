@extends('layouts.admin')
@section('title', 'Commission')

@section('content')
<form class="d-flex gap-2 mb-3 align-items-center small" method="GET">
    From <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm" style="width:160px">
    To <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm" style="width:160px">
    <button class="btn btn-sm btn-dark">Show</button>
</form>
<div class="panel">
    <div class="panel-head">Platform commission by branch <span class="small text-muted fw-normal">rate = branch plan's commission % at order time</span></div>
    <table class="table mb-0 small">
        <thead class="table-light"><tr><th>Branch</th><th>Orders</th><th>Sales (GMV)</th><th>Commission</th><th>On paid orders</th></tr></thead>
        <tbody>
        @forelse ($rows as $r)
            <tr><td>{{ $r->branch?->display_name }}</td><td>{{ $r->orders }}</td><td>৳ {{ number_format($r->sales, 2) }}</td><td class="fw-semibold">৳ {{ number_format($r->commission, 2) }}</td><td>৳ {{ number_format($r->paid_commission, 2) }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No orders in this period.</td></tr>
        @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot><tr class="fw-bold"><td>Total</td><td>{{ $rows->sum('orders') }}</td><td>৳ {{ number_format($rows->sum('sales'), 2) }}</td><td>৳ {{ number_format($rows->sum('commission'), 2) }}</td><td>৳ {{ number_format($rows->sum('paid_commission'), 2) }}</td></tr></tfoot>
        @endif
    </table>
</div>
@endsection
