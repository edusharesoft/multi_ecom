@php
    $px = $store->option('fb_pixel_id');
    $ga = $store->option('ga4_id');
    $gtm = $store->option('gtm_id');
@endphp
@if ($gtm)
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer',@json($gtm));</script>
@endif
@if ($ga)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',@json($ga));</script>
@endif
@if ($px)
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',@json($px));fbq('track','PageView');</script>
@endif
<script>
// one helper for all trackers: track('AddToCart', {value, content_ids:[..]})
window.mbTrack = function (event, data) {
    data = Object.assign({currency: 'BDT'}, data || {});
    try { if (window.fbq) fbq('track', event, data); } catch (e) {}
    const ga = {ViewContent: 'view_item', AddToCart: 'add_to_cart', InitiateCheckout: 'begin_checkout', Purchase: 'purchase'}[event];
    try { if (window.gtag && ga) gtag('event', ga, {currency: 'BDT', value: data.value, transaction_id: data.order_id}); } catch (e) {}
    try { (window.dataLayer = window.dataLayer || []).push({event: ga || event, ecommerce: data}); } catch (e) {}
};
</script>
