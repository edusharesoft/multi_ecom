@php
    $me = $tenant ?? \App\Support\Tenancy::branch();
    $who = $actor ?? \App\Support\Tenancy::actor();
    $sub = $me->subscription;
    $plan = $me->effectivePlan();
@endphp
<!doctype html>
<html lang="bn">
<head>
    @include('partials.head')
    <link href="{{ asset('css/panel.css') }}?v=2" rel="stylesheet">
    @if ($me->favicon_url)<link rel="icon" href="{{ $me->favicon_url }}">@endif
    <title>@yield('title', 'Dashboard') — {{ $me->display_name }}</title>
    @include('pwa._head', ['manifest' => route('pwa.manager.manifest'), 'color' => '#111827', 'appName' => 'POS', 'icon' => asset('images/pwa/pos-192.png')])
</head>
@php
    $can = fn (string $p) => \App\Support\Tenancy::can($p);
    $is = fn (...$r) => request()->routeIs(...$r);
    $feat = fn (string $f) => $me->hasFeature($f);
    $plans = route('manager.billing.plans');
    $newOrders = $can('orders.view') ? $me->orders()->whereIn('status', ['new', 'pending'])->count() : 0;
    $menu = [
        ['label' => __('Dashboard'), 'icon' => 'fa-solid fa-gauge-high', 'color' => '#6366f1', 'url' => route('manager.dashboard'), 'active' => $is('manager.dashboard'), 'show' => $can('dashboard.view')],
        ['label' => __('POS'), 'icon' => 'fa-solid fa-cash-register', 'color' => '#16a34a', 'url' => $feat('pos') ? route('manager.pos.index') : $plans, 'active' => $is('manager.pos.*'), 'lock' => ! $feat('pos'), 'show' => $can('pos.view')],
        ['label' => __('Orders'), 'icon' => 'fa-solid fa-receipt', 'color' => '#f59e0b', 'children' => [
            ['label' => __('Manage Orders'), 'icon' => 'fa-solid fa-list-check', 'url' => route('manager.orders.index'), 'active' => $is('manager.orders.*'), 'badge' => $newOrders, 'show' => $can('orders.view')],
            ['label' => __('Courier & Shipments'), 'icon' => 'fa-solid fa-truck-fast', 'color' => '#0ea5e9', 'url' => route('manager.shipments.index'), 'active' => $is('manager.shipments.*', 'manager.courier.*'), 'show' => $can('orders.view')],
            ['label' => __('Returns & Refunds'), 'icon' => 'fa-solid fa-rotate-left', 'color' => '#ef4444', 'url' => route('manager.returns.index'), 'active' => $is('manager.returns.*'), 'show' => $can('returns.view')],
        ]],
        ['label' => __('Products'), 'icon' => 'fa-solid fa-boxes-stacked', 'color' => '#0ea5e9', 'show' => $can('products.view'), 'children' => [
            ['label' => __('Manage Products'), 'icon' => 'fa-solid fa-box', 'url' => route('manager.products.index'), 'active' => $is('manager.products.*')],
            ['label' => __('Brands'), 'icon' => 'fa-solid fa-tag', 'color' => '#8b5cf6', 'url' => route('manager.brands.index'), 'active' => $is('manager.brands.*')],
        ]],
        $feat('inventory')
            ? ['label' => __('Inventory'), 'icon' => 'fa-solid fa-warehouse', 'color' => '#8b5cf6', 'children' => [
                ['label' => __('Stock & Warehouses'), 'icon' => 'fa-solid fa-cubes', 'url' => route('manager.inventory.stock'), 'active' => $is('manager.inventory.*', 'manager.warehouses.*'), 'show' => $can('inventory.view')],
                ['label' => __('Purchases'), 'icon' => 'fa-solid fa-truck-ramp-box', 'color' => '#f97316', 'url' => route('manager.purchases.index'), 'active' => $is('manager.purchases.*'), 'show' => $can('purchases.view')],
                ['label' => __('Suppliers'), 'icon' => 'fa-solid fa-handshake', 'color' => '#14b8a6', 'url' => route('manager.suppliers.index'), 'active' => $is('manager.suppliers.*'), 'show' => $can('suppliers.view')],
            ]]
            : ['label' => __('Inventory & Purchase'), 'icon' => 'fa-solid fa-warehouse', 'color' => '#8b5cf6', 'url' => $plans, 'active' => false, 'lock' => true, 'show' => $can('inventory.view')],
        ['label' => __('Customers (CRM)'), 'icon' => 'fa-solid fa-users', 'color' => '#ec4899', 'url' => route('manager.customers.index'), 'active' => $is('manager.customers.*'), 'show' => $can('customers.view')],
        ['label' => __('Marketing'), 'icon' => 'fa-solid fa-bullhorn', 'color' => '#ef4444', 'children' => [
            ['label' => __('Marketing & SMS'), 'icon' => 'fa-solid fa-comment-sms', 'url' => route('manager.marketing.index'), 'active' => $is('manager.marketing.*'), 'show' => $can('marketing.view')],
            ['label' => __('Coupons'), 'icon' => 'fa-solid fa-ticket', 'color' => '#f59e0b', 'url' => $feat('coupon') ? route('manager.coupons.index') : $plans, 'active' => $is('manager.coupons.*'), 'lock' => ! $feat('coupon'), 'show' => $can('coupons.view')],
            ['label' => __('Reviews'), 'icon' => 'fa-solid fa-star', 'color' => '#eab308', 'url' => route('manager.reviews.index'), 'active' => $is('manager.reviews.*'), 'show' => $can('reviews.view')],
        ]],
        ['label' => __('Website'), 'icon' => 'fa-solid fa-globe', 'color' => '#14b8a6', 'children' => [
            ['label' => __('Website & SEO'), 'icon' => 'fa-solid fa-paintbrush', 'url' => route('manager.website.builder'), 'active' => $is('manager.website.*', 'manager.sliders.*', 'manager.pages.*', 'manager.domains.*'), 'show' => $can('website.view')],
            ['label' => __('Blog'), 'icon' => 'fa-solid fa-pen-nib', 'color' => '#6366f1', 'url' => $feat('blog') ? route('manager.blog.index') : $plans, 'active' => $is('manager.blog.*'), 'lock' => ! $feat('blog'), 'show' => $can('blog.view')],
            ['label' => __('View Store'), 'icon' => 'fa-solid fa-arrow-up-right-from-square', 'color' => '#0ea5e9', 'url' => route('store.home', $me), 'active' => false, 'blank' => true],
        ]],
        ['label' => __('Reports'), 'icon' => 'fa-solid fa-chart-pie', 'color' => '#f97316', 'show' => $can('reports.view'), 'children' => [
            ['label' => __('Reports'), 'icon' => 'fa-solid fa-chart-column', 'url' => $feat('reports') ? route('manager.reports.index') : $plans, 'active' => $is('manager.reports.*'), 'lock' => ! $feat('reports')],
            ['label' => __('AI Assistant'), 'icon' => 'fa-solid fa-wand-magic-sparkles', 'color' => '#a855f7', 'url' => route('manager.ai.insights'), 'active' => $is('manager.ai.*'), 'lock' => ! $feat('ai')],
        ]],
        ['label' => __('Accounts & Expenses'), 'icon' => 'fa-solid fa-scale-balanced', 'color' => '#0f766e', 'url' => $feat('accounting') ? route('manager.accounts.index') : $plans, 'active' => $is('manager.accounts.*', 'manager.vouchers.*'), 'lock' => ! $feat('accounting'), 'show' => $can('accounts.view')],
        ['label' => __('HR & Staff'), 'icon' => 'fa-solid fa-id-card-clip', 'color' => '#a855f7', 'children' => [
            ['label' => __('HRM & Payroll'), 'icon' => 'fa-solid fa-user-clock', 'url' => $feat('hrm') ? route('manager.hrm.employees') : $plans, 'active' => $is('manager.hrm.*'), 'lock' => ! $feat('hrm'), 'show' => $can('hrm.view')],
            ['label' => __('Staff & Roles'), 'icon' => 'fa-solid fa-users-gear', 'color' => '#6366f1', 'url' => route('manager.staff.index'), 'active' => $is('manager.staff.*', 'manager.roles.*'), 'show' => $can('staff.view')],
        ]],
        ['label' => __('Settings'), 'icon' => 'fa-solid fa-gear', 'color' => '#64748b', 'children' => [
            ['label' => __('Setting'), 'icon' => 'fa-solid fa-sliders', 'url' => route('manager.settings'), 'active' => $is('manager.settings')],
            ['label' => __('Subscription'), 'icon' => 'fa-solid fa-credit-card', 'color' => '#16a34a', 'url' => route('manager.billing'), 'active' => $is('manager.billing*'), 'show' => $can('subscription.view')],
            ['label' => __('Security (2FA)'), 'icon' => 'fa-solid fa-shield-halved', 'color' => '#ef4444', 'url' => route('manager.security'), 'active' => $is('manager.security*')],
            ['label' => __('REST API'), 'icon' => 'fa-solid fa-code', 'color' => '#0ea5e9', 'url' => route('manager.api.index'), 'active' => $is('manager.api.*'), 'lock' => ! $feat('api'), 'show' => $can('settings.edit')],
            ['label' => __('Activity Log'), 'icon' => 'fa-solid fa-clock-rotate-left', 'color' => '#f59e0b', 'url' => route('manager.activity'), 'active' => $is('manager.activity'), 'show' => $can('activity.view')],
        ]],
    ];
    $initial = mb_strtoupper(mb_substr((string) ($who?->name ?: $me->display_name), 0, 1));
