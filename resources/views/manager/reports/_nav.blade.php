@php $acc = \App\Support\Tenancy::branch()->hasFeature('accounting'); @endphp
<ul class="nav nav-pills small mb-3 gap-1 flex-wrap d-print-none">
    @foreach ([
        ['manager.reports.index', 'fa-gauge', 'Overview', true],
        ['manager.reports.sales', 'fa-chart-column', 'Sales', true],
        ['manager.reports.products', 'fa-box', 'Products & Profit', true],
        ['manager.reports.profit-loss', 'fa-scale-balanced', 'Profit & Loss', $acc],
        ['manager.reports.purchases', 'fa-truck-ramp-box', 'Purchases & Supplier Due', $acc],
        ['manager.reports.customers', 'fa-users', 'Customers', true],
        ['manager.reports.dues', 'fa-hourglass-half', 'Receivables', true],
        ['manager.reports.tax', 'fa-percent', 'VAT / Tax', true],
    ] as [$r, $icon, $label, $ok])
        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs($r) ? 'active' : '' }}" href="{{ $ok ? route($r) : route('manager.billing.plans') }}"><i class="fa-solid {{ $icon }}"></i> {{ $label }} @unless($ok)<i class="fa-solid fa-lock small"></i>@endunless</a></li>
    @endforeach
</ul>
