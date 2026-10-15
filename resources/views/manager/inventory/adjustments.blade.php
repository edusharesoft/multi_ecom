@extends('layouts.manager')
@section('title', 'Stock Adjustment / Damage')

@section('content')
@include('manager.inventory._nav')
@perm('inventory.approve')
<form method="POST" action="{{ route('manager.inventory.adjustments.store') }}" class="panel mb-4">@csrf
    <div class="panel-head">New Adjustment <small class="text-muted fw-normal">Stock in (পাওয়া গেছে / ফ্রি) · Stock out (হারিয়েছে) · Damaged</small></div>
    <div class="panel-body">
        <div class="row g-2 mb-3 small">
            <div class="col-md-3"><select name="warehouse_id" id="adjWh" class="form-select form-select-sm" required>@foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected($w->is_default)>{{ $w->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="type" class="form-select form-select-sm">@foreach (\App\Models\StockAdjustment::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-3"><input name="reason" class="form-control form-control-sm" placeholder="Reason (stock count, expired, broken...)"></div>
            <div class="col-md-3"><input name="note" class="form-control form-control-sm" placeholder="Note"></div>
        </div>
        @include('manager.inventory._item-picker', ['withCost' => false, 'warehouseSelect' => '#adjWh'])
        <button class="btn btn-brand btn-sm">Save Adjustment</button>
    </div>
</form>
@endperm
<div class="panel">
    <table class="table mb-0 small">
        <thead class="table-light"><tr><th>No.</th><th>Date</th><th>Warehouse</th><th>Type</th><th>Items</th><th>Reason</th><th>By</th></tr></thead>
        @forelse ($adjustments as $a)
            <tr><td><a href="{{ route('manager.inventory.document', ['adjustment', $a->number]) }}">{{ $a->number }}</a></td><td>{{ $a->created_at->format('d M Y') }}</td><td>{{ $a->warehouse?->name }}</td><td><span class="badge bg-{{ $a->type === 'in' ? 'success' : ($a->type === 'damage' ? 'danger' : 'warning') }}">{{ \App\Models\StockAdjustment::TYPES[$a->type] }}</span></td><td>{{ $a->items_count }}</td><td>{{ $a->reason }}</td><td>{{ $a->actor_name }}</td></tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No adjustments.</td></tr>
        @endforelse
    </table>
    <div class="panel-body">{{ $adjustments->links() }}</div>
</div>
@endsection
