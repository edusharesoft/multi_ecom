@extends('layouts.manager')
@section('title', 'POS — Return / Exchange')

@section('content')
@include('manager.pos._nav')
<form class="panel mb-3" method="GET"><div class="panel-body d-flex gap-2">
    <input name="invoice" value="{{ request('invoice') }}" class="form-control" placeholder="Invoice / Order number (scan receipt barcode)" autofocus>
    <button class="btn btn-dark">Find</button>
</div></form>

@if ($order)
<form method="POST" action="{{ route('manager.pos.returns.process', $order) }}" class="panel" onsubmit="return confirm('ফেরত প্রসেস করবেন?')">@csrf
    <div class="panel-head"><span>{{ $order->order_number }} · {{ $order->created_at->format('d M Y h:i A') }} · {{ $order->customer_name }}</span>
        <span>Total @money($order->total) @if($order->refunded_amount > 0)· <span class="text-danger">refunded @money($order->refunded_amount)</span>@endif</span></div>
    <div class="table-responsive"><table class="table table-sm mb-0 small align-middle">
        <thead class="table-light"><tr><th>Item</th><th class="text-end">Price</th><th class="text-end">Sold</th><th class="text-end">Returned</th><th style="width:110px">Return qty</th></tr></thead>
        @foreach ($order->items as $i)
            @php $left = $i->quantity - (int) $i->returned_qty; @endphp
            <tr><td>{{ $i->product_name }} {{ $i->variant_name }}</td><td class="text-end">{{ number_format($i->price, 2) }}</td><td class="text-end">{{ $i->quantity }}</td><td class="text-end">{{ $i->returned_qty ?: '' }}</td>
                <td>@if ($left > 0)<input type="number" min="0" max="{{ $left }}" name="qty[{{ $i->id }}]" value="0" class="form-control form-control-sm">@else<span class="text-muted">—</span>@endif</td></tr>
        @endforeach
    </table></div>
    <div class="panel-body small">
        @if (in_array($order->status, ['cancelled', 'failed']) || (float) $order->refunded_amount >= (float) $order->total)
            <div class="alert alert-warning mb-0">এই বিলে আর ফেরত নেওয়া যাবে না।</div>
        @else
        <p class="text-muted mb-2">রিফান্ড = ফেরত পণ্যের মূল্য × (বিলের মোট / সাবটোটাল) — অর্থাৎ ডিসকাউন্ট/ট্যাক্স আনুপাতিক হারে সমন্বয় হবে। স্টক {{ $order->warehouse?->name ?? 'default warehouse' }}-এ যোগ হবে।</p>
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label mb-0">Refund method</label>
                <select name="method" class="form-select form-select-sm">
                    <option value="cash">Cash (drawer)</option>
                    @if ($order->customer_id)<option value="wallet">Customer wallet</option>@endif
                    <option value="card">Card</option><option value="bkash">bKash</option><option value="nagad">Nagad</option>
                </select></div>
            <div class="col-md-4"><label class="form-label mb-0">Reason</label><input name="note" class="form-control form-control-sm"></div>
            <div class="col-md-2"><div class="form-check"><input type="checkbox" name="exchange" value="1" class="form-check-input" id="exch"><label for="exch" class="form-check-label">Exchange (নতুন বিল)</label></div></div>
            <div class="col-md-3"><button class="btn btn-danger btn-sm w-100"><i class="fa-solid fa-rotate-left"></i> Process Return</button></div>
        </div>
        @endif
    </div>
</form>
@endif
@endsection
