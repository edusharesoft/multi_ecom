{{-- Pay an invoice: ShatajPay or manual bKash/Nagad/Bank. expects $invoice, $pay --}}
@php
    $amt = '৳ '.number_format($invoice->total, 2);
    $ref = e($invoice->number);
    $manualInfo = [
        'bkash' => 'আমাদের bKash নম্বরে <b>'.e($pay['bkash'] ?? '').'</b> Send Money করুন — '.$amt.', Reference: <b>'.$ref.'</b>',
        'nagad' => 'আমাদের Nagad নম্বরে <b>'.e($pay['nagad'] ?? '').'</b> Send Money করুন — '.$amt.', Reference: <b>'.$ref.'</b>',
        'bank' => nl2br(e($pay['bank'] ?? '')).'<br>Amount: '.$amt.' · Reference: <b>'.$ref.'</b>',
    ];
@endphp
<form method="POST" action="{{ route('manager.billing.pay', $invoice) }}" id="invoicePay">
    @csrf
    <div class="d-grid gap-2 mb-3">
        <label class="pay-option active"><input type="radio" name="method" value="shatajpay" class="d-none" checked>
            <span class="logo" style="background:linear-gradient(135deg,#16a34a,#e41e26)">SP</span>
            <span><span class="fw-semibold d-block">ShatajPay (Card / bKash / Nagad / Rocket)</span><small class="text-muted">অনলাইনে পেমেন্ট — সাথে সাথে প্ল্যান চালু</small></span></label>
        @foreach (['bkash' => ['bK', '#e2136e', 'bKash Send Money'], 'nagad' => ['Ng', '#f7941d', 'Nagad Send Money'], 'bank' => ['<i class="fa-solid fa-building-columns"></i>', '#1e3a8a', 'Bank Transfer']] as $m => [$logo, $color, $label])
            @if (! empty($pay[$m]))
                <label class="pay-option"><input type="radio" name="method" value="{{ $m }}" class="d-none">
                    <span class="logo" style="background:{{ $color }}">{!! $logo !!}</span>
                    <span><span class="fw-semibold d-block">{{ $label }}</span><small class="text-muted">Super Admin ভেরিফাই করার পর চালু হবে</small></span></label>
            @endif
        @endforeach
    </div>
    <div id="manualBox" class="d-none">
        <div class="info-box blue mb-2 small" id="manualInfo"></div>
        <div class="row g-2">
            <div class="col-md-6"><input name="sender_number" class="form-control" placeholder="যে নম্বর/অ্যাকাউন্ট থেকে পাঠিয়েছেন" disabled></div>
            <div class="col-md-6"><input name="transaction_id" class="form-control text-uppercase" placeholder="Transaction ID / Reference" disabled></div>
        </div>
    </div>
    <button class="btn btn-brand w-100 mt-3 py-2 fw-semibold"><i class="fa-solid fa-lock"></i> ৳ {{ number_format($invoice->total, 2) }} পরিশোধ করুন</button>
</form>
@push('scripts')
<script>
(() => {
    const info = @json($manualInfo);
    const form = document.getElementById('invoicePay'), box = document.getElementById('manualBox');
    form.querySelectorAll('input[name=method]').forEach(r => r.addEventListener('change', () => {
        form.querySelectorAll('.pay-option').forEach(o => o.classList.toggle('active', o.contains(r)));
        const manual = r.value !== 'shatajpay';
        box.classList.toggle('d-none', !manual);
        box.querySelectorAll('input').forEach(i => { i.disabled = !manual; i.required = manual; });
        if (manual) document.getElementById('manualInfo').innerHTML = info[r.value];
    }));
})();
</script>
@endpush
