@extends('layouts.manager')
@section('title', 'Stock Transfer')

@section('content')
@include('manager.inventory._nav')
@if ($warehouses->count() < 2)
    <div class="alert alert-info small">ট্রান্সফারের জন্য কমপক্ষে ২টি ওয়্যারহাউস লাগবে। <a href="{{ route('manager.warehouses.index') }}">ওয়্যারহাউস যোগ করুন</a></div>
@else
    @perm('inventory.edit')
    <form method="POST" action="{{ route('manager.inventory.transfers.store') }}" class="panel mb-4">@csrf
        <div class="panel-head">New Transfer</div>
        <div class="panel-body">
            <div class="row g-2 mb-3 small">
                <div class="col-md-4"><label class="form-label mb-0">From</label><select name="from_warehouse_id" id="trFrom" class="form-select form-select-sm">@foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected($w->is_default)>{{ $w->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label mb-0">To</label><select name="to_warehouse_id" class="form-select form-select-sm">@foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected(! $w->is_default && $loop->index === 1)>{{ $w->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label mb-0">Note</label><input name="note" class="form-control form-control-sm"></div>
            </div>
            @include('manager.inventory._item-picker', ['withCost' => false, 'warehouseSelect' => '#trFrom'])
            <button class="btn btn-brand btn-sm">Transfer</button>
        </div>
    </form>
    @endperm
@endif
<div class="panel">
    <table class="table mb-0 small">
        <thead class="table-light"><tr><th>No.</th><th>Date</th><th>From → To</th><th>Items</th><th>Note</th><th>By</th></tr></thead>
        @forelse ($transfers as $t)
            <tr><td><a href="{{ route('manager.inventory.document', ['transfer', $t->number]) }}">{{ $t->number }}</a></td><td>{{ $t->created_at->format('d M Y') }}</td><td>{{ $t->from?->name }} → {{ $t->to?->name }}</td><td>{{ $t->items_count }}</td><td>{{ $t->note }}</td><td>{{ $t->actor_name }}</td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No transfers.</td></tr>
        @endforelse
    </table>
    <div class="panel-body">{{ $transfers->links() }}</div>
</div>
@endsection
