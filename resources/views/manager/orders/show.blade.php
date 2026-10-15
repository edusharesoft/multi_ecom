@extends('layouts.manager')
@section('title', 'Order '.$order->order_number)

@section('content')
<div class="d-flex justify-content-between flex-wrap gap-2">
    <a href="{{ route('manager.orders.index') }}" class="small text-muted"><i class="fa-solid fa-arrow-left"></i> All orders</a>
    @perm('orders.print')
    <div class="btn-group btn-group-sm">
        <a href="{{ route('manager.orders.print', [$order, 'invoice']) }}" target="_blank" class="btn btn-outline-dark"><i class="fa-solid fa-file-invoice"></i> Invoice</a>
        <a href="{{ route('manager.orders.print', [$order, 'packing']) }}" target="_blank" class="btn btn-outline-dark"><i class="fa-solid fa-box"></i> Packing Slip</a>
        <a href="{{ route('manager.orders.print', [$order, 'label']) }}" target="_blank" class="btn btn-outline-dark"><i class="fa-solid fa-tag"></i> Shipping Label</a>
    </div>
    @endperm
</div>
<div class="row g-4 mt-1">
    <div class="col-xl-8">
        <div class="panel mb-4">
            <div class="panel-head">
                <span>{{ $order->order_number }} <small class="text-muted fw-normal">· {{ $order->created_at->format('d M Y, h:i A') }} · {{ strtoupper($order->source) }}</small></span>
                <span><span class="badge bg-{{ $order->status_color }}">{{ $order->status_label }}</span> <span class="badge bg-{{ $order->payment_color }}">{{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}</span></span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 small">
                    <thead class="table-light"><tr><th>Product</th><th>Price</th><th>Qty</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td><div class="d-flex gap-2 align-items-center"><img src="{{ $item->product?->image_url ?? asset('images/placeholder.svg') }}" class="thumb-sm"><div><strong>{{ $item->product_name }}</strong>@if($item->variant_name)<br><span class="text-muted">{{ $item->variant_name }}</span>@endif @if($item->product?->sku)<br><span class="text-muted">SKU {{ $item->product->sku }}</span>@endif</div></div></td>
                            <td>@money($item->price)</td>
                            <td>{{ $item->quantity }}</td>
                            <td class="text-end">@money($item->total)</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" class="text-end text-muted">Subtotal</td><td class="text-end">@money($order->subtotal)</td></tr>
                        @if ($order->discount > 0)<tr><td colspan="3" class="text-end text-success">Discount {{ $order->coupon_code }}</td><td class="text-end text-success">− @money($order->discount)</td></tr>@endif
                        <tr><td colspan="3" class="text-end text-muted">Shipping</td><td class="text-end">@money($order->shipping)</td></tr>
                        <tr><td colspan="3" class="text-end text-muted">Tax</td><td class="text-end">@money($order->tax)</td></tr>
                        <tr><td colspan="3" class="text-end fw-bold">Total</td><td class="text-end fw-bold text-brand">@money($order->total)</td></tr>
                        @if ($order->wallet_used > 0)<tr><td colspan="3" class="text-end">Paid by wallet</td><td class="text-end">− @money($order->wallet_used)</td></tr>@endif
                        @if ($order->refunded_amount > 0)<tr><td colspan="3" class="text-end text-danger">Refunded</td><td class="text-end text-danger">@money($order->refunded_amount)</td></tr>@endif
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="panel mb-4">
            <div class="panel-head">Status History</div>
            <ul class="list-group list-group-flush small">
                @forelse ($order->histories->reverse() as $h)
                    <li class="list-group-item d-flex justify-content-between"><span><span class="badge bg-{{ \App\Models\Order::STATUS_META[$h->status][1] ?? 'secondary' }}">{{ \App\Models\Order::STATUS_META[$h->status][0] ?? $h->status }}</span> {{ $h->note }} <span class="text-muted">— {{ $h->actor_name }}</span></span><span class="text-muted">{{ $h->created_at->format('d M, h:i A') }}</span></li>
                @empty
                    <li class="list-group-item text-muted">No history.</li>
                @endforelse
            </ul>
        </div>

        <div class="panel">
            <div class="panel-head">Payment History</div>
            <div class="table-responsive"><table class="table mb-0 small">
                <thead class="table-light"><tr><th>Date</th><th>Method</th><th>Reference</th><th>TrxID</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($order->payments as $p)
                    <tr><td>{{ $p->created_at->format('d M, h:i A') }}</td><td>{{ strtoupper($p->gateway) }}</td><td>{{ $p->reference }}</td><td>{{ $p->transaction_id ?? '—' }}</td><td class="{{ $p->amount < 0 ? 'text-danger' : '' }}">@money($p->amount)</td><td><span class="badge bg-{{ ['success' => 'success', 'failed' => 'danger', 'cancelled' => 'secondary', 'refunded' => 'dark'][$p->status] ?? 'warning' }}">{{ $p->status }}</span></td></tr>
                @empty
                    <tr><td colspan="6" class="text-muted text-center py-3">No payment attempts.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-head">Customer @if($order->customer)<a href="{{ route('manager.customers.show', $order->customer) }}" class="small">Profile</a>@endif</div>
            <div class="panel-body small">
                <strong>{{ $order->customer_name }}</strong> @if($order->customer)<span class="badge bg-success">Registered</span>@else<span class="badge bg-light text-dark">Guest</span>@endif<br>
                <i class="fa-solid fa-phone text-muted"></i> <a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a><br>
                @if ($order->customer_email)<i class="fa-solid fa-envelope text-muted"></i> {{ $order->customer_email }}<br>@endif
                <div class="mt-2"><span class="text-muted">Delivery:</span> {{ $order->shipping_address }}</div>
                @if ($order->billing_address && $order->billing_address !== $order->shipping_address)<div><span class="text-muted">Billing:</span> {{ $order->billing_address }}</div>@endif
                @if ($order->note)<div class="alert alert-light mt-2 mb-0 p-2"><strong>Note:</strong> {{ $order->note }}</div>@endif
            </div>
        </div>

        @include('manager.courier._panel')

        @perm('orders.edit')
        <form method="POST" action="{{ route('manager.orders.status', $order) }}" class="panel mb-3">
            @csrf @method('PATCH')
            <div class="panel-head">Order Status</div>
            <div class="panel-body">
                <select name="status" class="form-select form-select-sm mb-2">
                    @foreach (\App\Models\Order::STATUS_META as $k => [$label])<option value="{{ $k }}" @selected($order->status === $k)>{{ $label }}</option>@endforeach
                </select>
                <input name="note" class="form-control form-control-sm mb-2" placeholder="Note (optional) e.g. courier tracking no.">
                <button class="btn btn-sm btn-brand">Update</button>
                <p class="small text-muted mt-2 mb-0">Cancelled/Failed করলে স্টক ফেরত যায়। Delivered/Completed হলে কাস্টমার লয়্যালটি পয়েন্ট পায়।</p>
            </div>
        </form>
        @endperm

        <div class="panel mb-3">
            <div class="panel-head">Payment — {{ $order->method_label }}</div>
            <div class="panel-body small">
                @if ($order->sender_number)<div>Sender: <strong>{{ $order->sender_number }}</strong></div>@endif
                @if ($order->transaction_id)<div>TrxID: <strong>{{ $order->transaction_id }}</strong></div>@endif
                @if ($order->paid_at)<div class="text-success">Paid at {{ $order->paid_at->format('d M Y, h:i A') }}</div>@endif
                @perm('orders.approve')
                @if ($order->payment_status === 'pending_verification')
                    <p class="mt-2 mb-2">আপনার {{ $order->method_label }} অ্যাকাউন্টে TrxID মিলিয়ে দেখুন।</p>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('manager.orders.payment', $order) }}">@csrf @method('PATCH')<button name="action" value="approve" class="btn btn-sm btn-success">Approve Payment</button></form>
                        <form method="POST" action="{{ route('manager.orders.payment', $order) }}">@csrf @method('PATCH')<button name="action" value="reject" class="btn btn-sm btn-outline-danger">Reject</button></form>
                    </div>
                @elseif ($order->payment_method === 'cod' && ! $order->isPaid())
                    <form method="POST" action="{{ route('manager.orders.payment', $order) }}" class="mt-2">@csrf @method('PATCH')<button name="action" value="collected" class="btn btn-sm btn-success">Mark Cash Collected</button></form>
                @endif
                @if (($order->isPaid() || $order->payment_status === 'partially_refunded') && $order->refunded_amount < $order->total)
                    <hr>
                    <form method="POST" action="{{ route('manager.orders.refund', $order) }}" class="row g-1" onsubmit="return confirm('Record refund?')">@csrf
                        <div class="col-5"><input type="number" step="0.01" min="1" max="{{ $order->total - $order->refunded_amount }}" name="amount" value="{{ $order->total - $order->refunded_amount }}" class="form-control form-control-sm"></div>
                        <div class="col-7"><select name="method" class="form-select form-select-sm">@if($order->customer_id)<option value="wallet">To wallet</option>@endif<option value="original">Original method</option><option value="cash">Cash</option><option value="bkash">bKash</option><option value="nagad">Nagad</option></select></div>
                        <div class="col-12"><input name="note" class="form-control form-control-sm" placeholder="Reason"></div>
                        <div class="col-12"><button class="btn btn-sm btn-outline-danger w-100">Refund / Partial Refund</button></div>
                    </form>
                @endif
                @endperm
            </div>
        </div>

        @foreach ($order->returns as $ret)
            <a href="{{ route('manager.returns.show', $ret) }}" class="alert alert-warning d-block small">Return {{ $ret->number }} — {{ $ret->status_label }}</a>
        @endforeach
    </div>
</div>
@endsection
