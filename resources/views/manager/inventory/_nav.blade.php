<ul class="nav nav-pills small mb-3 flex-wrap gap-1">
    @foreach ([['manager.inventory.stock', 'fa-boxes-stacked', 'Stock & Valuation'], ['manager.inventory.ledger', 'fa-list', 'Stock Ledger'], ['manager.inventory.adjustments', 'fa-sliders', 'Adjustment / Damage'], ['manager.inventory.transfers', 'fa-right-left', 'Stock Transfer'], ['manager.warehouses.index', 'fa-warehouse', 'Warehouses']] as [$r, $i, $l])
        <li class="nav-item"><a href="{{ route($r) }}" class="nav-link {{ request()->routeIs($r) ? 'active bg-dark' : 'bg-white text-dark border' }}"><i class="fa-solid {{ $i }}"></i> {{ $l }}</a></li>
    @endforeach
</ul>
