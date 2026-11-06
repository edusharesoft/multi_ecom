<!doctype html>
<html lang="bn">
<head>
    @include('partials.head')
    <title>@yield('title', 'Dashboard') — Super Admin</title>
    <link href="{{ asset('css/panel.css') }}?v=2" rel="stylesheet">
</head>
@php
    $admin = auth('web')->user();
    $siteLogo = \App\Models\Setting::get('site_logo');
    $is = fn (...$r) => request()->routeIs(...$r);
    $pv = \App\Models\Invoice::where('status', 'pending_verification')->count();
    $un = \App\Models\AppNotification::forAdmin()->unread()->count();
    $menu = [
        ['label' => __('Dashboard'), 'icon' => 'fa-solid fa-gauge-high', 'color' => '#6366f1', 'url' => route('admin.dashboard'), 'active' => $is('admin.dashboard')],
        ['sec' => __('SaaS')],
        ['label' => __('Subscriptions & Billing'), 'icon' => 'fa-solid fa-file-invoice-dollar', 'color' => '#16a34a', 'children' => [
            ['label' => __('Subscriptions'), 'icon' => 'fa-solid fa-rotate', 'url' => route('admin.subscriptions.index'), 'active' => $is('admin.subscriptions.*')],
            ['label' => __('Invoices & Payments'), 'icon' => 'fa-solid fa-money-check-dollar', 'color' => '#f59e0b', 'url' => route('admin.invoices.index'), 'active' => $is('admin.invoices.*'), 'badge' => $pv],
            ['label' => __('Packages / Plans'), 'icon' => 'fa-solid fa-layer-group', 'color' => '#8b5cf6', 'url' => route('admin.plans.index'), 'active' => $is('admin.plans.*')],
            ['label' => __('Commission'), 'icon' => 'fa-solid fa-percent', 'color' => '#ef4444', 'url' => route('admin.commission'), 'active' => $is('admin.commission')],
        ]],
        ['label' => __('Platform Reports'), 'icon' => 'fa-solid fa-chart-pie', 'color' => '#f97316', 'url' => route('admin.reports'), 'active' => $is('admin.reports')],
        ['sec' => __('Multi Branch')],
        ['label' => __('Branches'), 'icon' => 'fa-solid fa-store', 'color' => '#0ea5e9', 'url' => route('admin.branches.index'), 'active' => $is('admin.branches.*')],
        ['label' => __('All Orders'), 'icon' => 'fa-solid fa-receipt', 'color' => '#f59e0b', 'url' => route('admin.orders.index'), 'active' => $is('admin.orders.*')],
        ['label' => __('Catalog & Coupons'), 'icon' => 'fa-solid fa-tags', 'color' => '#ec4899', 'children' => [
            ['label' => __('Categories'), 'icon' => 'fa-solid fa-sitemap', 'url' => route('admin.categories.index'), 'active' => $is('admin.categories.*')],
            ['label' => __('Global Coupons'), 'icon' => 'fa-solid fa-ticket', 'color' => '#f59e0b', 'url' => route('admin.coupons.index'), 'active' => $is('admin.coupons.*')],
        ]],
        ['sec' => __('Site')],
        ['label' => __('Communication'), 'icon' => 'fa-solid fa-comment-sms', 'color' => '#14b8a6', 'children' => [
            ['label' => __('SMS & Email Log'), 'icon' => 'fa-solid fa-envelope', 'url' => route('admin.messages'), 'active' => $is('admin.messages')],
            ['label' => __('Subscribers'), 'icon' => 'fa-solid fa-envelope-open-text', 'color' => '#6366f1', 'url' => route('admin.subscribers'), 'active' => $is('admin.subscribers')],
        ]],
        ['label' => __('Home Slider'), 'icon' => 'fa-solid fa-images', 'color' => '#f43f5e', 'url' => route('admin.sliders.index'), 'active' => $is('admin.sliders.*')],
        ['label' => __('Custom Domains'), 'icon' => 'fa-solid fa-globe', 'color' => '#0284c7', 'url' => route('admin.domains.index'), 'active' => $is('admin.domains.*')],
        ['label' => __('Payment & SMS Gateways'), 'icon' => 'fa-solid fa-credit-card', 'color' => '#16a34a', 'url' => route('admin.gateways'), 'active' => $is('admin.gateways*')],
        ['label' => __('AI Settings'), 'icon' => 'fa-solid fa-wand-magic-sparkles', 'color' => '#a855f7', 'url' => route('admin.ai'), 'active' => $is('admin.ai*')],
        ['label' => __('System'), 'icon' => 'fa-solid fa-gear', 'color' => '#64748b', 'children' => [
            ['label' => __('Settings & Gateways'), 'icon' => 'fa-solid fa-sliders', 'url' => route('admin.settings'), 'active' => $is('admin.settings')],
            ['label' => __('Backup & Restore'), 'icon' => 'fa-solid fa-box-archive', 'color' => '#0f766e', 'url' => route('admin.backups.index'), 'active' => $is('admin.backups.*')],
            ['label' => __('Security (2FA)'), 'icon' => 'fa-solid fa-shield-halved', 'color' => '#ef4444', 'url' => route('admin.security'), 'active' => $is('admin.security*')],
            ['label' => __('Activity Logs'), 'icon' => 'fa-solid fa-clock-rotate-left', 'color' => '#f59e0b', 'url' => route('admin.activity'), 'active' => $is('admin.activity')],
        ]],
        ['label' => __('View Website'), 'icon' => 'fa-solid fa-arrow-up-right-from-square', 'color' => '#0ea5e9', 'url' => route('home'), 'active' => false, 'blank' => true],
    ];
    $initial = mb_strtoupper(mb_substr((string) ($admin?->name ?? 'A'), 0, 1));
