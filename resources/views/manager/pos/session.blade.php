@extends('layouts.manager')
@section('title', 'POS Session #'.$session->id)

@section('content')
@include('manager.pos._nav')
<div class="text-end mb-2 d-print-none"><button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-print"></i> Print Z-Report</button></div>
<div class="row g-4">
    <div class="col-lg-5">@include('manager.pos._summary')</div>
    <div class="col-lg-7">
        <div class="panel"><div class="panel-head">Invoices ({{ $orders->count() }})</div>
            <div class="table-responsive"><table class="table table-sm table-hover mb-0 small">
                <thead class="table-light"><tr><th>Invoice</th><th>Time</th><th>Customer</th><th>Method</th><th class="text-end">Total</th><th></th></tr></thead>
                @forelse ($orders as $o)
                    <tr><td>{{ $o->order_number }} @if($o->status === 'returned')<span class="badge bg-warning text-dark">returned</span>@elseif($o->refunded_amount > 0)<span class="badge bg-light text-dark">partial refund</span>@endif</td>
                        <td>{{ $o->created_at->format('h:i A') }}</td><td>{{ $o->customer_name }}</td>
                        <td>{{ \App\Models\Order::METHOD_LABELS[$o->payment_method] ?? $o->payment_method }}</td>
                        <td class="text-end">{{ number_format($o->total, 2) }}</td>
                        <td class="text-end"><a href="{{ route('manager.pos.receipt', $o) }}" target="_blank" title="Receipt"><i class="fa-solid fa-receipt"></i></a></td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No sales in this session.</td></tr>
                @endforelse
            </table></div></div>
    </div>
</div>
@endsection
