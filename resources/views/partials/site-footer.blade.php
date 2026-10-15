{{--
    Footer for the main website and every store.  $f = App\Support\SiteFooter::platform() / ::store(...)
--}}
@php
    $socialIcons = ['facebook' => 'fa-facebook-f', 'instagram' => 'fa-instagram', 'youtube' => 'fa-youtube', 'tiktok' => 'fa-tiktok', 'whatsapp' => 'fa-whatsapp'];
    $external = fn ($u) => \Illuminate\Support\Str::startsWith($u, ['http://', 'https://']) && ! \Illuminate\Support\Str::startsWith($u, url('/'));
@endphp
@once
<style>
    .site-footer .ft-main{padding:22px 0 8px;border-bottom:1px solid #1f2937;margin-bottom:12px;font-size:13px}
    .site-footer h6{color:#fff;font-weight:700;font-size:14px;margin-bottom:12px}
    .site-footer a{color:#cbd5e1;text-decoration:none}
    .site-footer a:hover{color:#fff}
    .site-footer .ft-logo{max-height:48px;max-width:180px;background:#fff;border-radius:6px;padding:4px 8px;margin-bottom:10px}
    .site-footer .ft-links li{margin-bottom:6px}
    .site-footer .ft-contact li{display:flex;gap:8px;margin-bottom:8px}
    .site-footer .ft-contact i{color:var(--brand,#e41e26);margin-top:3px;width:14px}
    .site-footer .ft-social a{width:34px;height:34px;border-radius:50%;background:#1f2937;display:inline-flex;align-items:center;justify-content:center;margin:0 6px 6px 0}
    .site-footer .ft-social a:hover{background:var(--brand,#e41e26)}
</style>
@endonce
<footer class="site-footer" id="contact">
    <div class="container">
        @if ($f['show_tiles'])
            <div class="footer-tiles">
                @foreach ($f['tiles'] as $t)
                    <a href="{{ $t['url'] }}" class="footer-tile" style="background:{{ $t['bg'] }}" @if (! empty($t['blank'])) target="_blank" rel="noopener" @endif><i class="fa-solid {{ $t['icon'] }}"></i><span>{{ $t['label'] }}</span></a>
                @endforeach
            </div>
        @endif

        <div class="row g-4 ft-main">
            <div class="col-lg-4 col-md-6">
                @if ($f['logo'])<img src="{{ $f['logo'] }}" alt="{{ $f['name'] }}" class="ft-logo d-block">@else<h6>{{ $f['name'] }}</h6>@endif
                @if ($f['about'])<p class="mb-3">{!! nl2br(e($f['about'])) !!}</p>@endif
                @if ($f['socials'])
                    <div class="ft-social">
                        @foreach ($f['socials'] as $k => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" title="{{ ucfirst($k) }}" aria-label="{{ ucfirst($k) }}"><i class="fa-brands {{ $socialIcons[$k] }}"></i></a>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="col-lg-3 col-md-6 col-6">
                <h6>{{ __('Quick Links') }}</h6>
                <ul class="list-unstyled ft-links mb-0">
                    @foreach ($f['links'] as $l)
                        <li><a href="{{ $l['url'] }}" @if ($external($l['url'])) target="_blank" rel="noopener" @endif><i class="fa-solid fa-angle-right small me-1"></i>{{ $l['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="col-lg-5 col-md-12">
                <h6>{{ __('Contact Us') }}</h6>
                <ul class="list-unstyled ft-contact mb-0">
                    @if ($f['address'])<li><i class="fa-solid fa-location-dot"></i><span>{{ $f['address'] }}</span></li>@endif
                    @if ($f['phone'])<li><i class="fa-solid fa-phone"></i><a href="tel:{{ preg_replace('/[^\d+]/', '', $f['phone']) }}">{{ $f['phone'] }}</a></li>@endif
                    @if ($f['email'])<li><i class="fa-solid fa-envelope"></i><a href="mailto:{{ $f['email'] }}">{{ $f['email'] }}</a></li>@endif
                    @if ($f['hours'])<li><i class="fa-regular fa-clock"></i><span>{{ $f['hours'] }}</span></li>@endif
                </ul>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>{{ $f['copyright'] ?: '© '.date('Y').' '.$f['name'].'. '.__('All rights reserved.') }}@if ($f['credit']) <span class="mx-1">|</span> {{ $f['credit'] }}@endif</div>
            @if ($f['show_pay'])
                <div class="pay-logos">
                    <span class="shataj">ShatajPay</span><span>VISA</span><span>Mastercard</span><span style="color:#e2136e">bKash</span><span style="color:#f7941d">Nagad</span><span style="color:#8b1a8f">Rocket</span><span>COD</span>
                </div>
            @endif
        </div>
    </div>
</footer>
