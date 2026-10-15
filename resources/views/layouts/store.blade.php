<!doctype html>
<html lang="bn">
<head>
    @include('partials.head')
    <title>@yield('title', $storeSeo['title'] ?: $store->display_name)</title>
    <meta name="description" content="@yield('meta_description', $storeSeo['description'] ?: \Illuminate\Support\Str::limit($store->about ?: $store->display_name.' — online shop', 155))">
    @if ($storeSeo['keywords'])<meta name="keywords" content="{{ $storeSeo['keywords'] }}">@endif
    @if ($storeSeo['noindex'])<meta name="robots" content="noindex, nofollow">@endif
    @if ($storeSeo['google_verification'])<meta name="google-site-verification" content="{{ $storeSeo['google_verification'] }}">@endif
    @if ($storeSeo['bing_verification'])<meta name="msvalidate.01" content="{{ $storeSeo['bing_verification'] }}">@endif
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:site_name" content="{{ $store->display_name }}">
    <meta property="og:title" content="@yield('title', $store->display_name)">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', $store->banner_url)">
    @if ($store->favicon_url)<link rel="icon" href="{{ $store->favicon_url }}">@endif
    @include('store._theme-style')
    @include('store._tracking-head')
    @php
        $pwaScope = \App\Http\Controllers\PwaController::storeScope($store);
        $pwaIcon = $store->logo ? route('store.pwa.icon', [$store, 'size' => 192]) : asset('images/pwa/store-192.png');
    @endphp
    @include('pwa._head', ['manifest' => route('store.pwa.manifest', $store), 'color' => $theme['primary'], 'appName' => $store->display_name, 'icon' => $pwaIcon])
    @stack('head')
</head>
<body>
@if ($theme['announcement_text'])
    <div class="text-center small py-2 px-3" style="background:{{ $theme['announcement_bg'] }};color:#fff">
        @if ($theme['announcement_link'])<a href="{{ $theme['announcement_link'] }}" class="text-white text-decoration-underline">{{ $theme['announcement_text'] }}</a>@else{{ $theme['announcement_text'] }}@endif
    </div>
@endif
<div class="topbar">
    <div class="container d-flex justify-content-between flex-wrap gap-1">
        <div><i class="fa-solid fa-phone"></i> {{ $store->store_phone ?: $store->mobile }} <span class="mx-2">|</span> <i class="fa-solid fa-envelope"></i> {{ $store->email }}</div>
        <div class="d-none d-md-block">{{ $theme['tagline'] }}</div>
        <div>@include('partials.lang-switch', ['class' => 'text-white']) <span class="mx-2">|</span> <a href="{{ route('store.track', $store) }}"><i class="fa-solid fa-truck"></i> {{ __('Track Order') }}</a> <span class="mx-2">|</span> <a href="{{ route('login', ['branch' => $store->slug]) }}" rel="nofollow"><i class="fa-solid fa-user-tie"></i> {{ __('Seller Login') }}</a> @if ($theme['show_all_branches'] && ! app()->bound('custom_domain_branch'))<span class="mx-2">|</span> <a href="{{ route('home') }}"><i class="fa-solid fa-store"></i> {{ __('All Branches') }}</a>@endif</div>
    </div>
