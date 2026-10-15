@if ($brands->isNotEmpty())
<section class="section pt-0">
    <div class="container">
        <h2 class="section-title mb-3">{{ $sec['title'] ?: __('Brands') }}</h2>
        <div class="d-flex flex-wrap gap-3">
            @foreach ($brands as $b)
                <a href="{{ route('store.products', [$store, 'brand' => $b->slug]) }}" class="bg-white rounded shadow-sm px-4 py-3 d-flex align-items-center" style="min-width:140px;height:70px">
                    @if ($b->logo_url)<img src="{{ $b->logo_url }}" alt="{{ $b->name }}" style="max-height:44px;max-width:120px">@else<span class="fw-bold text-dark">{{ $b->name }}</span>@endif
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif
