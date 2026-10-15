@extends('layouts.manager')
@section('title', 'Returns & Refunds')

@section('content')
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="{{ route('manager.returns.index') }}" class="btn btn-sm {{ request('status') ? 'btn-light' : 'btn-dark' }}">All</a>
    @foreach (\App\Models\ReturnRequest::STATUSES as $k => [$label, $color])
        <a href="{{ route('manager.returns.index', ['status' => $k]) }}" class="btn btn-sm {{ request('status') === $k ? 'btn-'.$color : 'btn-light' }}">{{ $label }}</a>
    @endforeach
</div>
<div class="panel">
    <div class="table-responsive"><table class="table table-hover mb-0 small align-middle">
        <thead class="table-light"><tr><th>Request</th><th>Order</th><th>Customer</th><th>Type / Reason</th><th>Items</th><th>Refund</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($returns as $r)
            <tr onclick="location='{{ route('manager.returns.show', $r) }}'" style="cursor:pointer">
                <td class="fw-semibold">{{ $r->number }}<br><span class="text-muted">{{ $r->created_at->format('d M Y') }}</span></td>
                <td>{{ $r->order->order_number }}</td>
                <td>{{ $r->customer?->name ?? $r->order->customer_name }}<br><span class="text-muted">{{ $r->order->customer_phone }}</span></td>
                <td>{{ \App\Models\ReturnRequest::TYPES[$r->type] ?? $r->type }}<br><span class="text-muted">{{ \App\Models\ReturnRequest::REASONS[$r->reason] ?? $r->reason }}</span></td>
                <td>{{ collect($r->items)->sum('quantity') }}</td>
                <td>@money($r->refund_amount)</td>
                <td><span class="badge bg-{{ $r->status_color }}">{{ $r->status_label }}</span></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-5">No return requests.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="panel-body">{{ $returns->links() }}</div>
</div>
@endsection
