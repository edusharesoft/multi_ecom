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

                <div class="mt-5 sb-product-tabs" id="desc">
                    <ul class="nav nav-tabs" id="productInfoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold" id="description-tab" data-bs-toggle="tab" data-bs-target="#description-pane" type="button" role="tab" aria-controls="description-pane" aria-selected="true">
                                <i class="fa-solid fa-align-left me-1"></i>Description
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews-pane" type="button" role="tab" aria-controls="reviews-pane" aria-selected="false">
                                <i class="fa-regular fa-star me-1"></i>Reviews
                                @if ($product->reviews->count())
                                    <span class="badge bg-light text-muted ms-1">{{ $product->reviews->count() }}</span>
                                @endif
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content pt-4" id="productInfoTabsContent">
                        <div class="tab-pane fade show active" id="description-pane" role="tabpanel" aria-labelledby="description-tab" tabindex="0">
                            <div class="row g-4">
                                <div class="{{ $product->specifications ? 'col-lg-7' : 'col-12' }}">
                                    <div class="pd-box h-100">
                                        <h5 class="fw-bold mb-3">Product Description</h5>
                                        <div class="small text-muted" style="white-space:pre-line">{{ $product->description ?: 'No description available.' }}</div>
                                    </div>
                                </div>
                                @if ($product->specifications)
                                    <div class="col-lg-5">
                                        <div class="pd-box h-100">
                                            <h5 class="fw-bold mb-3">Specification</h5>
                                            <div class="table-responsive">
                                                <table class="table table-sm small mb-0">
                                                    <tbody>
                                                        @foreach ($product->specifications as $spec)
                                                            <tr>
                                                                <th class="text-muted fw-normal" style="width:40%">{{ $spec['key'] }}</th>
                                                                <td>{{ $spec['value'] }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="tab-pane fade" id="reviews-pane" role="tabpanel" aria-labelledby="reviews-tab" tabindex="0">
                            <div class="sb-review-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                                <div>
                                    <h4 class="fw-bold mb-1">Customer Reviews</h4>
                                    <p class="text-muted small mb-0">See what customers are saying about this product.</p>
                                </div>
                                <button type="button" class="btn btn-brand btn-sm" onclick="document.getElementById('review-form').scrollIntoView({behavior:'smooth',block:'center'})">
                                    <i class="fa-solid fa-pen me-1"></i>Write a Review
                                </button>
                            </div>
                            @if ($product->reviews->count())
                                @php
                                    $reviewCount = $product->reviews->count();
                                    $averageRating = round($product->reviews->avg('rating'), 1);
                                @endphp
                                <div class="sb-review-summary mb-4">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <div class="sb-review-average">{{ number_format($averageRating, 1) }}</div>
                                                <div class="stars fs-5 mb-1">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        {{ $i <= round($averageRating) ? '★' : '☆' }}
                                                    @endfor
                                                </div>
                                                <div class="small text-muted">Based on {{ $reviewCount }} {{ $reviewCount == 1 ? 'review' : 'reviews' }}</div>
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            @for ($rating = 5; $rating >= 1; $rating--)
                                                @php
                                                    $ratingCount = $product->reviews->where('rating', $rating)->count();
                                                    $ratingPercent = $reviewCount ? ($ratingCount / $reviewCount) * 100 : 0;
                                                @endphp
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="small text-muted sb-rating-label">{{ $rating }} <span class="stars">★</span></span>
                                                    <div class="progress flex-grow-1 sb-rating-progress">
                                                        <div class="progress-bar" style="width:{{ $ratingPercent }}%"></div>
                                                    </div>
                                                    <span class="small text-muted sb-rating-count">{{ $ratingCount }}</span>
                                                </div>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="sb-review-form mb-4" id="review-form">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <div class="sb-review-form-icon"><i class="fa-solid fa-pen"></i></div>
                                    <div>
                                        <h5 class="fw-bold mb-0">Write a Review</h5>
                                        <small class="text-muted">Share your experience with this product.</small>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('store.review', [$store, $product->slug]) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-md-auto">
                                            <label class="form-label small fw-semibold">Rating</label>
                                            <select name="rating" class="form-select form-select-sm stars sb-rating-select" required>
                                                @for ($i = 5; $i >= 1; $i--)
                                                    <option value="{{ $i }}">{{ str_repeat('★', $i) }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        @unless ($storeCustomer)
                                            <div class="col-md-4">
                                                <label class="form-label small fw-semibold">Your Name</label>
                                                <input name="name" class="form-control form-control-sm" placeholder="Enter your name" required maxlength="100">
                                            </div>
                                        @endunless
                                        <div class="col-md flex-grow-1">
                                            <label class="form-label small fw-semibold">Your Review</label>
                                            <input name="comment" class="form-control form-control-sm" placeholder="Write your experience..." required maxlength="1000">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-semibold">Photo <span class="text-muted fw-normal">(Optional)</span></label>
                                            <input type="file" name="image" accept="image/*" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-brand btn-sm px-4">
                                                <i class="fa-solid fa-paper-plane me-1"></i>Submit Review
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                @error('comment')
                                    <div class="text-danger small mt-2"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="sb-review-list">
                                @forelse ($product->reviews as $r)
                                    <div class="sb-review-item">
                                        <div class="d-flex gap-3">
                                            <div class="sb-review-avatar">{{ strtoupper(substr($r->name ?: 'C', 0, 1)) }}</div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex flex-wrap justify-content-between gap-2">
                                                    <div>
                                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                                            <strong class="small">{{ $r->name }}</strong>
                                                            @if ($r->is_verified)
                                                                <span class="sb-verified-badge"><i class="fa-solid fa-circle-check"></i>Verified Purchase</span>
                                                            @endif
                                                        </div>
                                                        <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                                            <span class="stars small">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</span>
                                                            <span class="text-muted small">{{ $r->created_at->diffForHumans() }}</span>
                                                        </div>
                                                    </div>
                                                    <form method="POST" action="{{ route('store.review.report', [$store, $product->slug, $r->id]) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-link btn-sm p-0 text-muted" title="Report review">
                                                            <i class="fa-regular fa-flag"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                <div class="small text-dark mt-3 sb-review-comment">{{ $r->comment }}</div>
                                                @if ($r->image_url)
                                                    <div class="mt-3">
                                                        <a href="{{ $r->image_url }}" target="_blank" class="sb-review-image-link">
                                                            <img src="{{ $r->image_url }}" alt="Review image" class="sb-review-image">
                                                        </a>
                                                    </div>
                                                @endif
                                                @if ($r->reply)
                                                    <div class="sb-store-reply mt-3">
                                                        <div class="small fw-semibold text-brand mb-1"><i class="fa-solid fa-reply me-1"></i>{{ $store->display_name }}</div>
                                                        <div class="small text-muted">{{ $r->reply }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="sb-no-reviews text-center py-5">
                                        <div class="sb-no-review-icon mb-3"><i class="fa-regular fa-comment-dots"></i></div>
                                        <h6 class="fw-bold mb-1">No reviews yet</h6>
                                        <p class="small text-muted mb-3">Be the first customer to review this product.</p>
                                        <button type="button" class="btn btn-brand btn-sm" onclick="document.getElementById('review-form').scrollIntoView({behavior:'smooth',block:'center'})">
                                            <i class="fa-solid fa-pen me-1"></i>Write the First Review
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <style>
                .sb-product-tabs .nav-tabs{border-bottom:1px solid #dee2e6}
                .sb-product-tabs .nav-link{color:#6c757d;border:0;border-bottom:2px solid transparent;border-radius:0;padding:.7rem 1rem}
                .sb-product-tabs .nav-link:hover{color:var(--bs-primary);border-color:transparent}
                .sb-product-tabs .nav-link.active{color:var(--bs-primary);background:transparent;border-color:transparent transparent var(--bs-primary)}
                .sb-product-tabs .tab-content{min-height:150px}
                .sb-review-summary{padding:1.25rem;border:1px solid #e9ecef;border-radius:.75rem;background:#fff}
                .sb-review-average{font-size:2.5rem;line-height:1;font-weight:700}
                .sb-rating-progress{height:6px;background:#edf0f2;border-radius:10px}
                .sb-rating-progress .progress-bar{background:var(--bs-primary);border-radius:10px}
                .sb-rating-label{width:42px}
                .sb-rating-count{width:25px;text-align:right}
                .sb-review-form{padding:1.25rem;border:1px solid #e9ecef;border-radius:.75rem;background:#fafbfc}
                .sb-review-form-icon{width:34px;height:34px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:var(--bs-primary-bg-subtle);color:var(--bs-primary)}
                .sb-rating-select{min-width:105px}
                .sb-review-item{padding:1.25rem 0;border-bottom:1px solid #edf0f2}
                .sb-review-item:first-child{padding-top:0}
                .sb-review-avatar{width:42px;height:42px;min-width:42px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:var(--bs-primary-bg-subtle);color:var(--bs-primary);font-weight:700}
                .sb-verified-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:20px;background:#eaf8f0;color:#198754;font-size:.7rem;font-weight:600}
                .sb-review-comment{line-height:1.7}
                .sb-review-image-link{display:inline-block}
                .sb-review-image{width:76px;height:76px;object-fit:cover;border-radius:.5rem;border:1px solid #e9ecef;transition:opacity .2s ease}
                .sb-review-image:hover{opacity:.8}
                .sb-store-reply{padding:.75rem 1rem;border-left:3px solid var(--bs-primary);background:#f8f9fa;border-radius:0 .5rem .5rem 0}
                .sb-no-reviews{border:1px dashed #dee2e6;border-radius:.75rem}
                .sb-no-review-icon{font-size:2rem;color:#adb5bd}
                @media(max-width:575.98px){.sb-review-summary,.sb-review-form{padding:1rem}.sb-review-item{padding:1rem 0}.sb-review-avatar{width:36px;height:36px;min-width:36px}.sb-review-header h4{font-size:1.15rem}}
                </style>
            </div>
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