</div>
<header class="site-header">
    <div class="container d-flex align-items-center gap-3 py-2 flex-wrap">
        <a href="{{ route('store.home', $store) }}" class="logo">
            @if ($store->logo_url)<img src="{{ $store->logo_url }}" alt="{{ $store->display_name }}">@else<i class="fa-solid fa-shop"></i> {{ $store->display_name }}@endif
        </a>
        <form class="header-search mx-auto" action="{{ route('store.products', $store) }}" method="GET">
            <select name="category" class="form-select form-select-sm">
                <option value="">{{ __('Category') }}</option>
                @foreach ($menuCategories as $c)
                    <option value="{{ $c->slug }}" @selected(request('category') === $c->slug)>{{ $c->name }}</option>
                    @foreach ($c->children as $sc)<option value="{{ $sc->slug }}" @selected(request('category') === $sc->slug)>&nbsp;&nbsp;— {{ $sc->name }}</option>@endforeach
                @endforeach
            </select>
            <input class="form-control form-control-sm" name="q" value="{{ request('q') }}" placeholder="{{ __('Search for products, code or barcode...') }}">
            <button class="btn btn-brand btn-sm px-3"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
        <div class="d-flex align-items-center gap-3">
            @if ($storeCustomer)
                <a href="{{ route('store.account', $store) }}" class="header-icon"><i class="fa-regular fa-user"></i><span>{{ \Illuminate\Support\Str::limit($storeCustomer->name, 12) }}<small>{{ __('My Account') }}</small></span></a>
            @else
                <a href="{{ route('store.account.login', $store) }}" class="btn btn-sm btn-brand fw-semibold px-3"><i class="fa-solid fa-right-to-bracket"></i> {{ __('Login') }}</a>
                <a href="{{ route('store.account.register', $store) }}" class="btn btn-sm btn-outline-brand fw-semibold px-3 d-none d-sm-inline-block"><i class="fa-regular fa-user"></i> {{ __('Register') }}</a>
            @endif
            <a href="{{ route('store.wishlist', $store) }}" class="header-icon"><span class="count">{{ $wishlistCount }}</span><span>{{ __('Wishlist') }}<small>{{ __('Edit your wishlist') }}</small></span></a>
            <a href="#miniCart" data-bs-toggle="offcanvas" class="cart-pill">
                <span class="small">{{ $cartCount }} {{ __('item(s)') }} - @money($cartTotal)</span>
                <span class="bag"><i class="fa-solid fa-bag-shopping"></i><span>{{ $cartCount }}</span></span>
            </a>
        </div>
    </div>
    <div class="main-nav">
        <div class="container d-flex flex-wrap">
            <a href="{{ route('store.home', $store) }}" @class(['active' => request()->routeIs('store.home')])>{{ __('Home') }}</a>
            @foreach ($menuCategories->take(6) as $c)
                @if ($c->children->isNotEmpty())
                    <div class="dropdown d-inline-block">
                        <a href="{{ route('store.products', [$store, 'category' => $c->slug]) }}" class="dropdown-toggle {{ request('category') === $c->slug ? 'active' : '' }}" data-bs-toggle="dropdown">{{ $c->name }}</a>
                        <ul class="dropdown-menu small">
                            <li><a class="dropdown-item" href="{{ route('store.products', [$store, 'category' => $c->slug]) }}">{{ __('All') }} {{ $c->name }}</a></li>
                            @foreach ($c->children as $sc)<li><a class="dropdown-item" href="{{ route('store.products', [$store, 'category' => $sc->slug]) }}">{{ $sc->name }}</a></li>@endforeach
                        </ul>
                    </div>
                @else
                    <a href="{{ route('store.products', [$store, 'category' => $c->slug]) }}" @class(['active' => request('category') === $c->slug])>{{ $c->name }}</a>
                @endif
            @endforeach
            <a href="{{ route('store.products', $store) }}" @class(['active' => request()->routeIs('store.products') && ! request()->hasAny(['category', 'sale', 'flash'])])>{{ __('New Arrivals') }}</a>
            <a href="{{ route('store.products', [$store, 'sale' => 1]) }}" @class(['active' => request()->boolean('sale')])>{{ __('Offer') }}</a>
            @foreach ($footerPages->where('show_in_header', true) as $pg)<a href="{{ route('store.page', [$store, $pg->slug]) }}" @class(['active' => request()->is('*/page/'.$pg->slug)])>{{ $pg->title }}</a>@endforeach
            @if ($hasBlog)<a href="{{ route('store.blog', $store) }}" @class(['active' => request()->routeIs('store.blog*')])>{{ __('Blog') }}</a>@endif
            @foreach ($storeMenu as $m)<a href="{{ $m['url'] }}" @if(\Illuminate\Support\Str::startsWith($m['url'], 'http') && ! \Illuminate\Support\Str::startsWith($m['url'], url('/'))) target="_blank" rel="noopener" @endif>{{ $m['label'] }}</a>@endforeach
            <a href="#contact">{{ __('Contact') }}</a>
        </div>
    </div>