@endphp
<body class="panel-ui">
<aside class="mg-sidebar" id="sidebar">
    <div class="brand">
        @if ($me->logo_url)<img src="{{ $me->logo_url }}" alt="" class="logo-img">@else<span class="logo-ic"><i class="fa-solid fa-store"></i></span>@endif
        <span class="t"><b>{{ $me->display_name }}</b><small>{{ preg_replace('#^https?://#', '', url($me->slug)) }}</small></span>
        <a href="{{ route('manager.billing') }}" class="plan badge bg-{{ $sub?->isUsable() ? 'success' : 'secondary' }} w-100 py-1">{{ $plan->name }}{{ $sub?->status === 'trial' ? ' · Trial' : '' }}</a>
    </div>
    <nav>
        @include('partials.panel-menu', ['items' => $menu])
        <form method="POST" action="{{ route('logout') }}">@csrf
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
            <a href="{{ rtrim(config('app.url'), '/') }}/user-manual#dashboard" target="_blank" rel="noopener" class="text-dark" title="{{ __('User Manual') }}">
                <i class="fa-solid fa-circle-question fs-5" style="color:#16a34a"></i>
            </a>
            <a href="{{ route('manager.notifications') }}" class="position-relative text-dark" title="Notifications">
                <span class="position-relative"><i class="fa-regular fa-bell fs-5"></i>
                @if (($unreadNotifications ?? 0) > 0)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px">{{ $unreadNotifications }}</span>@endif</span>
            </a>
            <a href="{{ route('manager.settings') }}" class="tb-user">
                <span class="avatar">{{ $initial }}</span>
                <span class="nm">{{ $who?->name }}<small>{{ ($isOwner ?? true) ? __('Branch Admin') : ($who->role?->name ?? __('Staff')) }}</small></span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="p-0">@csrf<button class="tb-logout" title="{{ __('Logout') }}"><i class="fa-solid fa-right-from-bracket"></i></button></form>
        </div>
    </div>
    <div class="mg-content">
        @if (session('impersonating'))
            <div class="alert alert-dark small d-flex justify-content-between align-items-center py-2">
                <span><i class="fa-solid fa-user-secret"></i> Super Admin হিসেবে এই ব্রাঞ্চ দেখছেন।</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-light">Back to Super Admin</button></form>
            </div>
        @endif
        {{-- Subscription banners --}}
        @if ($sub && $sub->status === 'grace')
            <div class="alert alert-danger small py-2"><i class="fa-solid fa-triangle-exclamation"></i> সাবস্ক্রিপশনের মেয়াদ শেষ — Grace period {{ $sub->grace_ends_at?->format('d M Y') }} পর্যন্ত। <a href="{{ route('manager.billing') }}" class="alert-link">এখনই রিনিউ করুন</a></div>
        @elseif ($sub && $sub->isReadOnly())
            <div class="alert alert-danger small py-2"><i class="fa-solid fa-lock"></i> অ্যাকাউন্ট Read-only (মেয়াদ শেষ)। <a href="{{ route('manager.billing') }}" class="alert-link">রিনিউ করুন</a></div>
        @elseif ($sub && in_array($sub->status, ['active', 'trial'], true) && $sub->ends_at && $sub->daysLeft() <= 7 && ! $plan->is_free)
            <div class="alert alert-warning small py-2"><i class="fa-regular fa-clock"></i> {{ $sub->status === 'trial' ? 'ফ্রি ট্রায়াল' : 'সাবস্ক্রিপশন' }} {{ $sub->daysLeft() }} দিনের মধ্যে শেষ হবে ({{ $sub->ends_at->format('d M Y') }})। <a href="{{ route('manager.billing') }}" class="alert-link">Billing</a></div>
        @elseif ($sub && $sub->status === 'pending')
            <div class="alert alert-info small py-2"><i class="fa-solid fa-circle-info"></i> {{ $sub->plan->name }} প্ল্যানের পেমেন্ট বাকি — ততক্ষণ Free প্ল্যানের লিমিট প্রযোজ্য। <a href="{{ route('manager.billing') }}" class="alert-link">Pay now</a></div>
        @endif
        @include('partials.flash')
        @if ($errors->any())
            <div class="alert alert-danger small"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('partials.panel-toggle')
@include('pwa._register', ['sw' => route('pwa.manager.sw').'?v='.rawurlencode((string) env('PWA_VERSION', '3')), 'scope' => \App\Http\Controllers\PwaController::managerScope(), 'cachePrefix' => 'mb-m-pages-'])
@include('partials.image-upload-compressor')
@stack('scripts')
</body>
</html>
