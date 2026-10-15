@extends('layouts.manager')
@section('title', 'Stock Ledger')

@section('content')
@include('manager.inventory._nav')
<div class="panel">
    <form class="panel-head gap-2 flex-wrap justify-content-start" method="GET">
        @if ($product)<span class="badge bg-dark">{{ $product->name }}</span><input type="hidden" name="product" value="{{ $product->id }}">@endif
        <select name="warehouse" class="form-select form-select-sm" style="width:160px"><option value="">All warehouses</option>@foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected(request('warehouse') == $w->id)>{{ $w->name }}</option>@endforeach</select>
        <select name="type" class="form-select form-select-sm" style="width:170px"><option value="">All movements</option>@foreach (\App\Models\StockMovement::TYPES as $k => [$l])<option value="{{ $k }}" @selected(request('type') === $k)>{{ $l }}</option>@endforeach</select>
        <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" style="width:140px">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" style="width:140px">
        <button class="btn btn-sm btn-dark">Filter</button>
        @if ($product)<a href="{{ route('manager.inventory.ledger') }}" class="small">clear product</a>@endif
    </form>
    <div class="table-responsive">
        <table class="table table-sm mb-0 small align-middle">
            <thead class="table-light"><tr><th>Date</th><th>Product</th><th>Warehouse</th><th>Movement</th><th class="text-end">Qty</th><th class="text-end">Balance</th><th class="text-end">Cost</th><th>Reference / Note</th><th>By</th></tr></thead>
            <tbody>
            @forelse ($movements as $m)
                <tr>
                    <td class="text-nowrap">{{ $m->created_at->format('d M y, h:i A') }}</td>
                    <td>{{ $m->product?->name }}@if($m->variant)<span class="text-muted"> — {{ $m->variant->name }}</span>@endif</td>
                    <td>{{ $m->warehouse?->name }}</td>
                    <td><span class="badge bg-{{ $m->type_color }}">{{ $m->type_label }}</span></td>
                    <td class="text-end fw-semibold {{ $m->quantity >= 0 ? 'text-success' : 'text-danger' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
                    <td class="text-end">{{ $m->balance_after }}</td>
                    <td class="text-end">{{ $m->unit_cost !== null ? number_format($m->unit_cost, 2) : '' }}</td>
                    <td>@if($m->reference_type)<span class="text-muted">{{ class_basename($m->reference_type) }}</span> {{ $m->reference?->order_number ?? $m->reference?->number ?? '' }}@endif {{ $m->note }}</td>
                    <td class="text-muted">{{ $m->actor_name }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-5">No movements.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $movements->links() }}</div>
</div>
@endsection
