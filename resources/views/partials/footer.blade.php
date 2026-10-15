<footer class="site-footer">
    <div class="container">
        <div class="footer-tiles">
            <a href="{{ isset($store) ? route('store.home', $store) : route('home') }}" class="footer-tile" style="background:linear-gradient(135deg,#f472b6,#fb923c)"><i class="fa-solid fa-store"></i><span>Our Store</span></a>
            <a href="{{ isset($store) ? route('store.products', $store) : route('home').'#arrivals' }}" class="footer-tile" style="background:linear-gradient(135deg,#6366f1,#ec4899)"><i class="fa-solid fa-shirt"></i><span>Fashion Collection</span></a>
            <a href="{{ isset($store) ? route('store.track', $store) : route('home').'#how' }}" class="footer-tile" style="background:linear-gradient(135deg,#0ea5e9,#22c55e)"><i class="fa-solid fa-headset"></i><span>Service Team</span></a>
            <a href="{{ isset($store) && $store->map_url ? $store->map_url : route('home').'#branches' }}" class="footer-tile" style="background:linear-gradient(135deg,#f59e0b,#ef4444)" @if(isset($store) && $store->map_url) target="_blank" rel="noopener" @endif><i class="fa-solid fa-location-dot"></i><span>Visit Our Store</span></a>
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>&copy; {{ date('Y') }} {{ isset($store) ? $store->display_name : \App\Models\Setting::siteName() }}. All rights reserved. | {{ \App\Models\Setting::get('footer_text') }}</div>
            <div class="pay-logos">
                <span class="shataj">ShatajPay</span><span>VISA</span><span>Mastercard</span><span style="color:#e2136e">bKash</span><span style="color:#f7941d">Nagad</span><span style="color:#8b1a8f">Rocket</span><span>COD</span>
            </div>
        </div>
    </div>
</footer>
