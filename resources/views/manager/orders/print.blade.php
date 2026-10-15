<!doctype html>
<html lang="bn"><head><meta charset="utf-8">
<title>{{ ucfirst($type) }} {{ $order->order_number }}</title>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Hind Siliguri',Arial,sans-serif;font-size:13px;color:#111;margin:0;padding:20px}
.wrap{max-width:780px;margin:auto}
h1{font-size:20px;margin:0}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{border-bottom:1px solid #ddd;padding:6px;text-align:left}
.r{text-align:right}.muted{color:#666}.row{display:flex;justify-content:space-between;gap:20px}
.box{border:1px solid #ccc;padding:10px;border-radius:6px}
.label{width:380px;border:2px dashed #000;padding:14px;margin:auto}
.big{font-size:18px;font-weight:700}
@media print{.noprint{display:none}}
</style></head>
<body><div class="wrap">
<button class="noprint" onclick="window.print()" style="margin-bottom:10px">Print</button>
@php($b = $order->branch)

@if ($type === 'label')
    <div class="label">
        <div class="row"><strong>{{ $b->display_name }}</strong><span>{{ $order->order_number }}</span></div>
        <div class="muted">From: {{ $b->address }} · {{ $b->store_phone ?: $b->mobile }}</div>
        <hr>
        <div>TO:</div>
        <div class="big">{{ $order->customer_name }}</div>
        <div class="big">{{ $order->customer_phone }}</div>
        <div style="font-size:15px">{{ $order->shipping_address }}</div>
        <hr>
        <div class="row"><span>Items: {{ $order->items->sum('quantity') }}</span>
            <span class="big">{{ $order->isPaid() || $order->payable <= 0 ? 'PAID' : 'COD: ৳ '.number_format($order->payable, 2) }}</span></div>
        <svg id="bc"></svg>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>JsBarcode('#bc', @json($order->order_number), {height: 40, fontSize: 12, width: 1.5});</script>
@else
    <div class="row">
        <div><h1>{{ $b->display_name }}</h1><div class="muted">{{ $b->address }}<br>{{ $b->store_phone ?: $b->mobile }} · {{ $b->email }}</div></div>
        <div class="r"><h1>{{ $type === 'invoice' ? 'INVOICE / চালান' : 'PACKING SLIP' }}</h1><div>{{ $order->order_number }}<br>{{ $order->created_at->format('d M Y, h:i A') }}</div></div>
    </div>
    <div class="row" style="margin-top:14px">
        <div class="box" style="flex:1"><strong>Ship to / প্রাপক</strong><br>{{ $order->customer_name }}<br>{{ $order->customer_phone }}<br>{{ $order->shipping_address }}</div>
        @if ($type === 'invoice')<div class="box" style="flex:1"><strong>Bill to</strong><br>{{ $order->customer_name }}<br>{{ $order->billing_address ?: $order->shipping_address }}<br>Payment: {{ $order->method_label }} ({{ str_replace('_', ' ', $order->payment_status) }})</div>@endif
    </div>
    <table>
        <thead><tr><th>#</th><th>পণ্য / Item</th><th>Qty</th>@if($type === 'invoice')<th class="r">Price</th><th class="r">Total</th>@else<th>✓</th>@endif</tr></thead>
        <tbody>
        @foreach ($order->items as $i => $item)
            <tr><td>{{ $i + 1 }}</td><td>{{ $item->product_name }} @if($item->variant_name)<span class="muted">({{ $item->variant_name }})</span>@endif</td><td>{{ $item->quantity }}</td>
                @if($type === 'invoice')<td class="r">৳ {{ number_format($item->price, 2) }}</td><td class="r">৳ {{ number_format($item->total, 2) }}</td>@else<td>☐</td>@endif</tr>
        @endforeach
        </tbody>
        @if ($type === 'invoice')
            <tfoot>
                <tr><td colspan="4" class="r">Subtotal / উপমোট</td><td class="r">৳ {{ number_format($order->subtotal, 2) }}</td></tr>
                @if ($order->discount > 0)<tr><td colspan="4" class="r">Discount / ছাড়</td><td class="r">− ৳ {{ number_format($order->discount, 2) }}</td></tr>@endif
                <tr><td colspan="4" class="r">Delivery / ডেলিভারি</td><td class="r">৳ {{ number_format($order->shipping, 2) }}</td></tr>
                @if ($order->tax > 0)<tr><td colspan="4" class="r">VAT / ভ্যাট</td><td class="r">৳ {{ number_format($order->tax, 2) }}</td></tr>@endif
                <tr><td colspan="4" class="r"><strong>Total / মোট</strong></td><td class="r"><strong>৳ {{ number_format($order->total, 2) }}</strong></td></tr>
                @if ($order->wallet_used > 0)<tr><td colspan="4" class="r">Wallet</td><td class="r">− ৳ {{ number_format($order->wallet_used, 2) }}</td></tr>@endif
                <tr><td colspan="4" class="r">{{ $order->isPaid() ? 'Paid / পরিশোধিত' : 'Due / বাকি' }}</td><td class="r">৳ {{ number_format($order->isPaid() ? 0 : $order->payable, 2) }}</td></tr>
            </tfoot>
        @endif
    </table>
    @if ($order->note)<p><strong>Note:</strong> {{ $order->note }}</p>@endif
    <p class="muted" style="margin-top:30px;text-align:center">{{ $b->option('invoice_note') }}</p>
@endif
</div></body></html>
