@php
    $cancelled = in_array($order->status, ['cancelled', 'failed', 'returned', 'refunded'], true);
    $reached = array_search($order->status === 'completed' ? 'delivered' : ($order->status === 'ready_to_ship' ? 'packed' : ($order->status === 'pending' ? 'new' : $order->status)), \App\Models\Order::TIMELINE, true);
@endphp
<div class="pd-box">
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
        <div><strong>{{ $order->order_number }}</strong><div class="small text-muted">{{ $order->created_at->format('d M Y, h:i A') }}</div></div>
        <div>
            <span class="badge bg-{{ $order->status_color }}">{{ $order->status_label }}</span>
            <span class="badge bg-{{ $order->payment_color }}">{{ $order->method_label }} · {{ str_replace('_', ' ', ucfirst($order->payment_status)) }}</span>
        </div>
    </div>
    @unless ($cancelled)
        <div class="d-flex justify-content-between small text-center mb-3 flex-wrap gap-1">
            @foreach (\App\Models\Order::TIMELINE as $i => $st)
                <div class="flex-fill">
                    <div class="mx-auto rounded-circle d-flex align-items-center justify-content-center {{ $reached !== false && $i <= $reached ? 'bg-success text-white' : 'bg-light border' }}" style="width:26px;height:26px;font-size:11px">{!! $reached !== false && $i <= $reached ? '<i class="fa-solid fa-check"></i>' : $i + 1 !!}</div>
                    <div class="mt-1" style="font-size:10px">{{ \App\Models\Order::STATUS_META[$st][0] }}</div>
                </div>
            @endforeach
        </div>
    @endunless
    @php $ship = $order->shipment; @endphp
    @if ($ship && $ship->status_group !== 'cancelled')
        <div class="alert alert-info small py-2"><i class="fa-solid fa-truck-fast"></i> {{ $ship->courierLabel() }} — Tracking: <strong>{{ $ship->tracking_code ?: $ship->consignment_id }}</strong>
            @if ($u = $ship->publicTrackingUrl())<a href="{{ $u }}" target="_blank" rel="noopener" class="ms-1">Live tracking →</a>@endif
            <span class="d-block text-muted">{{ \App\Models\Shipment::GROUPS[$ship->status_group][0] }} · {{ $ship->status }}</span></div>
    @endif
    @foreach ($order->items as $item)
        <div class="d-flex justify-content-between small py-2 border-bottom">
            <span>{{ $item->product_name }} @if($item->variant_name)<span class="text-muted">({{ $item->variant_name }})</span>@endif × {{ $item->quantity }}</span>
            <span>@money($item->total)</span>
        </div>
    @endforeach
    <div class="summary-row mt-2"><span class="text-muted">Subtotal</span><span>@money($order->subtotal)</span></div>
    @if ($order->discount > 0)<div class="summary-row text-success"><span>Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</span><span>− @money($order->discount)</span></div>@endif
    <div class="summary-row"><span class="text-muted">Shipping</span><span>@money($order->shipping)</span></div>
    @if ($order->tax > 0)<div class="summary-row"><span class="text-muted">Tax</span><span>@money($order->tax)</span></div>@endif
    <div class="summary-row total"><span>Total</span><span>@money($order->total)</span></div>
    @if ($order->wallet_used > 0)<div class="summary-row text-success"><span>Paid from wallet</span><span>− @money($order->wallet_used)</span></div>@endif
    @if ($order->refunded_amount > 0)<div class="summary-row text-danger"><span>Refunded</span><span>@money($order->refunded_amount)</span></div>@endif
    <div class="small text-muted mt-2"><i class="fa-solid fa-location-dot"></i> {{ $order->customer_name }}, {{ $order->customer_phone }} — {{ $order->shipping_address }}</div>
    @if ($order->points_earned > 0)<div class="small text-success mt-1"><i class="fa-solid fa-coins"></i> এই অর্ডারে {{ $order->points_earned }} লয়্যালটি পয়েন্ট {{ $order->points_credited ? 'যোগ হয়েছে' : '(ডেলিভারির পর যোগ হবে)' }}</div>@endif
</div>
