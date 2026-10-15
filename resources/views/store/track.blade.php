@extends('layouts.store')
@section('title', 'Track Order — '.$store->display_name)

@section('content')
<section class="section pt-2">
    <div class="container" style="max-width:720px">
        <h1 class="h4 fw-bold mb-3"><i class="fa-solid fa-location-crosshairs text-brand"></i> Track Your Order</h1>
        <form method="GET" class="pd-box row g-2 mb-4 mx-0">
            <div class="col-md-5"><input name="order_number" value="{{ request('order_number') }}" class="form-control" placeholder="Order number (ORD-...)" required></div>
            <div class="col-md-4"><input name="phone" value="{{ request('phone') }}" class="form-control" placeholder="Mobile number" required></div>
            <div class="col-md-3"><button class="btn btn-brand w-100">Track</button></div>
        </form>
        @if ($order)
            @include('store._order-detail')
            @if ($order->needsOnlinePayment())
                <a href="{{ route('store.payment', [$store, $order]) }}" class="btn btn-brand mt-3">Complete Payment</a>
            @endif
        @endif
    </div>
</section>
@endsection
