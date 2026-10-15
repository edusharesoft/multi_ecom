@extends('layouts.store')
@section('title', 'Order '.$order->order_number)

@section('content')
<section class="section pt-2">
    <div class="container" style="max-width:720px">
        <div class="text-center mb-4">
            <i class="fa-solid fa-circle-check text-success" style="font-size:60px"></i>
            <h1 class="h4 fw-bold mt-3">ধন্যবাদ! আপনার অর্ডারটি গ্রহণ করা হয়েছে</h1>
            <p class="text-muted small">অর্ডার নম্বর <strong>{{ $order->order_number }}</strong> সংরক্ষণ করুন — অর্ডার ট্র্যাক করতে লাগবে।</p>
            @if ($order->payment_status === 'pending_verification')
                <div class="alert alert-warning small">আপনার {{ $order->method_label }} পেমেন্ট (TrxID: {{ $order->transaction_id }}) ভেরিফিকেশনের অপেক্ষায় আছে।</div>
            @elseif ($order->needsOnlinePayment())
                <a href="{{ route('store.payment', [$store, $order]) }}" class="btn btn-brand">Complete Payment</a>
            @endif
        </div>
        @include('store._order-detail')
        <div class="text-center mt-4"><a href="{{ route('store.products', $store) }}" class="btn btn-outline-brand">Continue Shopping</a></div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const key = 'mb_purchase_' + @json($order->order_number);
    try { if (localStorage.getItem(key)) return; localStorage.setItem(key, 1); } catch (e) {}
    window.mbTrack && mbTrack('Purchase', {value: {{ (float) $order->total }}, order_id: @json($order->order_number), content_ids: @json($order->items->pluck('product_id')->filter()->values()), content_type: 'product', num_items: {{ (int) $order->items->sum('quantity') }}});
})();
</script>
@endpush
