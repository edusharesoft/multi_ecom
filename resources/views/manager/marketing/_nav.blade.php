<ul class="nav nav-pills small mb-3 gap-1 flex-wrap">
    <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('manager.marketing.index', 'manager.marketing.show', 'manager.marketing.create') ? 'active' : '' }}" href="{{ route('manager.marketing.index') }}"><i class="fa-solid fa-bullhorn"></i> Campaigns</a></li>
    @perm('marketing.edit')<li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('manager.marketing.settings') ? 'active' : '' }}" href="{{ route('manager.marketing.settings') }}"><i class="fa-solid fa-bell"></i> Notifications & Pixel</a></li>@endperm
    <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('manager.marketing.logs') ? 'active' : '' }}" href="{{ route('manager.marketing.logs') }}"><i class="fa-solid fa-list"></i> Message Log</a></li>
</ul>
