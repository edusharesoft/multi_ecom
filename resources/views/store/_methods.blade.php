{{-- Payment method radio cards. expects $methods, $selected --}}
@php
    $logos = [
        'shatajpay' => ['SP', 'linear-gradient(135deg,#16a34a,#e41e26)', 'Card, bKash, Nagad, Rocket — secure online payment'],
        'bkash' => ['bK', '#e2136e', 'Send Money to our bKash number, then submit the TrxID'],
        'nagad' => ['Ng', '#f7941d', 'Send Money to our Nagad number, then submit the TrxID'],
        'cod' => ['<i class="fa-solid fa-money-bill-wave"></i>', '#374151', 'Pay in cash when you receive the product'],
    ];
@endphp
<div class="d-grid gap-2" id="payMethods">
    @foreach ($methods as $key => $label)
        <label class="pay-option {{ $selected === $key ? 'active' : '' }}">
            <input type="radio" name="payment_method" value="{{ $key }}" class="d-none" @checked($selected === $key)>
            <span class="logo" style="background:{{ $logos[$key][1] }}">{!! $logos[$key][0] !!}</span>
            <span><span class="fw-semibold d-block">{{ $label }}</span><small class="text-muted">{{ $logos[$key][2] }}</small></span>
        </label>
    @endforeach
</div>
@push('scripts')
<script>
document.querySelectorAll('#payMethods input').forEach(r => r.addEventListener('change', () => {
    document.querySelectorAll('#payMethods .pay-option').forEach(o => o.classList.toggle('active', o.contains(r) && r.checked));
    document.dispatchEvent(new CustomEvent('paymethod', {detail: r.value}));
}));
</script>
@endpush
