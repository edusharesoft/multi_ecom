@if ($sec['image'])
<section class="section pt-0">
    <div class="container">
        <a href="{{ $sec['link'] ?: route('store.products', $store) }}" class="d-block rounded overflow-hidden position-relative">
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($sec['image']) }}" alt="{{ $sec['title'] }}" class="w-100" loading="lazy" style="max-height:420px;object-fit:cover">
            @if ($sec['title'])<span class="position-absolute bottom-0 start-0 p-3 text-white fw-bold fs-4" style="text-shadow:0 2px 6px rgba(0,0,0,.6)">{{ $sec['title'] }}</span>@endif
        </a>
    </div>
</section>
@endif
