@extends('layouts.manager')
@section('title', $customer->name)

@section('content')
<a href="{{ route('manager.customers.index') }}" class="small text-muted"><i class="fa-solid fa-arrow-left"></i> Customers</a>
<div class="row g-4 mt-1">
    <div class="col-xl-4">
        <div class="panel mb-3">
            <div class="panel-body">
                <h5 class="fw-bold mb-0">{{ $customer->name }}</h5>
                <div class="small text-muted">{{ $customer->phone }} · {{ $customer->email }}</div>
                <span class="badge bg-{{ \App\Models\Customer::SEGMENTS[$segment][1] }} mt-2">{{ \App\Models\Customer::SEGMENTS[$segment][0] }}</span>
                <div class="row text-center mt-3 small">
                    <div class="col"><div class="fw-bold fs-5">{{ $orders->where('status', '!=', 'cancelled')->count() }}</div>Orders</div>
                    <div class="col"><div class="fw-bold fs-5">৳{{ number_format($orders->where('status', '!=', 'cancelled')->sum('total')) }}</div>Spent</div>
                    <div class="col"><div class="fw-bold fs-5">{{ $customer->loyalty_points }}</div>Points</div>
                </div>
                <div class="small text-muted mt-2">Joined {{ $customer->created_at->format('d M Y') }} · Last login {{ $customer->last_login_at?->diffForHumans() ?? 'never' }}</div>
            </div>
        </div>
        @perm('customers.edit')
        <form method="POST" action="{{ route('manager.customers.update', $customer) }}" class="panel mb-3">@csrf @method('PUT')
            <div class="panel-head">Profile & Notes</div>
            <div class="panel-body small">
                <input name="name" value="{{ $customer->name }}" class="form-control form-control-sm mb-2" required>
                <input type="email" name="email" value="{{ $customer->email }}" class="form-control form-control-sm mb-2" placeholder="Email">
                <select name="group" class="form-select form-select-sm mb-2">@foreach (\App\Models\Customer::GROUPS as $k => $v)<option value="{{ $k }}" @selected($customer->group === $k)>{{ $v }}</option>@endforeach</select>
                <textarea name="notes" rows="3" class="form-control form-control-sm mb-2" placeholder="Internal notes (customer can't see)">{{ $customer->notes }}</textarea>
                <div class="form-check mb-2"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($customer->is_active)><label class="form-check-label">Active (can log in)</label></div>
                <button class="btn btn-sm btn-dark">Save</button>
            </div>
        </form>
        <div class="panel mb-3">
            <div class="panel-head">Wallet: @money($customer->wallet_balance)</div>
            <div class="panel-body small">
                <form method="POST" action="{{ route('manager.customers.wallet', $customer) }}" class="d-flex gap-1 mb-2">@csrf
                    <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="+100 / -50" required style="width:100px">
                    <input name="description" class="form-control form-control-sm" placeholder="Reason" required>
                    <button class="btn btn-sm btn-dark">OK</button>
                </form>
                <form method="POST" action="{{ route('manager.customers.points', $customer) }}" class="d-flex gap-1">@csrf
                    <input type="number" name="points" class="form-control form-control-sm" placeholder="± points" required style="width:100px">
                    <button class="btn btn-sm btn-outline-dark">Adjust points</button>
                </form>
                @foreach ($customer->walletTransactions as $t)
                    <div class="d-flex justify-content-between border-bottom py-1 mt-1"><span>{{ $t->description }}<br><span class="text-muted">{{ $t->created_at->format('d M y') }}</span></span><span class="{{ $t->amount >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($t->amount, 2) }}</span></div>
                @endforeach
            </div>
        </div>
        @endperm
        @if ($customer->addresses->isNotEmpty())
            <div class="panel"><div class="panel-head">Addresses</div><div class="panel-body small">
                @foreach ($customer->addresses as $a)<div class="mb-2"><strong>{{ $a->label }}</strong> {{ $a->name }}, {{ $a->phone }}<br><span class="text-muted">{{ $a->fullAddress() }}</span></div>@endforeach
            </div></div>
        @endif
    </div>
    <div class="col-xl-8">
        <div class="panel mb-3">
            <div class="panel-head">Order / Purchase History</div>
            <div class="table-responsive"><table class="table table-hover mb-0 small">
                <thead class="table-light"><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($orders as $o)
                    <tr onclick="location='{{ route('manager.orders.show', $o) }}'" style="cursor:pointer"><td class="fw-semibold">{{ $o->order_number }}</td><td>{{ $o->created_at->format('d M Y') }}</td><td>{{ $o->items_count }}</td><td>@money($o->total)</td><td>{{ $o->method_label }}</td><td><span class="badge bg-{{ $o->status_color }}">{{ $o->status_label }}</span></td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No orders.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
        <div class="row g-3">
            <div class="col-md-6"><div class="panel h-100"><div class="panel-head">Wishlist</div><div class="panel-body small">
                @forelse ($wishlist as $w)<div>{{ $w->product?->name }}</div>@empty<span class="text-muted">Empty</span>@endforelse
            </div></div></div>
            <div class="col-md-6"><div class="panel h-100"><div class="panel-head">Reviews & Returns</div><div class="panel-body small">
                @foreach ($reviews as $r)<div>{{ str_repeat('★', $r->rating) }} {{ $r->product?->name }}</div>@endforeach
                @foreach ($customer->returns as $ret)<div><a href="{{ route('manager.returns.show', $ret) }}">{{ $ret->number }}</a> <span class="badge bg-{{ $ret->status_color }}">{{ $ret->status_label }}</span></div>@endforeach
                @if ($reviews->isEmpty() && $customer->returns->isEmpty())<span class="text-muted">None</span>@endif
            </div></div></div>
        </div>
    </div>
</div>
@endsection
