@extends('layouts.store')
@section('title', 'Products — '.$store->display_name)

@section('content')
<section class="section pt-2">
    <div class="container">
        <div class="row g-4">
            <aside class="col-lg-3">
                <form method="GET" class="pd-box">
                    <h6 class="fw-bold">Categories</h6>
                    <div class="list-group list-group-flush mb-3 small">
                        <a href="{{ route('store.products', array_merge([$store], request()->except('category', 'page'))) }}" class="list-group-item list-group-item-action px-0 {{ request('category') ? '' : 'text-brand fw-semibold' }}">All Products</a>
                        @foreach ($categories as $c)
                            <a href="{{ route('store.products', array_merge([$store], request()->except('page'), ['category' => $c->slug])) }}" class="list-group-item list-group-item-action px-0 {{ request('category') === $c->slug ? 'text-brand fw-semibold' : '' }}">{{ $c->name }}</a>
                            @foreach ($c->children as $sc)
                                <a href="{{ route('store.products', array_merge([$store], request()->except('page'), ['category' => $sc->slug])) }}" class="list-group-item list-group-item-action ps-3 pe-0 {{ request('category') === $sc->slug ? 'text-brand fw-semibold' : 'text-muted' }}">— {{ $sc->name }}</a>
                            @endforeach
                        @endforeach
                    </div>
                    <input type="hidden" name="category" value="{{ request('category') }}">
                    @if ($brands->isNotEmpty())
                        <h6 class="fw-bold">Brand</h6>
                        <select name="brand" class="form-select form-select-sm mb-3">
                            <option value="">All brands</option>
                            @foreach ($brands as $b)<option value="{{ $b->slug }}" @selected(request('brand') === $b->slug)>{{ $b->name }}</option>@endforeach
                        </select>
                    @endif
                    <div class="form-check small"><input class="form-check-input" type="checkbox" name="sale" value="1" id="fSale" @checked(request()->boolean('sale'))><label class="form-check-label" for="fSale">Discount / Offer</label></div>
                    <div class="form-check small"><input class="form-check-input" type="checkbox" name="flash" value="1" id="fFlash" @checked(request()->boolean('flash'))><label class="form-check-label" for="fFlash">Flash Sale</label></div>
                    <div class="form-check small mb-3"><input class="form-check-input" type="checkbox" name="in_stock" value="1" id="fStock" @checked(request()->boolean('in_stock'))><label class="form-check-label" for="fStock">In stock only</label></div>
                    <h6 class="fw-bold">Search</h6>
                    <input name="q" value="{{ request('q') }}" class="form-control form-control-sm mb-3" placeholder="Product name / code">
                    <h6 class="fw-bold">Price (৳)</h6>
                    <div class="d-flex gap-2 mb-3">
                        <input name="min" type="number" min="0" value="{{ request('min') }}" class="form-control form-control-sm" placeholder="Min">
                        <input name="max" type="number" min="0" value="{{ request('max') }}" class="form-control form-control-sm" placeholder="Max">
                    </div>
                    <button class="btn btn-brand btn-sm w-100">Filter</button>
                </form>
            </aside>
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h2 class="section-title">{{ optional($categories->flatMap(fn ($c) => collect([$c])->merge($c->children))->firstWhere('slug', request('category')))->name ?? (request()->boolean('flash') ? 'Flash Sale' : (request()->boolean('sale') ? 'Offers' : 'All Products')) }}</h2>
                    <form method="GET" class="d-flex align-items-center gap-2">
                        @foreach (request()->except('sort', 'page') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                        <span class="small text-muted">{{ $products->total() }} items</span>
                        <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Newest</option>
                            <option value="price_asc" @selected(request('sort') === 'price_asc')>Price: Low to High</option>
                            <option value="price_desc" @selected(request('sort') === 'price_desc')>Price: High to Low</option>
                            <option value="name" @selected(request('sort') === 'name')>Name</option>
                            <option value="best" @selected(request('sort') === 'best')>Best Selling</option>
                        </select>
                    </form>
                </div>
                <div class="row g-3">
                    @forelse ($products as $product)
                        <div class="col-6 col-md-4">@include('partials.product-card')</div>
                    @empty
                        <div class="col-12 text-center text-muted py-5"><i class="fa-solid fa-box-open fa-2x mb-2"></i><br>কোনো পণ্য পাওয়া যায়নি।</div>
                    @endforelse
                </div>
                <div class="mt-4">{{ $products->links() }}</div>
            </div>
        </div>
    </div>
</section>
@endsection
