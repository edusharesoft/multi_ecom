<!doctype html>
<html lang="bn">
<head>
    @include('partials.head')
    <title>@yield('title', \App\Models\Setting::siteName().' — Online Shops')</title>
</head>
<body>
<div class="topbar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div><i class="fa-solid fa-phone"></i> {{ \App\Models\Setting::get('site_phone') }} <span class="mx-2">|</span> <i class="fa-solid fa-envelope"></i> {{ \App\Models\Setting::get('site_email', config('mail.from.address')) }}</div>
        <div class="d-flex align-items-center flex-wrap gap-2 topbar-actions">
            @include('partials.lang-switch', ['class' => 'text-white'])
            <span>|</span>
            <a href="{{ route('user.manual') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-light topbar-btn topbar-manual" title="{{ __('User Manual') }}"><i class="fa-solid fa-book-open"></i> <span class="d-none d-sm-inline">{{ __('User Manual') }}</span></a>
            @auth('branch')
                <a href="{{ route('manager.dashboard') }}" class="btn btn-sm btn-outline-light topbar-btn"><i class="fa-solid fa-gauge"></i> Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light topbar-btn"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
                @if (\App\Models\Setting::get('allow_registration'))
                    <a href="{{ route('register') }}" class="btn btn-sm btn-brand topbar-btn"><i class="fa-solid fa-store"></i> Open a Shop</a>
                @endif
            @endauth
        </div>
    </div>
</div>
<header class="site-header">
    <div class="container d-flex align-items-center justify-content-between gap-3 py-2 flex-wrap site-header-main">
        @php
            $siteLogo = \App\Models\Setting::get('site_logo');
        @endphp
        <a href="{{ route('home') }}" class="logo">
            @if ($siteLogo)<img src="{{ asset('storage/'.$siteLogo) }}" alt="{{ \App\Models\Setting::siteName() }}">@else<i class="fa-solid fa-shop"></i> {{ \App\Models\Setting::siteName() }}@endif
        </a>
        <nav class="main-nav d-flex gap-1 align-items-center" aria-label="Main navigation">
            {{-- section links use the full home URL so they also work from Contact / Subscriptions pages --}}
            <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>Home</a>
            <a href="{{ route('home') }}#branches">Shops</a>
            <a href="{{ route('home') }}#arrivals">New Arrivals</a>
            <a href="{{ route('home') }}#how">How to Buy</a>
            <a href="{{ route('contact.us.frontend') }}" @class(['active' => request()->routeIs('contact.us.frontend')])>Contact</a>
            <a href="{{ route('subscription.plan.frontend') }}" @class(['active' => request()->routeIs('subscription.plan.frontend')])>Subscriptions</a>
        </nav>
        <form class="site-search-form" action="{{ route('home') }}#branches" method="GET" style="max-width:320px">
            <div class="input-group">
                <span class="input-group-text site-search-icon" aria-hidden="true"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input class="form-control form-control-sm" name="q" value="{{ request('q') }}" placeholder="Search shops..." aria-label="Search shops">
            </div>
        </form>
    </div>
</header>

<div class="container mt-3">@include('partials.flash')</div>
@yield('content')

@include('partials.site-footer', ['f' => \App\Support\SiteFooter::platform()])
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('partials.image-upload-compressor')
@stack('scripts')
</body>
</html>