</header>

{{-- Mini cart --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="miniCart">
    <div class="offcanvas-header border-bottom"><h6 class="offcanvas-title fw-bold"><i class="fa-solid fa-bag-shopping text-brand"></i> {{ __('Your Cart') }} ({{ $cartCount }})</h6><button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button></div>
    <div class="offcanvas-body small">
        @forelse ($miniCart as $item)
            <div class="d-flex gap-2 mb-3 pb-3 border-bottom">
                <img src="{{ $item->product->image_url }}" class="thumb-sm" alt="">
                <div class="flex-fill">
                    <div class="fw-semibold">{{ $item->product->name }}</div>
                    @if ($item->variant)<div class="text-muted">{{ $item->variant->name }}</div>@endif
                    <div>{{ $item->quantity }} × @money($item->price)</div>
                </div>
                <form method="POST" action="{{ route('store.cart.remove', $store) }}">@csrf @method('DELETE')<input type="hidden" name="key" value="{{ $item->key }}"><button class="btn btn-sm btn-link text-danger p-0"><i class="fa-solid fa-xmark"></i></button></form>
            </div>
        @empty
            <p class="text-muted text-center py-4">কার্ট খালি।</p>
        @endforelse
        @if ($miniCart->isNotEmpty())
            <div class="d-flex justify-content-between fw-bold mb-3"><span>{{ __('Subtotal') }}</span><span>@money($miniCart->sum('total'))</span></div>
            <a href="{{ route('store.cart', $store) }}" class="btn btn-outline-brand w-100 mb-2">{{ __('View Cart') }}</a>
            <a href="{{ route('store.checkout', $store) }}" class="btn btn-brand w-100">{{ __('Checkout') }}</a>
        @endif
    </div>
</div>

<main class="container mt-3">@include('partials.flash')</main>
@yield('content')

@if ($theme['show_newsletter'])
<section class="newsletter mt-4">
    <div class="container">
        <h5 class="fw-bold">{{ __('Stay Updated') }}</h5>
        <p class="small text-muted">{{ __("Subscribe to get news of new collections and offers") }}</p>
        <form method="POST" action="{{ route('store.subscribe', $store) }}">@csrf
            <input type="email" name="email" class="form-control" placeholder="{{ __('Enter your email address') }}" required>
            <button class="btn btn-brand px-4">{{ __('Subscribe') }}</button>
        </form>
    </div>
</section>
@endif
@include('partials.site-footer', ['f' => \App\Support\SiteFooter::store($store, $theme, $footerPages)])
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Countdown for flash sales: <span data-countdown="ISO-DATE"></span>
document.querySelectorAll('[data-countdown]').forEach(el => {
    const end = new Date(el.dataset.countdown).getTime();
    const tick = () => {
        let s = Math.max(0, Math.floor((end - Date.now()) / 1000));
        const d = Math.floor(s / 86400); s %= 86400;
        const h = Math.floor(s / 3600); s %= 3600;
        const m = Math.floor(s / 60); s %= 60;
        el.textContent = (d ? d + 'd ' : '') + [h, m, s].map(v => String(v).padStart(2, '0')).join(':');
    };
    tick(); setInterval(tick, 1000);
});
</script>
@include('store._tracking-body')
@include('pwa._register', ['sw' => route('store.pwa.sw', $store), 'scope' => $pwaScope, 'cachePrefix' => 'mb-s'.$store->id.'-pages-'])
@stack('scripts')
</body>
</html>
