<ul class="nav nav-pills small mb-3 gap-1 flex-wrap">
    @foreach ([
        ['manager.website.theme', 'fa-palette', 'Theme & Menu', 'website.view'],
        ['manager.website.builder', 'fa-table-cells-large', 'Homepage Builder', 'website.view'],
        ['manager.pages.index', 'fa-file-lines', 'Pages', 'website.view'],
        ['manager.sliders.index', 'fa-images', 'Slider & Banner', 'website.view'],
        ['manager.website.seo', 'fa-magnifying-glass-chart', 'SEO', 'settings.edit'],
        ['manager.domains.index', 'fa-globe', 'Domain', 'settings.edit'],
    ] as [$r, $icon, $label, $perm])
        @if (\App\Support\Tenancy::can($perm))
            <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs($r, str_replace('.index', '.*', $r)) ? 'active' : '' }}" href="{{ route($r) }}"><i class="fa-solid {{ $icon }}"></i> {{ $label }}</a></li>
        @endif
    @endforeach
    <li class="nav-item ms-auto"><a class="nav-link py-1" href="{{ route('store.home', \App\Support\Tenancy::branch()) }}" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View store</a></li>
</ul>
