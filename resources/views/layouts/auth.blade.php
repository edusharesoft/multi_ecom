{{--
    Phase 12 — login / registration / OTP layout.
    Child views set:  @section('title')  @section('heading')  @section('subheading')  @section('content')
    optional:         @section('auth_kind', 'register')   → background + video of the registration page
                      @section('auth_size', 'wide')        → wider card (long forms)
                      @section('auth_video', 'off')        → no video on this page (OTP, 2FA …)
                      @section('auth_brand', 'platform')   → always the platform logo
--}}
@php
    $kind = trim($__env->yieldContent('auth_kind')) === 'register' ? 'register' : 'login';
    $size = trim($__env->yieldContent('auth_size'));
    // 'platform' pages (Super Admin login, new store registration) never show a branch logo
    $authBranch = $store ?? (trim($__env->yieldContent('auth_brand')) === 'platform' ? null : \App\Support\AuthTheme::branch());
    $logo = \App\Support\AuthTheme::logo($authBranch);
    $brandName = \App\Support\AuthTheme::name($authBranch);
    $bg = \App\Support\AuthTheme::background($kind);
    $video = trim($__env->yieldContent('auth_video')) === 'off' ? null : \App\Support\AuthTheme::video($kind);
    $footer = \App\Models\Setting::get('footer_text') ?: 'Developed By : Shataj Soft';
    [$fPre, $fName] = str_contains($footer, ':') ? array_map('trim', explode(':', $footer, 2)) : (preg_match('/^(.*\bby)\s+(.+)$/i', $footer, $m) ? [$m[1], $m[2]] : ['', $footer]);
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.head')
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/auth.css') }}?v=1" rel="stylesheet">
    @if ($authBranch?->favicon_url)<link rel="icon" href="{{ $authBranch->favicon_url }}">@endif
    <title>@yield('title') — {{ $brandName }}</title>
</head>
<body class="auth-ui">
<div class="au-bg" @if ($bg) style="--au-img:url('{{ $bg }}')" @endif></div>
<div class="au-top">
    @include('partials.lang-switch', ['class' => 'text-white'])
    <a href="{{ isset($store) ? route('store.home', $store) : route('home') }}"><i class="fa-solid fa-house"></i> {{ __('Home') }}</a>
</div>

<main class="au-wrap">
    <div @class(['au-card', 'wide' => $size === 'wide' && $video, 'solo' => ! $video && $size !== 'wide'])>
        <div class="au-head">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $brandName }}" class="au-logo">
            @else
                <div class="au-name"><i class="fa-solid fa-store" style="color:#0ea5e9;-webkit-text-fill-color:#0ea5e9"></i> {{ $brandName }}</div>
            @endif
            <div class="au-title">@yield('heading')</div>
            @hasSection('subheading')<div class="au-sub">@yield('subheading')</div>@endif
        </div>

        @include('partials.flash')

        @if ($video)
            <div class="row g-3 align-items-stretch">
                <div class="col-md-6 order-2 order-md-1">
                    <button type="button" class="au-media" data-yt="{{ $video }}" aria-label="Play video">
                        <img src="https://i.ytimg.com/vi/{{ $video }}/hqdefault.jpg" alt="" loading="lazy">
                        <span class="play"></span>
                    </button>
                </div>
                <div class="col-md-6 order-1 order-md-2 au-form">@yield('content')</div>
            </div>
        @else
            <div class="au-form">@yield('content')</div>
        @endif
    </div>
    <div class="au-foot">{{ $fPre }}{{ $fPre !== '' ? ' : ' : '' }}<b>{{ $fName }}</b></div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    // show / hide password
    document.querySelectorAll('.au-field .eye').forEach(b => b.addEventListener('click', () => {
        const i = b.parentElement.querySelector('input');
        const show = i.type === 'password';
        i.type = show ? 'text' : 'password';
        b.innerHTML = show ? '<i class="fa-regular fa-eye"></i>' : '<i class="fa-regular fa-eye-slash"></i>';
    }));
    // YouTube: the player is loaded only when clicked (fast page, no tracking before that)
    document.querySelectorAll('.au-media[data-yt]').forEach(b => b.addEventListener('click', () => {
        const id = b.dataset.yt;
        if (!/^[A-Za-z0-9_-]{11}$/.test(id)) return;
        b.innerHTML = '<iframe src="https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        b.style.cursor = 'default';
    }, {once: true}));
})();
</script>
@stack('scripts')
</body>
</html>
