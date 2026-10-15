@extends('layouts.store')
@section('title', 'Payment — '.$order->order_number)

@section('content')
<section class="section pt-2">
    <div class="container" style="max-width:900px">
        <h1 class="h4 fw-bold mb-1"><i class="fa-solid fa-credit-card text-brand"></i> Payment</h1>
        <p class="small text-muted">Order <strong>{{ $order->order_number }}</strong> · Payable <strong class="text-brand">@money($order->payable)</strong></p>

        @if ($errors->any())
            <div class="alert alert-danger small"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <form method="POST" action="{{ route('store.payment.pay', [$store, $order]) }}" class="pd-box">
                    @csrf
                    <h6 class="fw-bold mb-2">Select Payment Method</h6>
                    @php($selected = old('payment_method', $order->payment_method))
                    @include('store._methods', ['methods' => $methods, 'selected' => $selected])

                    @foreach (['bkash' => $store->bkash_number, 'nagad' => $store->nagad_number] as $m => $number)
                        @if ($number)
                            <div class="manual-box mt-3 {{ $selected === $m ? '' : 'd-none' }}" data-method="{{ $m }}">
                                <div class="info-box {{ $m === 'bkash' ? 'blue' : 'green' }} mb-3">
                                    <ol class="mb-0 ps-3">
                                        <li>আপনার {{ ucfirst($m) }} অ্যাপ থেকে <strong>Send Money</strong> করুন</li>
                                        <li>নম্বর: <strong class="fs-6">{{ $number }}</strong> <button type="button" class="btn btn-sm btn-link p-0" onclick="navigator.clipboard.writeText('{{ $number }}')">copy</button></li>
                                        <li>পরিমাণ: <strong>@money($order->payable)</strong> · Reference: <strong>{{ $order->order_number }}</strong></li>
                                        <li>নিচে আপনার নম্বর ও Transaction ID (TrxID) দিন</li>
                                    </ol>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6"><input name="sender_number" value="{{ old('sender_number') }}" class="form-control" placeholder="Your {{ ucfirst($m) }} number" disabled></div>
                                    <div class="col-md-6"><input name="transaction_id" value="{{ old('transaction_id') }}" class="form-control text-uppercase" placeholder="Transaction ID (TrxID)" disabled></div>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    <button class="btn btn-brand w-100 mt-4 py-2 fw-semibold" id="payBtn"><i class="fa-solid fa-lock"></i> <span>Pay @money($order->payable)</span></button>
                    <p class="small text-muted text-center mt-2 mb-0"><i class="fa-solid fa-shield-halved"></i> Secured by ShatajPay</p>
                </form>
            </div>
            <div class="col-lg-5">
                <div class="pd-box">
                    <strong>Order Summary</strong>
                    @foreach ($order->items as $item)
                        <div class="d-flex justify-content-between small py-2 border-bottom">
                            <span>{{ $item->product_name }} @if($item->variant_name)<span class="text-muted">({{ $item->variant_name }})</span>@endif × {{ $item->quantity }}</span>
                            <span>@money($item->total)</span>
                        </div>
                    @endforeach
                    <div class="summary-row mt-2"><span class="text-muted">Subtotal</span><span>@money($order->subtotal)</span></div>
                    <div class="summary-row"><span class="text-muted">Shipping</span><span>@money($order->shipping)</span></div>
                    <div class="summary-row"><span class="text-muted">Tax</span><span>@money($order->tax)</span></div>
                    <div class="summary-row total"><span>Total</span><span>@money($order->total)</span></div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const labels = {shatajpay: 'Pay with ShatajPay', bkash: 'Submit bKash Payment', nagad: 'Submit Nagad Payment', cod: 'Confirm Cash on Delivery'};
    const apply = m => {
        document.querySelectorAll('.manual-box').forEach(b => {
            const on = b.dataset.method === m;
            b.classList.toggle('d-none', !on);
            b.querySelectorAll('input').forEach(i => { i.disabled = !on; i.required = on; });
        });
        document.querySelector('#payBtn span').textContent = labels[m] || 'Pay';
    };
    document.addEventListener('paymethod', e => apply(e.detail));
    const checked = document.querySelector('#payMethods input:checked');
    if (checked) apply(checked.value);
})();
</script>
@endpush
