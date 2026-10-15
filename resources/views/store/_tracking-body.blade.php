@if ($store->option('whatsapp_button') && $store->option('whatsapp_number'))
    @php $wa = '88'.ltrim(preg_replace('/^\+?88/', '', $store->option('whatsapp_number')), '+'); @endphp
    <a href="https://wa.me/{{ $wa }}?text={{ urlencode('হ্যালো '.$store->display_name.', আমি একটি পণ্য সম্পর্কে জানতে চাই।') }}" target="_blank" rel="noopener"
       style="position:fixed;right:18px;bottom:18px;z-index:1050;width:54px;height:54px;border-radius:50%;background:#25d366;color:#fff;display:flex;align-items:center;justify-content:center;font-size:28px;box-shadow:0 4px 12px rgba(0,0,0,.25)" title="WhatsApp">
        <i class="fa-brands fa-whatsapp"></i></a>
@endif
@if ($store->option('messenger_url'))
    <a href="{{ $store->option('messenger_url') }}" target="_blank" rel="noopener"
       style="position:fixed;right:18px;bottom:{{ $store->option('whatsapp_button') && $store->option('whatsapp_number') ? 82 : 18 }}px;z-index:1050;width:54px;height:54px;border-radius:50%;background:#0084ff;color:#fff;display:flex;align-items:center;justify-content:center;font-size:26px;box-shadow:0 4px 12px rgba(0,0,0,.25)" title="Messenger">
        <i class="fa-brands fa-facebook-messenger"></i></a>
@endif
@if ($store->option('ai_chat') && app(\App\Services\Ai\AiService::class)->availableFor($store))
    @include('store._chat')
@endif
<script>
document.addEventListener('submit', e => {
    const f = e.target;
    if (f.action && /\/cart\/add/.test(f.action) && window.mbTrack) {
        mbTrack('AddToCart', {value: parseFloat(f.dataset.price || 0) || undefined, content_ids: [f.querySelector('[name=product_id]')?.value].filter(Boolean), content_type: 'product'});
    }
    if (f.action && /\/checkout$/.test(f.action) && window.mbTrack) mbTrack('InitiateCheckout', {});
}, true);
</script>
