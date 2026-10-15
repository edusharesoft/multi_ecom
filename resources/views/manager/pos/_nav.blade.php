<ul class="nav nav-pills small mb-3 gap-1">
    <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('manager.pos.index') ? 'active' : '' }}" href="{{ route('manager.pos.index') }}"><i class="fa-solid fa-cash-register"></i> Register</a></li>
    @perm('pos.approve')<li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('manager.pos.returns') ? 'active' : '' }}" href="{{ route('manager.pos.returns') }}"><i class="fa-solid fa-rotate-left"></i> Return / Exchange</a></li>@endperm
    <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs('manager.pos.sessions', 'manager.pos.session') ? 'active' : '' }}" href="{{ route('manager.pos.sessions') }}"><i class="fa-solid fa-clock-rotate-left"></i> Sessions / Daily Closing</a></li>
</ul>
