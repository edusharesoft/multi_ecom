@if ($flashSale->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded text-white flex-wrap gap-2" style="background:linear-gradient(90deg,#f59e0b,#e41e26)">
            <h2 class="h4 fw-bold m-0"><i class="fa-solid fa-bolt"></i> {{ $sec['title'] ?: __('Flash Sale') }}</h2>
            <div>{{ __('Ends in') }}: <strong class="fs-5" data-countdown="{{ $flashSale->first()->sale_ends_at->toIso8601String() }}"></strong></div>
            <a href="{{ route('store.products', [$store, 'flash' => 1]) }}" class="btn btn-light btn-sm">{{ __('View All') }}</a>
        </div>
        <div class="row g-3">
            @foreach ($flashSale as $product)
                <div class="col-6 col-md-4 col-lg-3">@include('partials.product-card')</div>
            @endforeach
        </div>
    </div>
</section>
@endif
