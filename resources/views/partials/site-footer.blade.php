@php
    $socialIcons = ['facebook' => 'fa-facebook-f', 'instagram' => 'fa-instagram', 'youtube' => 'fa-youtube', 'tiktok' => 'fa-tiktok', 'whatsapp' => 'fa-whatsapp'];
    $external = fn ($u) => \Illuminate\Support\Str::startsWith($u, ['http://', 'https://']) && ! \Illuminate\Support\Str::startsWith($u, url('/'));
    $footerAbout = $f['about'] ?: __('Welcome to :name. Browse our products and get in touch if you need help.', ['name' => $f['name']]);
    $footerAddress = $f['address'] ?: __('Bangladesh');
    $footerPhone = $f['phone'] ?: '+880 1700-000000';
@endphp
@once
<style>
    .site-footer a{color:#cbd5e1;text-decoration:none}
    .site-footer a:hover{color:#fff}
    .site-footer .ft-logo{max-height:48px;max-width:180px;background:#fff;border-radius:6px;padding:4px 8px;margin-bottom:10px}
    .site-footer .ft-contact li{display:flex;gap:8px;margin-bottom:8px}
    .site-footer .ft-contact i{color:var(--brand,#e41e26);margin-top:3px;width:14px}
    .site-footer .ft-social a{width:34px;height:34px;border-radius:50%;background:#1f2937;display:inline-flex;align-items:center;justify-content:center;margin:0 6px 6px 0}
    .site-footer .ft-social a:hover{background:var(--brand,#e41e26)}
    .site-footer .footer-columns{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;padding:22px 0 16px;border-bottom:1px solid #1f2937;margin-bottom:12px}
    .site-footer .footer-column{min-width:0;padding:14px;border:1px solid #1f2937;border-radius:8px;background:#111318;display:flex;flex-direction:column}
    .site-footer{font-size:15px;line-height:1.65}
    .site-footer .footer-column h4{margin:0 0 12px;color:#fff;font-size:16px;font-weight:700}
    .site-footer .footer-column p{overflow-wrap:anywhere;font-size:15px}
    .site-footer .ft-map-wrap{position:relative;width:100%;height:auto;min-height:230px;flex:1;overflow:hidden;border-radius:8px}
    .site-footer .ft-map{position:absolute;inset:0;width:100%;height:100%;border:0}
    .site-footer .ft-video-wrap{position:relative;width:100%;height:auto;min-height:230px;flex:1;overflow:hidden;border-radius:8px;background:#000}
    .site-footer .ft-video{position:absolute;inset:0;width:100%;height:100%;border:0}
    .site-footer .ft-video-placeholder{min-height:110px;display:flex;align-items:center;justify-content:center;padding:16px;border:1px dashed #374151;border-radius:8px;text-align:center}
    .site-footer .ft-contact li{font-size:15px}
    .site-footer .footer-bottom{padding:14px 0 0;gap:12px;font-size:14px}
    @media (max-width: 991.98px){.site-footer .footer-columns{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media (max-width: 575.98px){.site-footer .footer-columns{grid-template-columns:minmax(0,1fr);gap:12px}.site-footer .footer-column{padding:12px}.site-footer .ft-map-wrap,.site-footer .ft-video-wrap{height:220px;min-height:220px;flex:none}.site-footer .footer-bottom{align-items:flex-start;flex-direction:column}}
.footer-column h4 {
    color: #6e6e6e !important;
    background-color: rgb(233 237 241) !important;
}
</style>
@endonce
<footer class="site-footer" id="contact">
    <div class="container">
        <div class="footer-columns">
            <section class="footer-column">
                <h4 class="border rounded-1 p-1 text-bg-secondary text-center">{{ __('About Us') }}</h4>
                @if ($f['logo'])<img src="{{ $f['logo'] }}" alt="{{ $f['name'] }}" class="ft-logo d-block">@else<div class="fw-bold mb-2">{{ $f['name'] }}</div>@endif
                <p class="mb-3">{!! nl2br(e($footerAbout)) !!}</p>
                @if ($f['socials'])
                    <div class="ft-social">
                        @foreach ($f['socials'] as $k => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" title="{{ ucfirst($k) }}" aria-label="{{ ucfirst($k) }}"><i class="fa-brands {{ $socialIcons[$k] }}"></i></a>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="footer-column">
                <h4 class="border rounded-1 p-1 text-bg-secondary text-center">{{ __('Tutorial Video') }}</h4>
                @if (! empty($f['video_url']))
                    <div class="ft-video-wrap">
                        <iframe class="ft-video" src="{{ $f['video_url'] }}" title="{{ __('Tutorial video') }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                    </div>
                @else
                    <div class="ft-video-placeholder small text-white-50">{{ __('Tutorial video coming soon.') }}</div>
                @endif
            </section>

            <section class="footer-column">
                <h4 class="border rounded-1 p-1 text-bg-secondary text-center">{{ __('Google Map') }}</h4>
                <div class="ft-map-wrap">
                    <iframe class="ft-map" src="{{ $f['map_src'] }}" title="{{ __('Map showing :address', ['address' => $footerAddress]) }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
            </section>

            <section class="footer-column">
                <h4 class="border rounded-1 p-1 text-bg-secondary text-center">{{ __('Contact Us') }}</h4>
                <ul class="list-unstyled ft-contact mb-0">
                    <li><i class="fa-solid fa-location-dot"></i><span>{{ $footerAddress }}</span></li>
                    <li><i class="fa-solid fa-phone"></i><a href="tel:{{ preg_replace('/[^\d+]/', '', $footerPhone) }}">{{ $footerPhone }}</a></li>
                    @if ($f['email'])<li><i class="fa-solid fa-envelope"></i><a href="mailto:{{ $f['email'] }}">{{ $f['email'] }}</a></li>@endif
                    @if ($f['hours'])<li><i class="fa-regular fa-clock"></i><span>{{ $f['hours'] }}</span></li>@endif
                </ul>
            </section>
        </div>

        <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center">
            <div>{{ $f['copyright'] ?: '© '.date('Y').' '.$f['name'].'. '.__('All rights reserved.') }}@if ($f['credit']) <span class="mx-1">|</span> {{ $f['credit'] }}@endif</div>
            @if ($f['show_pay'])
                <img src="{{ asset('images/pay.png') }}" alt="payment logo" class="img-fluid rounded" style="max-height:32px">    
            @endif
        </div>
    </div>
</footer>
