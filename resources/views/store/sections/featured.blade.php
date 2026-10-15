@if ($featured->isNotEmpty())
<section class="section pt-0">
    <div class="container">
        <h2 class="section-title mb-3">{{ $sec['title'] ?: __('Featured') }}</h2>
        <div class="row g-2">
            @foreach ($featured as $f)
                <div class="col-md-4">
                    <a href="{{ $f->url }}" class="d-block position-relative rounded overflow-hidden" style="aspect-ratio:3/4">
                        <img src="{{ $f->image_url }}" alt="{{ $f->name }}" class="w-100 h-100" style="object-fit:cover">
                        <span class="position-absolute bottom-0 start-0 end-0 p-3 text-white fw-semibold" style="background:linear-gradient(transparent,rgba(0,0,0,.7))">{{ $f->name }} — @money($f->currentPrice())</span>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