@endphp
<body class="panel-ui">
<aside class="mg-sidebar" id="sidebar">
    <div class="brand">
        @if ($siteLogo)<img src="{{ asset('storage/'.$siteLogo) }}" alt="" class="logo-img">@else<span class="logo-ic" style="background:linear-gradient(135deg,#f59e0b,#ef4444)"><i class="fa-solid fa-crown"></i></span>@endif
        <span class="t"><b>Super Admin</b><small>{{ \App\Models\Setting::siteName() }}</small></span>
    </div>
    <nav>
        @include('partials.panel-menu', ['items' => $menu])
        <form method="POST" action="{{ route('admin.logout') }}">@csrf
            <button type="submit" class="pm-item" title="{{ __('Logout') }}"><span class="pm-ic" style="background:#e11d481f;color:#e11d48"><i class="fa-solid fa-right-from-bracket"></i></span><span class="pm-label">{{ __('Logout') }}</span></button>
        </form>
    </nav>
</aside>
<div class="sb-backdrop" onclick="document.getElementById('sidebar').classList.remove('show')"></div>
<div class="mg-main">
    <div class="mg-topbar">
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="burger" id="sbToggle" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
            <span class="page-title d-none d-sm-inline">@yield('title', 'Dashboard')</span>
        </div>
        <div class="tb-right">
            <div class="small">@include('partials.lang-switch')</div>
            <a href="{{ route('admin.notifications') }}" class="text-dark" title="Notifications"><span class="position-relative"><i class="fa-regular fa-bell fs-5"></i>@if($un)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px">{{ $un }}</span>@endif</span></a>
            <a href="{{ route('admin.settings') }}" class="tb-user">
                <span class="avatar" style="background:linear-gradient(135deg,#f59e0b,#ef4444)">{{ $initial }}</span>
                <span class="nm">{{ $admin->name }}<small>Super Admin</small></span>
            </a>
            <form method="POST" action="{{ route('admin.logout') }}" class="p-0">@csrf<button class="tb-logout" title="{{ __('Logout') }}"><i class="fa-solid fa-right-from-bracket"></i></button></form>
        </div>
    </div>
    <div class="mg-content">
        @include('partials.flash')
        @if ($errors->any())
            <div class="alert alert-danger small"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('partials.panel-toggle')
@include('partials.image-upload-compressor')
@stack('scripts')
</body>
</html>
