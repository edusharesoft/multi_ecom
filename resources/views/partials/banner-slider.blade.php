{{--
    Image-only banner slideshow (main website + every store). Nothing is written on top of the picture.
    $slides : iterable of ['url' => image url, 'link' => optional click url, 'alt' => optional alt text]
    $sid    : unique DOM id
--}}
@php
    $slides = collect($slides ?? [])->filter(fn ($s) => ! empty($s['url']))->values();
    $sid = $sid ?? 'bannerSlider';
@endphp
@if ($slides->isNotEmpty())
    @once
        <style>
            .banner-slider{background:#f3f4f6}
            .banner-slider .bs-img{display:block;width:100%;aspect-ratio:16/5;object-fit:cover;background:#e5e7eb}
            @media (max-width:576px){.banner-slider .bs-img{aspect-ratio:16/7}}
            .banner-slider .carousel-indicators [data-bs-target]{width:10px;height:10px;border-radius:50%;border:0;margin:0 4px}
        </style>
    @endonce
    <div id="{{ $sid }}" class="carousel slide banner-slider" data-bs-ride="carousel" data-bs-interval="5000">
        @if ($slides->count() > 1)
            <div class="carousel-indicators">
                @foreach ($slides as $i => $s)<button type="button" data-bs-target="#{{ $sid }}" data-bs-slide-to="{{ $i }}" @class(['active' => $i === 0]) aria-label="Slide {{ $i + 1 }}"></button>@endforeach
            </div>
        @endif
        <div class="carousel-inner">
            @foreach ($slides as $i => $s)
                <div @class(['carousel-item', 'active' => $i === 0])>
                    @if (! empty($s['link']))
                        <a href="{{ $s['link'] }}"><img src="{{ $s['url'] }}" alt="{{ $s['alt'] ?? '' }}" class="bs-img" @if ($i > 0) loading="lazy" @endif></a>
                    @else
                        <img src="{{ $s['url'] }}" alt="{{ $s['alt'] ?? '' }}" class="bs-img" @if ($i > 0) loading="lazy" @endif>
                    @endif
                </div>
            @endforeach
        </div>
        @if ($slides->count() > 1)
            <button class="carousel-control-prev" type="button" data-bs-target="#{{ $sid }}" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span><span class="visually-hidden">Previous</span></button>
            <button class="carousel-control-next" type="button" data-bs-target="#{{ $sid }}" data-bs-slide="next"><span class="carousel-control-next-icon"></span><span class="visually-hidden">Next</span></button>
        @endif
    </div>
@endif
