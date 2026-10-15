{{-- Section with a product grid. expects $title, $items, optional $link, $extra --}}
@if ($items->isNotEmpty())
<section class="section pt-0">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <h2 class="section-title">{!! $title !!}</h2>
            @isset($link)<a href="{{ $link }}" class="view-all">{{ __('View All') }} <i class="fa-solid fa-arrow-right"></i></a>@endisset
        </div>
        <div class="row g-3">
            @foreach ($items as $product)
                <div class="col-6 col-md-4 col-lg-3">@include('partials.product-card')</div>
            @endforeach
        </div>
    </div>
</section>
@endif
