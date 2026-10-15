@extends('layouts.manager')
@section('title', 'Customers (CRM)')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('manager.customers.index') }}" class="btn btn-sm {{ request('segment') ? 'btn-light' : 'btn-dark' }}">All</a>
    @foreach (\App\Models\Customer::SEGMENTS as $k => [$label, $color])
        <a href="{{ route('manager.customers.index', array_merge(request()->except('page'), ['segment' => $k])) }}" class="btn btn-sm {{ request('segment') === $k ? 'btn-'.$color : 'btn-light' }}">{{ $label }} <span class="badge bg-secondary">{{ $segmentCounts[$k] ?? 0 }}</span></a>
    @endforeach
</div>
<div class="panel">
    <div class="panel-head flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input type="hidden" name="segment" value="{{ request('segment') }}">
            <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:220px" placeholder="Name / phone / email">
            <select name="group" class="form-select form-select-sm" style="width:140px"><option value="">All groups</option>@foreach (\App\Models\Customer::GROUPS as $k => $v)<option value="{{ $k }}" @selected(request('group') === $k)>{{ $v }}</option>@endforeach</select>
            <select name="sort" class="form-select form-select-sm" style="width:150px"><option value="">Newest</option><option value="spent" @selected(request('sort') === 'spent')>Top spenders</option></select>
            <button class="btn btn-sm btn-dark">Filter</button>
        </form>
        <div class="d-flex gap-2">
            @perm('customers.export')<a href="{{ route('manager.customers.index', array_merge(request()->query(), ['export' => 'csv'])) }}" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-file-csv"></i> Export</a>@endperm
            @perm('customers.add')<button class="btn btn-sm btn-brand" data-bs-toggle="collapse" data-bs-target="#addCust"><i class="fa-solid fa-plus"></i> Add</button>@endperm
        </div>
    </div>
    @perm('customers.add')
    <div class="collapse" id="addCust">
        <form method="POST" action="{{ route('manager.customers.store') }}" class="panel-body border-bottom row g-2">@csrf
            <div class="col-md-3"><input name="name" class="form-control form-control-sm" placeholder="Name" required></div>
            <div class="col-md-3"><input name="phone" class="form-control form-control-sm" placeholder="01XXXXXXXXX" required></div>
            <div class="col-md-3"><input type="email" name="email" class="form-control form-control-sm" placeholder="Email"></div>
            <div class="col-md-2"><select name="group" class="form-select form-select-sm">@foreach (\App\Models\Customer::GROUPS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-1"><button class="btn btn-sm btn-brand w-100">Save</button></div>
        </form>
    </div>
    @endperm
    <div class="table-responsive">
        <table class="table table-hover mb-0 small align-middle">
            <thead class="table-light"><tr><th>Customer</th><th>Group</th><th>Segment</th><th>Orders</th><th>Spent</th><th>Wallet / Points</th><th>Last order</th></tr></thead>
            <tbody>
            @forelse ($customers as $c)
                <tr onclick="location='{{ route('manager.customers.show', $c) }}'" style="cursor:pointer">
                    <td><strong>{{ $c->name }}</strong>@unless($c->is_active) <span class="badge bg-secondary">blocked</span>@endunless<br><span class="text-muted">{{ $c->phone }} {{ $c->email }}</span></td>
                    <td>{{ \App\Models\Customer::GROUPS[$c->group] ?? $c->group }}</td>
                    <td><span class="badge bg-{{ \App\Models\Customer::SEGMENTS[$c->seg][1] }}">{{ \App\Models\Customer::SEGMENTS[$c->seg][0] }}</span></td>
                    <td>{{ $c->orders_count }}</td>
                    <td>@money($c->spent ?? 0)</td>
                    <td>@money($c->wallet_balance) · {{ $c->loyalty_points }} pts</td>
                    <td class="text-muted">{{ $c->last_order_at ? \Illuminate\Support\Carbon::parse($c->last_order_at)->diffForHumans() : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5">কোনো কাস্টমার নেই। স্টোরে রেজিস্ট্রেশন করলে এখানে দেখা যাবে।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $customers->links() }}</div>
</div>
@endsection
