@if ($posts->isNotEmpty())
<section class="section pt-0">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <h2 class="section-title">{{ $sec['title'] ?: __('From our blog') }}</h2>
            <a href="{{ route('store.blog', $store) }}" class="view-all">{{ __('View All') }} <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="row g-3">
            @foreach ($posts as $post)
                <div class="col-md-4">@include('store.blog._card')</div>
            @endforeach
        </div>
    </div>
</section>
@endif
