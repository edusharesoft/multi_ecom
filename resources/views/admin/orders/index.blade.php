@extends('layouts.admin')
@section('title', 'All Orders')

@section('content')
<div class="panel">
    <form class="panel-head flex-wrap gap-2 justify-content-start" method="GET">
        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Order no / phone / TrxID" style="width:200px">
        <select name="branch" class="form-select form-select-sm" style="width:170px">
            <option value="">All branches</option>
            @foreach ($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->store_name ?: $b->name }}</option>@endforeach
        </select>
        <select name="status" class="form-select form-select-sm" style="width:130px">
            <option value="">Any status</option>
            @foreach (\App\Models\Order::STATUSES as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select>
        <select name="payment" class="form-select form-select-sm" style="width:160px">
            <option value="">Any payment</option>
            @foreach (\App\Models\Order::PAYMENT_STATUSES as $s)<option value="{{ $s }}" @selected(request('payment') === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>@endforeach
        </select>
        <select name="method" class="form-select form-select-sm" style="width:140px">
            <option value="">Any method</option>
            @foreach (\App\Models\Order::METHOD_LABELS as $k => $v)<option value="{{ $k }}" @selected(request('method') === $k)>{{ $v }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" style="width:140px">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" style="width:140px">
        <button class="btn btn-sm btn-dark">Filter</button>
        <span class="ms-auto small">{{ $orders->total() }} orders · <strong>@money($total)</strong></span>
    </form>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead class="table-light"><tr><th>Order</th><th>Branch</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr onclick="location='{{ route('admin.orders.show', $o) }}'" style="cursor:pointer">
                    <td><strong>{{ $o->order_number }}</strong><br><span class="text-muted">{{ $o->created_at->format('d M Y, h:i A') }}</span></td>
                    <td>{{ $o->branch->display_name }}</td>
                    <td>{{ $o->customer_name }}<br><span class="text-muted">{{ $o->customer_phone }}</span></td>
                    <td>{{ $o->items_count }}</td>
                    <td class="fw-semibold">@money($o->total)</td>
                    <td>{{ $o->method_label }}<br><span class="badge bg-{{ $o->payment_color }}">{{ ucfirst(str_replace('_', ' ', $o->payment_status)) }}</span></td>
                    <td><span class="badge bg-{{ $o->status_color }}">{{ ucfirst($o->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5">No orders found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $orders->links() }}</div>
</div>
@endsection
