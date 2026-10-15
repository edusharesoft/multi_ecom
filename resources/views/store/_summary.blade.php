<div class="pd-box">
    <strong>Order Summary</strong>
    <div class="summary-row mt-2"><span class="text-muted">Subtotal</span><span>@money($totals['subtotal'])</span></div>
    @if (($totals['discount'] ?? 0) > 0)<div class="summary-row text-success"><span>Discount @if($totals['coupon'] ?? null)<span class="badge bg-success">{{ $totals['coupon']->code }}</span>@endif</span><span>− @money($totals['discount'])</span></div>@endif
    <div class="summary-row"><span class="text-muted">Shipping</span><span>@money($totals['shipping'])</span></div>
    <div class="summary-row"><span class="text-muted">Tax / VAT</span><span>@money($totals['tax'])</span></div>
    <div class="summary-row total"><span>Total</span><span>@money($totals['total'])</span></div>
    @if (($totals['wallet'] ?? 0) > 0)
        <div class="summary-row text-success"><span>Wallet</span><span>− @money($totals['wallet'])</span></div>
        <div class="summary-row fw-bold"><span>Payable</span><span>@money($totals['payable'])</span></div>
    @endif
</div>
