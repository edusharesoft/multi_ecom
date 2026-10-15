<section class="section pt-0">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <h2 class="section-title">{{ $sec['title'] ?: __('Popular Categories') }}</h2>
            <a href="{{ route('store.products', $store) }}" class="view-all">{{ __('View All') }} <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="row g-3">
            @foreach ($categories->where('products_count', '>', 0)->take($sec['limit'] ?: 4) as $c)
                <div class="col-6 col-lg-3">
                    <a href="{{ route('store.products', [$store, 'category' => $c->slug]) }}" class="cat-card">
                        <div class="img" style="background-image:url('{{ $c->image_url }}')"></div>
                        <h6>{{ $c->name }}</h6>
                        <small class="d-block pb-3">{{ $c->tagline }} · {{ $c->products_count }} items</small>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
