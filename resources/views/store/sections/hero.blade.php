{{-- Store banner slideshow — images only, managed in Manager → Website → Slider & Banner. --}}
@include('partials.banner-slider', [
    'sid' => 'storeSlider',
    'slides' => $sliders->isNotEmpty()
        ? $sliders->map(fn ($s) => ['url' => $s->image_url, 'link' => $s->link, 'alt' => $s->title ?: $store->display_name])
        : [['url' => $store->banner_url, 'link' => null, 'alt' => $store->display_name]],
])
