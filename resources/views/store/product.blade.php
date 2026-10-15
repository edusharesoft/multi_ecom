@extends('layouts.store')
@section('title', ($product->meta_title ?: $product->name).' — '.$store->display_name)
@section('meta_description', $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 155))
@section('og_image', $product->image_url)

@push('head')
    @if ($product->meta_keywords)<meta name="keywords" content="{{ $product->meta_keywords }}">@endif
    <meta property="og:type" content="product">
    <script type="application/ld+json">{!! json_encode(array_filter([
        '@@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'image' => [$product->image_url],
        'description' => \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 300),
        'sku' => $product->sku,
        'gtin' => $product->barcode,
        'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
        'offers' => ['@type' => 'Offer', 'priceCurrency' => 'BDT', 'price' => number_format($product->currentPrice(), 2, '.', ''), 'url' => $product->url,
            'availability' => $product->totalStock() > 0 || $product->product_type !== 'physical' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'],
        'aggregateRating' => $rating['count'] ? ['@type' => 'AggregateRating', 'ratingValue' => $rating['avg'], 'reviewCount' => $rating['count']] : null,
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<section class="section pt-2">
    <div class="container">
        <nav class="small mb-3"><a href="{{ route('store.home', $store) }}">Home</a>
            @if ($product->category) / <a href="{{ route('store.products', [$store, 'category' => $product->category->slug]) }}">{{ $product->category->name }}</a>@endif
            @if ($product->subcategory) / <a href="{{ route('store.products', [$store, 'category' => $product->subcategory->slug]) }}">{{ $product->subcategory->name }}</a>@endif
            / <span class="text-muted">{{ $product->name }}</span></nav>
        <div class="row g-4">
            {{-- Gallery --}}
            <div class="col-md-5">
                <div class="pd-box">
                    <img src="{{ $product->image_url }}" id="mainImg" class="pd-main" alt="{{ $product->name }}">
                    <div class="pd-thumbs d-flex gap-2 mt-2 flex-wrap">
                        <img src="{{ $product->image_url }}" class="active" alt="">
                        @foreach ($product->images as $img)<img src="{{ $img->url }}" alt="">@endforeach
                    </div>
                    @if ($product->youtube_id)
                        <div class="ratio ratio-16x9 mt-3 rounded overflow-hidden"><iframe src="https://www.youtube.com/embed/{{ $product->youtube_id }}" title="Product video" allowfullscreen loading="lazy"></iframe></div>
                    @elseif ($product->video_url)
                        <a href="{{ $product->video_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark mt-3"><i class="fa-solid fa-play"></i> Watch video</a>
                    @endif
                </div>
            </div>

            {{-- Info --}}
            <div class="col-md-7">
                <div class="pd-box">
                    <div class="d-flex justify-content-between gap-2">
                        <h1 class="h4 fw-bold mb-1">{{ $product->name }}</h1>
                        <div class="dropdown">
                            <button class="btn btn-brand btn-sm" data-bs-toggle="dropdown"><i class="fa-solid fa-share-nodes"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end small">
                                <li><a class="dropdown-item" target="_blank" rel="noopener" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($product->url) }}"><i class="fa-brands fa-facebook"></i> Facebook</a></li>
                                <li><a class="dropdown-item" target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode($product->name.' '.$product->url) }}"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a></li>
                                <li><a class="dropdown-item" href="#" onclick="navigator.clipboard.writeText('{{ $product->url }}');this.textContent='Copied!';return false;"><i class="fa-solid fa-link"></i> Copy link</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="small text-muted mb-2">
                        @if ($rating['count'])<span class="stars">@for ($i = 1; $i <= 5; $i++)<i class="fa-{{ $i <= round($rating['avg']) ? 'solid' : 'regular' }} fa-star"></i>@endfor</span> {{ $rating['avg'] }} ({{ $rating['count'] }}) · @endif
                        @if ($product->sku)SKU: {{ $product->sku }} · @endif
                        @if ($product->product_code)Code: {{ $product->product_code }} · @endif
                        @if ($product->brand)Brand: <a href="{{ route('store.products', [$store, 'brand' => $product->brand->slug]) }}">{{ $product->brand->name }}</a>@endif
                    </div>
                    <div class="mb-2">Price: <span class="price-tag">@money($product->currentPrice())</span>
                        @if ($product->comparePrice())<del class="text-muted ms-2">@money($product->comparePrice())</del> <span class="badge bg-success">-{{ $product->discount_percent }}%</span>@endif
                        <span class="small text-muted">/ {{ \App\Models\Product::UNITS[$product->unit] ?? $product->unit }}</span>
                    </div>
                    @if ($product->isFlashSale())
                        <div class="alert alert-warning py-2 small"><i class="fa-solid fa-bolt"></i> Flash Sale শেষ হবে: <strong data-countdown="{{ $product->sale_ends_at->toIso8601String() }}"></strong></div>
                    @endif
                    @if ($product->product_type !== 'physical')<span class="badge bg-info mb-2">{{ \App\Models\Product::TYPES[$product->product_type] }}</span>@endif

                    <form method="POST" action="{{ route('store.cart.add', $store) }}" id="addForm">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        @if ($product->variants->isNotEmpty())
                            <div class="fw-semibold small mb-2">Available Variants (Size / Color):</div>
                            @foreach ($product->variants as $v)
                                <label @class(['variant-row', 'out' => $v->stock < 1])>
                                    <input type="checkbox" class="form-check-input m-0" name="selected[]" value="{{ $v->id }}" @disabled($v->stock < 1)>
                                    <span class="flex-fill">{{ $v->name }}
                                        @if ($v->price !== null && (float) $v->price !== $product->currentPrice()) <small class="text-brand">(@money($v->price))</small>@endif
                                        @if ($v->stock < 1)<small class="text-danger">— Out of stock</small>@elseif ($v->stock < 5)<small class="text-warning">— Only {{ $v->stock }} left</small>@endif
                                    </span>
                                    <input type="number" name="variants[{{ $v->id }}]" value="1" min="1" max="{{ max(1, $v->stock) }}" class="form-control form-control-sm" @disabled($v->stock < 1)>
                                </label>
                            @endforeach
                        @else
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="small fw-semibold">Quantity:</span>
                                <input type="number" name="quantity" value="1" min="1" max="{{ $product->product_type === 'physical' ? max(1, $product->stock) : 99 }}" class="form-control form-control-sm" style="width:90px">
                                @if ($product->product_type === 'physical')
                                    <span class="small {{ $product->stock > 0 ? 'text-success' : 'text-danger' }}">{{ $product->stock > 0 ? $product->stock.' in stock' : 'Out of stock' }}</span>
                                @endif
                            </div>
                        @endif

                        <div class="row g-2 mt-2">
                            <div class="col-4"><button name="action" value="buy" class="btn btn-brand w-100 btn-sm">Buy Now</button></div>
                            <div class="col-4"><button name="action" value="cart" class="btn btn-gray w-100 btn-sm"><i class="fa-solid fa-cart-shopping"></i> Add to Cart</button></div>
                            <div class="col-4"><button type="submit" form="wishForm" class="btn btn-outline-brand w-100 btn-sm"><i class="fa-regular fa-heart"></i> Wishlist</button></div>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('store.wishlist.toggle', [$store, $product]) }}" id="wishForm">@csrf</form>

                    <div class="row g-2 mt-1">
                        <div class="col-4"><a href="#desc" class="btn btn-brand w-100 btn-sm">Description</a></div>
                        <div class="col-4"><a href="#reviews" class="btn btn-brand w-100 btn-sm">Reviews ({{ $rating['count'] }})</a></div>
                        <div class="col-4"><a href="{{ $store->map_url ?: 'https://www.google.com/maps/search/'.urlencode($store->address) }}" target="_blank" rel="noopener" class="btn btn-brand w-100 btn-sm">Find in Store</a></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Description + specification --}}
        <div class="row g-4 mt-1" id="desc">
            <div class="{{ $product->specifications ? 'col-lg-7' : 'col-12' }}">
                <div class="pd-box h-100">
                    <h5 class="fw-bold">Product Description</h5>
                    <div class="small text-muted" style="white-space:pre-line">{{ $product->description ?: 'No description available.' }}</div>
                </div>
            </div>
            @if ($product->specifications)
                <div class="col-lg-5">
                    <div class="pd-box h-100">
                        <h5 class="fw-bold">Specification</h5>
                        <table class="table table-sm small mb-0">
                            @foreach ($product->specifications as $spec)<tr><th class="text-muted fw-normal" style="width:40%">{{ $spec['key'] }}</th><td>{{ $spec['value'] }}</td></tr>@endforeach
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Reviews --}}
        <div class="mt-4" id="reviews">
            <h5 class="fw-bold">Write a Review</h5>
            <form method="POST" action="{{ route('store.review', [$store, $product->slug]) }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center">
                @csrf
                <select name="rating" class="form-select form-select-sm stars" style="width:110px">
                    @for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}">{{ str_repeat('★', $i) }}</option>@endfor
                </select>
                @unless ($storeCustomer)<input name="name" class="form-control form-control-sm" style="max-width:170px" placeholder="Your name" required maxlength="100">@endunless
                <input name="comment" class="form-control form-control-sm flex-fill" style="min-width:200px" placeholder="Write a short review..." required maxlength="1000">
                <input type="file" name="image" accept="image/*" class="form-control form-control-sm" style="max-width:220px" title="Photo (optional)">
                <button class="btn btn-brand btn-sm">Submit</button>
            </form>
            @error('comment')<div class="text-danger small">{{ $message }}</div>@enderror

            <h5 class="fw-bold mt-4">Customer Reviews</h5>
            @forelse ($product->reviews as $r)
                <div class="border-bottom py-2 small">
                    <div class="d-flex justify-content-between">
                        <span><span class="stars">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</span> <strong>{{ $r->name }}</strong>
                            @if ($r->is_verified)<span class="badge bg-success-subtle text-success"><i class="fa-solid fa-circle-check"></i> Verified Purchase</span>@endif
                            <span class="text-muted">· {{ $r->created_at->diffForHumans() }}</span></span>
                        <form method="POST" action="{{ route('store.review.report', [$store, $product->slug, $r->id]) }}">@csrf<button class="btn btn-link btn-sm p-0 text-muted" title="Report"><i class="fa-regular fa-flag"></i></button></form>
                    </div>
                    <div>{{ $r->comment }}</div>
                    @if ($r->image_url)<a href="{{ $r->image_url }}" target="_blank"><img src="{{ $r->image_url }}" class="rounded mt-1" style="height:70px"></a>@endif
                    @if ($r->reply)<div class="bg-light rounded p-2 mt-1"><strong class="text-brand"><i class="fa-solid fa-reply"></i> {{ $store->display_name }}:</strong> {{ $r->reply }}</div>@endif
                </div>
            @empty
                <p class="small text-muted">এখনও কোনো রিভিউ নেই। প্রথম রিভিউটি দিন!</p>
            @endforelse
        </div>

        @if ($related->isNotEmpty())
            <h5 class="fw-bold mt-5 mb-3">You may also like</h5>
            <div class="row g-3">
                @foreach ($related as $product)
                    <div class="col-6 col-md-3">@include('partials.product-card')</div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.pd-thumbs img').forEach(t => t.addEventListener('click', () => {
    document.getElementById('mainImg').src = t.src;
    document.querySelectorAll('.pd-thumbs img').forEach(x => x.classList.remove('active'));
    t.classList.add('active');
}));
document.querySelectorAll('.variant-row input[type=number]').forEach(n => n.addEventListener('input', () => {
    n.closest('.variant-row').querySelector('input[type=checkbox]').checked = true;
}));
</script>
@endpush

@push('scripts')
<script>window.mbTrack && mbTrack('ViewContent', {value: {{ (float) $product->currentPrice() }}, content_ids: [{{ $product->id }}], content_name: @json($product->name), content_type: 'product'});</script>
@endpush
