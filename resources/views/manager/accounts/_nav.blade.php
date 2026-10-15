<ul class="nav nav-pills small mb-3 gap-1 flex-wrap">
    <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('manager.accounts.index') ? 'active' : '' }}" href="{{ route('manager.accounts.index') }}"><i class="fa-solid fa-building-columns"></i> Accounts</a></li>
    <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('manager.vouchers.*') && request('kind') !== 'income' ? 'active' : '' }}" href="{{ route('manager.vouchers.index') }}"><i class="fa-solid fa-money-bill-wave"></i> Expenses</a></li>
    <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('manager.vouchers.*') && request('kind') === 'income' ? 'active' : '' }}" href="{{ route('manager.vouchers.index', ['kind' => 'income']) }}"><i class="fa-solid fa-hand-holding-dollar"></i> Other Income</a></li>
    <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('manager.accounts.transfers') ? 'active' : '' }}" href="{{ route('manager.accounts.transfers') }}"><i class="fa-solid fa-right-left"></i> Transfers</a></li>
    <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('manager.accounts.cashbook') ? 'active' : '' }}" href="{{ route('manager.accounts.cashbook') }}"><i class="fa-solid fa-book"></i> Cash Book</a></li>
    @perm('reports.view')<li class="nav-item"><a class="nav-link py-1" href="{{ route('manager.reports.profit-loss') }}"><i class="fa-solid fa-chart-line"></i> Profit & Loss</a></li>@endperm
</ul>
