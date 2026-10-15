@extends('layouts.manager')
@section('title', 'Manage Orders')

@section('content')
<div class="d-flex flex-wrap gap-1 mb-3">
    <a href="{{ route('manager.orders.index') }}" class="btn btn-sm {{ request('status') ? 'btn-light' : 'btn-dark' }}">All <span class="badge bg-secondary">{{ $counts->sum() }}</span></a>
    @foreach (\App\Models\Order::STATUS_META as $k => [$label, $color])
        @if (($counts[$k] ?? 0) > 0 || request('status') === $k)
            <a href="{{ route('manager.orders.index', ['status' => $k]) }}" class="btn btn-sm {{ request('status') === $k ? 'btn-'.$color : 'btn-light' }}">{{ $label }} <span class="badge bg-secondary">{{ $counts[$k] ?? 0 }}</span></a>
        @endif
    @endforeach
</div>
<div class="panel">
    <div class="panel-head flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Order no / name / phone / TrxID" style="width:230px">
            <select name="payment" class="form-select form-select-sm" style="width:160px">
                <option value="">All payments</option>
                @foreach (\App\Models\Order::PAYMENT_STATUSES as $s)<option value="{{ $s }}" @selected(request('payment') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>@endforeach
            </select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" style="width:140px">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" style="width:140px">
            <button class="btn btn-sm btn-dark">Filter</button>
        </form>
        @perm('orders.edit')
        <form method="POST" action="{{ route('manager.orders.bulk') }}" id="bulkForm" class="d-flex gap-1">@csrf
            <select name="status" class="form-select form-select-sm" style="width:150px">@foreach (\App\Models\Order::STATUS_META as $k => [$label])<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
            <button class="btn btn-sm btn-outline-dark text-nowrap" onclick="return document.querySelectorAll('.pick:checked').length ? confirm('Update selected orders?') : (alert('Select orders'), false)">Bulk update</button>
        </form>
        @if (\App\Support\Tenancy::branch()->hasFeature('courier'))
        @perm('orders.edit')
        <form method="POST" action="{{ route('manager.orders.bulk-ship') }}" id="bulkShipForm" class="d-flex gap-1" onsubmit="document.querySelectorAll('.pick:checked').forEach(c => { const h = document.createElement('input'); h.type = 'hidden'; h.name = 'ids[]'; h.value = c.value; this.appendChild(h); }); return confirm('নির্বাচিত অর্ডারগুলো কুরিয়ারে বুক করবেন?')">@csrf
            <select name="courier" class="form-select form-select-sm" style="width:120px"><option value="steadfast">Steadfast</option><option value="pathao">Pathao</option></select>
            <button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-truck-fast"></i> Bulk ship</button>
        </form>
        @endperm
        @endif
        @endperm
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small align-middle">
            <thead class="table-light"><tr><th><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.pick').forEach(c=>c.checked=this.checked)"></th><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr>
                    <td><input type="checkbox" class="form-check-input pick" name="ids[]" value="{{ $o->id }}" form="bulkForm"></td>
                    <td><strong>{{ $o->order_number }}</strong>@if($o->source === 'pos') <span class="badge bg-dark">POS</span>@endif<br><span class="text-muted">{{ $o->created_at->format('d M Y, h:i A') }}</span></td>
                    <td>{{ $o->customer_name }}@if($o->customer_id) <i class="fa-solid fa-user-check text-success" title="Registered"></i>@endif<br><span class="text-muted">{{ $o->customer_phone }}</span></td>
                    <td>{{ $o->items_count }}</td>
                    <td class="fw-semibold">@money($o->total)@if($o->discount > 0)<br><span class="text-success">−{{ number_format($o->discount) }} {{ $o->coupon_code }}</span>@endif</td>
                    <td>{{ $o->method_label }}<br><span class="badge bg-{{ $o->payment_color }}">{{ ucfirst(str_replace('_', ' ', $o->payment_status)) }}</span></td>
                    <td><span class="badge bg-{{ $o->status_color }}">{{ $o->status_label }}</span></td>
                    <td class="text-end"><a href="{{ route('manager.orders.show', $o) }}" class="btn btn-sm btn-light">Details</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-5">কোনো অর্ডার পাওয়া যায়নি।</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $orders->links() }}</div>
</div>
@endsection
