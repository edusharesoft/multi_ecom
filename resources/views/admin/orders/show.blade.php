@extends('layouts.admin')
@section('title', 'Order '.$order->order_number)

@section('content')
<a href="{{ route('admin.orders.index') }}" class="small text-muted"><i class="fa-solid fa-arrow-left"></i> All orders</a>
<div class="row g-4 mt-1">
    <div class="col-xl-8">
        <div class="panel mb-4">
            <div class="panel-head">
                <span>{{ $order->order_number }} <small class="text-muted fw-normal">· <a href="{{ route('admin.branches.show', $order->branch) }}">{{ $order->branch->display_name }}</a> · {{ $order->created_at->format('d M Y, h:i A') }}</small></span>
                <span><span class="badge bg-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span> <span class="badge bg-{{ $order->payment_color }}">{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</span></span>
            </div>
            <table class="table mb-0 small">
                <thead class="table-light"><tr><th>Product</th><th>Price</th><th>Qty</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                @foreach ($order->items as $item)
                    <tr><td>{{ $item->product_name }} @if($item->variant_name)<span class="text-muted">({{ $item->variant_name }})</span>@endif</td><td>@money($item->price)</td><td>{{ $item->quantity }}</td><td class="text-end">@money($item->total)</td></tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr><td colspan="3" class="text-end text-muted">Subtotal / Shipping / Tax</td><td class="text-end">@money($order->subtotal) / @money($order->shipping) / @money($order->tax)</td></tr>
                    <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold text-brand">@money($order->total)</td></tr>
                </tfoot>
            </table>
        </div>
        <div class="panel">
            <div class="panel-head">Payment History</div>
            <table class="table mb-0 small">
                <thead class="table-light"><tr><th>Date</th><th>Gateway</th><th>Reference</th><th>TrxID</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($order->payments as $p)
                    <tr><td>{{ $p->created_at->format('d M, h:i A') }}</td><td>{{ strtoupper($p->gateway) }}</td><td>{{ $p->reference }}</td><td>{{ $p->transaction_id ?? '—' }}</td><td>@money($p->amount)</td><td>{{ $p->status }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-muted text-center py-3">No payment attempts.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel">
            <div class="panel-head">Customer</div>
            <div class="panel-body small">
                <strong>{{ $order->customer_name }}</strong><br>{{ $order->customer_phone }}<br>{{ $order->customer_email }}<br>{{ $order->shipping_address }}
                @if ($order->note)<div class="mt-2"><strong>Note:</strong> {{ $order->note }}</div>@endif
                <hr>
                <div>Method: {{ $order->method_label }}</div>
                @if ($order->transaction_id)<div>TrxID: {{ $order->transaction_id }}</div>@endif
                @if ($order->sender_number)<div>Sender: {{ $order->sender_number }}</div>@endif
                <div class="text-muted mt-2">অর্ডার প্রসেস/স্ট্যাটাস পরিবর্তন সংশ্লিষ্ট ব্রাঞ্চ ম্যানেজার করবেন।
                    <form method="POST" action="{{ route('admin.branches.impersonate', $order->branch) }}" class="d-inline">@csrf<button class="btn btn-link btn-sm p-0">Open as branch →</button></form></div>
            </div>
        </div>
    </div>
</div>
@endsection
