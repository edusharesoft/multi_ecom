@extends('layouts.manager')
@section('title', 'Inventory')

@section('content')
@include('manager.inventory._nav')
<div class="row g-3 mb-3">
    @foreach ([['Total units', number_format($valuation['units']), 'fa-boxes-stacked', '#dbeafe', '#1d4ed8'], ['Stock value (cost)', '৳ '.number_format($valuation['cost'], 2), 'fa-sack-dollar', '#dcfce7', '#15803d'], ['Retail value', '৳ '.number_format($valuation['retail'], 2), 'fa-tags', '#fef3c7', '#b45309'], ['Low stock', $valuation['low'], 'fa-triangle-exclamation', '#fef9c3', '#a16207'], ['Out of stock', $valuation['out'], 'fa-circle-xmark', '#fee2e2', '#b91c1c']] as [$l, $v, $i, $bg, $c])
        <div class="col"><div class="stat-card"><div class="ic" style="background:{{ $bg }};color:{{ $c }}"><i class="fa-solid {{ $i }}"></i></div><div><div class="v" style="font-size:18px">{{ $v }}</div><div class="l">{{ $l }}</div></div></div></div>
    @endforeach
</div>
<div class="panel">
    <form class="panel-head gap-2 flex-wrap justify-content-start" method="GET">
        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:220px" placeholder="Name / SKU / barcode">
        <select name="category" class="form-select form-select-sm" style="width:160px"><option value="">All categories</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach</select>
        <select name="filter" class="form-select form-select-sm" style="width:140px"><option value="">All stock</option><option value="low" @selected(request('filter') === 'low')>Low stock alert</option><option value="out" @selected(request('filter') === 'out')>Out of stock</option></select>
        <button class="btn btn-sm btn-dark">Filter</button>
        @perm('inventory.export')<a href="{{ route('manager.inventory.stock', ['export' => 'csv']) }}" class="btn btn-sm btn-outline-dark ms-auto"><i class="fa-solid fa-file-csv"></i> Export</a>@endperm
    </form>
    <div class="table-responsive">
        <table class="table table-sm mb-0 small align-middle">
            <thead class="table-light"><tr><th>Product</th>@foreach ($warehouses as $w)<th class="text-center">{{ $w->code ?: $w->name }}</th>@endforeach<th class="text-center">Total</th><th class="text-end">Cost</th><th class="text-end">Value</th></tr></thead>
            <tbody>
            @forelse ($products as $p)
                @foreach ($p->variants->isEmpty() ? [null] : $p->variants as $v)
                    @php
                        $key = $p->id.'-'.($v?->id ?? 0);
                        $total = $v ? $v->stock : $p->stock;
                        $cls = $total <= 0 ? 'text-danger fw-bold' : ($total <= $p->min_stock ? 'text-warning fw-semibold' : '');
                    @endphp
                    <tr>
                        <td>@if(! $v)<strong>{{ $p->name }}</strong>@else<span class="text-muted">{{ $p->name }} —</span> {{ $v->name }}@endif <span class="text-muted">{{ $p->sku }}</span>
                            <a href="{{ route('manager.inventory.ledger', ['product' => $p->id]) }}" class="ms-1" title="Ledger"><i class="fa-solid fa-list"></i></a></td>
                        @foreach ($warehouses as $w)<td class="text-center">{{ $split[$key][$w->id] ?? 0 }}</td>@endforeach
                        <td class="text-center {{ $cls }}">{{ $total }}</td>
                        <td class="text-end">{{ number_format((float) $p->purchase_price, 2) }}</td>
                        <td class="text-end">{{ number_format($total * (float) $p->purchase_price, 2) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="{{ $warehouses->count() + 4 }}" class="text-center text-muted py-5">No products.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $products->links() }}</div>
</div>
@endsection
