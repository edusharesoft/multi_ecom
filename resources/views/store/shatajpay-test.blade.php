<!doctype html>
<html lang="en">
<head>
    @include('partials.head')
    <title>ShatajPay — Test Payment</title>
</head>
<body class="auth-wrap">
<div class="auth-card text-center" style="max-width:440px">
    <div class="pay-logos justify-content-center mb-3"><span class="shataj fs-5 px-3 py-1">ShatajPay</span></div>
    <span class="badge bg-warning text-dark mb-3">TEST MODE</span>
    <p class="mb-1 text-muted small">Merchant</p>
    <h5 class="fw-bold">{{ $merchant }}</h5>
    <p class="mb-1 text-muted small">{{ data_get($payment->payload, 'ref', $payment->reference) }}</p>
    <div class="display-6 fw-bold text-brand my-3">৳ {{ number_format($payment->amount, 2) }}</div>
    <div class="d-grid gap-2">
        <a href="{{ $successUrl }}" class="btn btn-success">Simulate Successful Payment</a>
        <a href="{{ $failUrl }}" class="btn btn-outline-danger">Simulate Failed Payment</a>
        <a href="{{ $cancelUrl }}" class="btn btn-link text-muted">Cancel</a>
    </div>
    <p class="small text-muted mt-3 mb-0">লাইভ ShatajPay চালু করতে Super Admin → Payment & SMS Gateways এ Mode = Live ও credential দিন।</p>
</div>
</body>
</html>
