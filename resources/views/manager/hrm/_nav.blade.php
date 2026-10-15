<ul class="nav nav-pills small mb-3 gap-1 flex-wrap">
    @foreach ([['manager.hrm.employees', 'fa-id-badge', 'Employees'], ['manager.hrm.attendance', 'fa-calendar-check', 'Attendance'], ['manager.hrm.attendance.report', 'fa-table', 'Monthly sheet'], ['manager.hrm.leaves', 'fa-plane-departure', 'Leave'], ['manager.hrm.payrolls', 'fa-money-check-dollar', 'Payroll']] as [$r, $i, $l])
        <li class="nav-item"><a class="nav-link py-1 {{ request()->routeIs($r) || ($r === 'manager.hrm.payrolls' && request()->routeIs('manager.hrm.payroll*')) ? 'active' : '' }}" href="{{ route($r) }}"><i class="fa-solid {{ $i }}"></i> {{ __($l) }}</a></li>
    @endforeach
</ul>
