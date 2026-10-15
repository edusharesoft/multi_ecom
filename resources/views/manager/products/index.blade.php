@extends('layouts.manager')
@section('title', 'Manage Products')

@section('content')
<div class="panel">
    <div class="panel-head flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search name / SKU" style="width:200px">
            <select name="category" class="form-select form-select-sm" style="width:170px">
                <option value="">All categories</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
            </select>
            <select name="brand" class="form-select form-select-sm" style="width:140px">
                <option value="">All brands</option>
                @foreach ($brands as $b)<option value="{{ $b->id }}" @selected(request('brand') == $b->id)>{{ $b->name }}</option>@endforeach
            </select>
            <select name="stock" class="form-select form-select-sm" style="width:130px">
                <option value="">Any stock</option>
                <option value="low" @selected(request('stock') === 'low')>Low stock</option>
                <option value="out" @selected(request('stock') === 'out')>Out of stock</option>
            </select>
            <select name="status" class="form-select form-select-sm" style="width:130px">
                <option value="">Any status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="hidden" @selected(request('status') === 'hidden')>Hidden</option>
            </select>
            <button class="btn btn-sm btn-dark">Filter</button>
        </form>
        <div class="d-flex gap-2">
            @perm('products.print')<button type="button" class="btn btn-sm btn-outline-dark" onclick="const ids=[...document.querySelectorAll('.pick:checked')].map(c=>c.value); if(!ids.length){alert('Select products first');return;} window.open('{{ route('manager.products.labels') }}?copies='+(prompt('Copies per product?', '1')||1)+'&ids='+ids.join(','))"><i class="fa-solid fa-barcode"></i> Print Labels</button>@endperm
            @perm('products.add')<a href="{{ route('manager.products.create') }}" class="btn btn-sm btn-brand"><i class="fa-solid fa-plus"></i> Add Product</a>@endperm
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead class="table-light"><tr><th><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.pick').forEach(c=>c.checked=this.checked)"></th><th></th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            @forelse ($products as $p)
                <tr>
                    <td><input type="checkbox" class="form-check-input pick" value="{{ $p->id }}"></td>
                    <td><img src="{{ $p->image_url }}" class="thumb-sm" alt=""></td>
                    <td><strong>{{ $p->name }}</strong>@if($p->is_featured) <span class="badge bg-warning text-dark">Featured</span>@endif<br><span class="text-muted">{{ $p->sku }} @if($p->variants->count()) · {{ $p->variants->count() }} variants @endif</span></td>
                    <td>{{ $p->category?->name ?? '—' }}</td>
                    <td>@money($p->price) @if($p->old_price)<br><del class="text-muted">@money($p->old_price)</del>@endif</td>
                    <td>@php($s = $p->totalStock())<span class="{{ $s <= 0 ? 'text-danger fw-bold' : ($s <= $p->min_stock ? 'text-warning fw-semibold' : '') }}">{{ $s }}</span> <span class="text-muted">{{ $p->unit }}</span>@if($p->isOnSale())<br><span class="badge bg-warning text-dark">{{ $p->isFlashSale() ? 'Flash' : 'Sale' }} ৳{{ number_format($p->sale_price) }}</span>@endif</td>
                    <td><span class="badge bg-{{ $p->is_active ? 'success' : 'secondary' }}">{{ $p->is_active ? 'Active' : 'Hidden' }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('store.product', [$tenant->slug, $p->slug]) }}" target="_blank" class="btn btn-sm btn-light" title="View"><i class="fa-regular fa-eye"></i></a>
                        <a href="{{ route('manager.products.edit', $p) }}" class="btn btn-sm btn-light" title="Edit"><i class="fa-solid fa-pen"></i></a>
                        <form method="POST" action="{{ route('manager.products.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Delete this product?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-light text-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-5">কোনো পণ্য নেই। <a href="{{ route('manager.products.create') }}">প্রথম পণ্য যোগ করুন</a></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $products->links() }}</div>
</div>
@endsection
