{{-- expects $product (with branch loaded) --}}
<div class="product-card">
    <a href="{{ $product->url }}" class="thumb">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
        @if ($product->isFlashSale())<span class="tag" style="background:#f59e0b"><i class="fa-solid fa-bolt"></i> {{ __('Flash') }}</span>
        @elseif ($product->is_new)<span class="tag">{{ __('New') }}</span>@endif
        @if ($product->discount_percent)<span class="tag sale">-{{ $product->discount_percent }}%</span>@endif
    </a>
    <div class="info">
        <a href="{{ $product->url }}" class="name">{{ $product->name }}</a>
        @if ($product->description)
            <div class="description">{{ \Illuminate\Support\Str::limit(strip_tags($product->description), 92) }}</div>
        @endif
        <div class="price mt-1">@money($product->currentPrice()) @if($product->comparePrice())<del>@money($product->comparePrice())</del>@endif</div>
        <div class="meta">
            @if ($showBranch ?? false){{ $product->branch->display_name }}
            @elseif ($product->isFlashSale())<i class="fa-regular fa-clock"></i> <span data-countdown="{{ $product->sale_ends_at->toIso8601String() }}"></span>
            @elseif ($product->stock <= 0 && ! ($product->variants_count ?? 0))<span class="text-danger">{{ __('Out of stock') }}</span>
            @else {{ __('Limited time offer') }} @endif
        </div>
    </div>
    <div class="actions">
        @if (($product->variants_count ?? $product->variants()->count()) > 0)
            <a href="{{ $product->url }}" class="btn btn-gray"><i class="fa-solid fa-cart-plus"></i> {{ __('Add to Cart') }}</a>
        @else
            <form method="POST" action="{{ route('store.cart.add', $product->branch) }}" class="d-flex flex-fill">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button class="btn btn-gray w-100"><i class="fa-solid fa-cart-plus"></i> {{ __('Add to Cart') }}</button>
            </form>
        @endif
        <a href="{{ $product->url }}" class="btn btn-gray"><i class="fa-regular fa-eye"></i> {{ __('View') }}</a>
    </div>
</div>
