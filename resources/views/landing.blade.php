@extends('layouts.main')

@section('content')
{{-- Banner slideshow (Super Admin → Home Slider) — images only --}}
@include('partials.banner-slider', ['sid' => 'homeSlider', 'slides' => \App\Support\PlatformSlides::forLanding()])

{{-- Our Branches --}}
<section class="section" id="branches">
    <div class="container">
        <h2 class="section-title center">Our <span class="text-primary">Branches</span></h2>
        <p class="text-center text-muted small mt-2">Visit any of our {{ $branches->count() }} branches across the country for an exceptional shopping experience</p>
        <div class="d-flex justify-content-center gap-2 mb-4 flex-wrap">
            <span class="chip bg-primary"><i class="fa-solid fa-store"></i> {{ $branches->count() }} Branches</span>
            <span class="chip bg-success"><i class="fa-regular fa-clock"></i> All Open Now</span>
            <span class="chip bg-warning text-dark"><i class="fa-solid fa-truck-fast"></i> Home Delivery</span>
        </div>

        <div class="row g-3">
            @forelse ($branches as $b)
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="branch-card">
                        <div class="cover" style="background-image:url('{{ $b->banner_url }}')"><span class="open">Open</span></div>
                        <div class="body">
                            <h6>{{ $b->display_name }}</h6>
                            <div><i class="fa-solid fa-location-dot text-brand"></i> {{ \Illuminate\Support\Str::limit($b->address, 70) }}</div>
                            <div><i class="fa-solid fa-phone text-brand"></i> {{ $b->store_phone ?: $b->mobile }}</div>
                            <div><i class="fa-solid fa-envelope text-brand"></i> {{ $b->email }}</div>
                            <div><i class="fa-solid fa-box text-brand"></i> {{ $b->products_count }} products</div>
                        </div>
                        <div class="actions">
                            <button type="button" class="btn btn-primary js-map" data-bs-toggle="modal" data-bs-target="#mapModal"
                                data-name="{{ $b->display_name }}" data-address="{{ $b->address }}"
                                data-src="{{ \App\Support\MapEmbed::src($b) }}" data-open="{{ \App\Support\MapEmbed::openUrl($b) }}"><i class="fa-solid fa-location-dot"></i> Location</button>
                            <a href="{{ route('store.home', $b) }}" class="btn btn-danger"><i class="fa-solid fa-globe"></i> Website</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted py-5">
                    এখনও কোনো ব্রাঞ্চ নেই। <a href="{{ route('register') }}">প্রথম ব্রাঞ্চটি খুলুন</a>
                </div>
            @endforelse
        </div>
    </div>
</section>

{{-- New Arrivals --}}
<section class="section pt-0" id="arrivals">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <h2 class="section-title">New Arrival</h2>
        </div>
        <div class="row g-3">
            @forelse ($newArrivals as $product)
                <div class="col-6 col-md-4 col-lg-3">@include('partials.product-card', ['product' => $product, 'showBranch' => true])</div>
            @empty
                <div class="col-12 text-muted">No products yet.</div>
            @endforelse
        </div>
    </div>
</section>

{{-- How to Buy --}}
<section class="section pt-0" id="how">
    <div class="container">
        <h2 class="section-title center">How to Buy</h2>
        <p class="text-center text-muted small mt-2 mb-4">Follow these simple steps to place your order easily and securely</p>
        <div class="row g-3">
            @foreach ([
                ['fa-store', 'Choose a Branch', 'Select your nearest branch and visit its website'],
                ['fa-magnifying-glass', 'Browse Products', 'Explore categories, sizes and colors, and find your favorites'],
                ['fa-cart-shopping', 'Add to Cart', 'Select your size, quantity, and add products to your cart'],
                ['fa-credit-card', 'Checkout & Payment', 'Pay with ShatajPay, bKash, Nagad or Cash on Delivery'],
            ] as [$icon, $title, $text])
                <div class="col-6 col-lg-3">
                    <div class="step-card"><div class="ic"><i class="fa-solid {{ $icon }}"></i></div><h6>{{ $title }}</h6><p>{{ $text }}</p></div>
                </div>
            @endforeach
        </div>

        <div class="promo mt-4">
            <h3 class="fw-bold mb-1">{{ \App\Models\Setting::get('promo_title') }}</h3>
            <p class="small opacity-75 mb-3">{{ \App\Models\Setting::get('promo_text') }}</p>
            <a href="#branches" class="btn btn-brand px-4">Shop Now</a>
        </div>
    </div>
</section>

{{-- Customer Reviews --}}
@if ($reviews->isNotEmpty())
<section class="section pt-0">
    <div class="container">
        <h2 class="section-title mb-3">Customer Reviews</h2>
        <div class="row g-3">
            @foreach ($reviews->take(3) as $r)
                <div class="col-md-4">
                    <div class="review-card">
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <div class="avatar">{{ mb_substr($r->name, 0, 1) }}</div>
                            <div><div class="fw-semibold">{{ $r->name }}</div><small class="text-muted">{{ $r->product->branch->display_name }}</small></div>
                        </div>
                        <p>"{{ \Illuminate\Support\Str::limit($r->comment, 160) }}"</p>
                        <div class="stars text-end">@for ($i = 1; $i <= 5; $i++)<i class="fa-{{ $i <= $r->rating ? 'solid' : 'regular' }} fa-star"></i>@endfor</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Newsletter --}}
<section class="newsletter" id="newsletter">
    <div class="container">
        <h4 class="fw-bold">Stay Updated</h4>
        <p class="small text-muted">Subscribe to our newsletter and get 10% off your first order</p>
        <form method="POST" action="{{ route('subscribe') }}">
            @csrf
            <input type="email" name="email" class="form-control" placeholder="Enter your email address" required>
            <button class="btn btn-brand px-4">Subscribe</button>
        </form>
    </div>
</section>

{{-- Location popup (opens on the same page) --}}
<div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <div>
                    <h6 class="modal-title fw-bold mb-0" id="mapModalTitle"><i class="fa-solid fa-location-dot text-danger"></i> <span class="js-map-name"></span></h6>
                    <small class="text-muted js-map-address"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9 bg-light"><iframe class="js-map-frame border-0" title="Map" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>
            </div>
            <div class="modal-footer py-2">
                <a href="#" class="btn btn-sm btn-outline-primary js-map-open" target="_blank" rel="noopener"><i class="fa-solid fa-diamond-turn-right"></i> {{ __('Directions in Google Maps') }}</a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const modal = document.getElementById('mapModal');
    if (!modal) return;
    const frame = modal.querySelector('.js-map-frame');
    modal.addEventListener('show.bs.modal', e => {
        const b = e.relatedTarget;
        if (!b) return;
        modal.querySelector('.js-map-name').textContent = b.dataset.name || '';
        modal.querySelector('.js-map-address').textContent = b.dataset.address || '';
        modal.querySelector('.js-map-open').href = b.dataset.open || '#';
        const src = b.dataset.src || '';
        if (/^https:\/\/(maps|www)\.google\.[a-z.]+\//i.test(src)) frame.src = src;
    });
    modal.addEventListener('hidden.bs.modal', () => { frame.src = 'about:blank'; });
})();
</script>
@endpush
