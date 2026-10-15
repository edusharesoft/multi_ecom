<!doctype html>
<html><head><meta charset="utf-8"><title>Labels</title>
<style>
body{font-family:Arial,sans-serif;margin:10px}
.grid{display:flex;flex-wrap:wrap;gap:8px}
.label{width:190px;border:1px dashed #bbb;padding:6px;text-align:center;font-size:11px;page-break-inside:avoid}
.label .n{font-weight:bold;height:28px;overflow:hidden}
.label .p{font-size:14px;font-weight:bold}
.qr{display:inline-block}
@media print{.noprint{display:none}.label{border-color:#eee}}
</style></head>
<body>
<button class="noprint" onclick="window.print()" style="margin-bottom:10px">Print</button>
<div class="grid">
@foreach ($products as $p)
    @for ($i = 0; $i < $copies; $i++)
        <div class="label">
            <div class="n">{{ \Illuminate\Support\Str::limit($p->name, 48) }}</div>
            <div class="p">৳ {{ number_format($p->currentPrice(), 2) }}</div>
            @if ($p->barcode)<svg class="bc" data-code="{{ $p->barcode }}"></svg>@else<div style="color:#999;padding:8px 0">No barcode</div>@endif
            <div class="qr" data-url="{{ $p->url }}"></div>
            <div>{{ $p->sku ?: $p->product_code }}</div>
        </div>
    @endfor
@endforeach
</div>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.querySelectorAll('.bc').forEach(el => JsBarcode(el, el.dataset.code, {height: 38, fontSize: 11, margin: 2, width: 1.4}));
document.querySelectorAll('.qr').forEach(el => new QRCode(el, {text: el.dataset.url, width: 56, height: 56}));
</script>
</body></html>
