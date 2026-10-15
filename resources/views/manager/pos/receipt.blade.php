@php $b = $order->branch; @endphp
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<title>Receipt {{ $order->order_number }}</title>
<style>
    @page { size: 80mm auto; margin: 0; }
    * { box-sizing: border-box; }
    body { width: 80mm; margin: 0 auto; padding: 4mm; font: 12px/1.35 'Courier New', monospace; color: #000; }
    .c { text-align: center; } .r { text-align: right; } .b { font-weight: bold; }
    hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
    table { width: 100%; border-collapse: collapse; } td { vertical-align: top; padding: 1px 0; }
    .big { font-size: 15px; }
    .no-print { margin-top: 10px; text-align: center; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body onload="window.print()">
    <div class="c">
        @if ($b->logo_url)<img src="{{ $b->logo_url }}" alt="" style="max-height:40px"><br>@endif
        <div class="b big">{{ $b->store_name ?: $b->name }}</div>
        @if ($b->address)<div>{{ $b->address }}</div>@endif
        <div>{{ $b->store_phone ?: $b->mobile }}</div>
    </div>
    <hr>
    <div>Invoice: <span class="b">{{ $order->order_number }}</span></div>
    <div>Date: {{ $order->created_at->format('d/m/Y h:i A') }}</div>
    @if ($order->staff_name)<div>Cashier: {{ $order->staff_name }}</div>@endif
    <div>Customer: {{ $order->customer_name }} {{ $order->customer_phone }}</div>
    <hr>
    <table>
        @foreach ($order->items as $i)
            <tr><td colspan="2">{{ $i->product_name }}{{ $i->variant_name ? ' ('.$i->variant_name.')' : '' }}</td></tr>
            <tr><td>&nbsp; {{ $i->quantity }} x {{ number_format($i->price, 2) }}@if($i->returned_qty) <span>[ret {{ $i->returned_qty }}]</span>@endif</td><td class="r">{{ number_format($i->total, 2) }}</td></tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td>Subtotal</td><td class="r">{{ number_format($order->subtotal, 2) }}</td></tr>
        @if ($order->discount > 0)<tr><td>Discount</td><td class="r">-{{ number_format($order->discount, 2) }}</td></tr>@endif
        @if ($order->tax > 0)<tr><td>VAT/Tax</td><td class="r">{{ number_format($order->tax, 2) }}</td></tr>@endif
        <tr class="b big"><td>TOTAL</td><td class="r">{{ number_format($order->total, 2) }}</td></tr>
        <tr><td>Paid ({{ \App\Models\Order::METHOD_LABELS[$order->payment_method] ?? $order->payment_method }})</td><td class="r">{{ number_format($order->tendered ?? $order->total, 2) }}</td></tr>
        @if ($order->change_due > 0)<tr><td>Change</td><td class="r">{{ number_format($order->change_due, 2) }}</td></tr>@endif
        @if ($order->refunded_amount > 0)<tr><td>Refunded</td><td class="r">-{{ number_format($order->refunded_amount, 2) }}</td></tr>@endif
    </table>
    @if ($order->customer && $order->points_earned)<hr><div class="c">Points earned: {{ $order->points_earned }} · Balance: {{ $order->customer->loyalty_points }}</div>@endif
    <hr>
    <div class="c">ধন্যবাদ! আবার আসবেন।<br><svg id="bc"></svg></div>
    <div class="no-print"><button onclick="window.print()">Print</button> <button onclick="window.close()">Close</button></div>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>try { JsBarcode('#bc', @json($order->order_number), {height: 35, width: 1.4, fontSize: 11, margin: 0}); } catch (e) {}</script>
</body>
</html>
