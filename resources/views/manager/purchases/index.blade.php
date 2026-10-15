@extends('layouts.manager')
@section('title', 'Purchases')

@section('content')
<div class="panel">
    <div class="panel-head flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <select name="supplier" class="form-select form-select-sm" style="width:180px"><option value="">All suppliers</option>@foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected(request('supplier') == $s->id)>{{ $s->name }}</option>@endforeach</select>
            <select name="status" class="form-select form-select-sm" style="width:120px"><option value="">Any</option><option value="due" @selected(request('status') === 'due')>Has due</option></select>
            <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" style="width:140px">
            <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" style="width:140px">
            <button class="btn btn-sm btn-dark">Filter</button>
        </form>
        @perm('purchases.add')<a href="{{ route('manager.purchases.create') }}" class="btn btn-sm btn-brand"><i class="fa-solid fa-plus"></i> New Purchase</a>@endperm
    </div>
    <div class="table-responsive"><table class="table table-hover mb-0 small align-middle">
        <thead class="table-light"><tr><th>Purchase</th><th>Supplier</th><th>Warehouse</th><th>Items</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($purchases as $p)
            <tr onclick="location='{{ route('manager.purchases.show', $p) }}'" style="cursor:pointer">
                <td><strong>{{ $p->number }}</strong><br><span class="text-muted">{{ $p->purchase_date->format('d M Y') }} {{ $p->reference }}</span></td>
                <td>{{ $p->supplier?->name }}</td>
                <td>{{ $p->warehouse?->name }}</td>
                <td>{{ $p->items_count }}</td>
                <td class="text-end">@money($p->total)@if($p->returned > 0)<br><span class="text-warning">−{{ number_format($p->returned) }} ret</span>@endif</td>
                <td class="text-end">@money($p->paid)</td>
                <td class="text-end {{ $p->due > 0 ? 'text-danger fw-semibold' : '' }}">@money($p->due)</td>
                <td><span class="badge bg-{{ ['paid' => 'success', 'partial' => 'warning', 'due' => 'danger'][$p->payment_status] }}">{{ ucfirst($p->payment_status) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-5">No purchases yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="panel-body">{{ $purchases->links() }}</div>
</div>
@endsection
