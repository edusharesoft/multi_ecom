@extends('layouts.store')
@section('title', 'Shopping Cart — '.$store->display_name)

@section('content')
<section class="section pt-2">
    <div class="container" style="max-width:1000px">
        <h1 class="h4 fw-bold mb-0"><i class="fa-solid fa-cart-shopping text-brand"></i> Shopping Cart</h1>
        <p class="small text-muted">You have {{ $items->sum('quantity') }} items in your cart</p>

        @if ($items->isEmpty())
            <div class="pd-box text-center py-5">
                <i class="fa-solid fa-cart-arrow-down fa-3x text-muted mb-3"></i>
                <p>আপনার কার্ট খালি।</p>
                <a href="{{ route('store.products', $store) }}" class="btn btn-brand">Continue Shopping</a>
            </div>
        @else
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="pd-box">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <strong>Cart Items</strong>
                        <form method="POST" action="{{ route('store.cart.clear', $store) }}">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger py-0"><i class="fa-solid fa-trash"></i> Clear Cart</button>
                        </form>
                    </div>
                    @foreach ($items as $item)
                        <div class="d-flex gap-3 py-3 border-bottom">
                            <a href="{{ $item->product->url }}"><img src="{{ $item->product->image_url }}" class="thumb-sm" style="width:70px;height:86px" alt=""></a>
                            <div class="flex-fill">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="{{ $item->product->url }}" class="fw-semibold text-dark">{{ $item->product->name }}</a>
                                        @if ($item->variant)<div class="small text-muted">{{ $item->variant->name }}</div>@endif
                                    </div>
                                    <div class="text-brand fw-bold">@money($item->price)</div>
                                </div>
                                <div class="d-flex justify-content-end align-items-center gap-3 mt-2 flex-wrap">
                                    <form method="POST" action="{{ route('store.cart.update', $store) }}" class="qty-group">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="key" value="{{ $item->key }}">
                                        <button class="btn btn-danger" name="quantity" value="{{ $item->quantity - 1 }}" formnovalidate onclick="this.form.querySelector('input[name=quantity]').value = this.value">−</button>
                                        <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ max(1, $item->stock) }}" onchange="this.form.submit()">
                                        <button class="btn btn-success" name="quantity" value="{{ min($item->quantity + 1, max(1, $item->stock)) }}">+</button>
                                    </form>
                                    <form method="POST" action="{{ route('store.cart.remove', $store) }}">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="key" value="{{ $item->key }}">
                                        <button class="btn btn-sm btn-outline-danger py-0">Remove</button>
                                    </form>
                                    <strong style="min-width:90px" class="text-end">@money($item->total)</strong>
                                </div>
                                @if ($item->quantity > $item->stock)<div class="small text-danger">স্টকে মাত্র {{ $item->stock }} টি আছে।</div>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-4">
                <div class="pd-box mb-3">
                    @if ($totals['coupon'])
                        <div class="d-flex justify-content-between align-items-center small">
                            <span><i class="fa-solid fa-ticket text-success"></i> <strong>{{ $totals['coupon']->code }}</strong> — {{ $totals['coupon']->label() }}</span>
                            <form method="POST" action="{{ route('store.cart.coupon.remove', $store) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0">Remove</button></form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('store.cart.coupon', $store) }}" class="d-flex gap-2">
                            @csrf
                            <input name="code" class="form-control form-control-sm text-uppercase" placeholder="Coupon code" required>
                            <button class="btn btn-sm btn-dark text-nowrap">Apply</button>
                        </form>
                    @endif
                </div>
                @include('store._summary', ['totals' => $totals])
                <a href="{{ route('store.checkout', $store) }}" class="btn btn-brand w-100 mt-3"><i class="fa-solid fa-lock"></i> Proceed to Checkout</a>
                <a href="{{ route('store.products', $store) }}" class="btn btn-link w-100 small">Continue Shopping</a>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection
