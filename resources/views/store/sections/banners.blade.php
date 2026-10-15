@if ($banners->isNotEmpty())
<section class="section pt-0">
    <div class="container"><div class="row g-3">
        @foreach ($banners as $b)
            <div class="col-md-{{ 12 / $banners->count() }}">
                <a href="{{ $b->link ?: route('store.products', $store) }}" class="d-block rounded overflow-hidden position-relative" style="aspect-ratio:16/7;background:#eee url('{{ $b->image_url }}') center/cover">
                    @if ($b->title)<span class="position-absolute bottom-0 start-0 p-3 text-white fw-bold fs-5" style="text-shadow:0 2px 6px rgba(0,0,0,.6)">{{ $b->title }}<br><small class="fw-normal fs-6">{{ $b->subtitle }}</small></span>@endif
                </a>
            </div>
        @endforeach
    </div></div>
</section>
@endif
