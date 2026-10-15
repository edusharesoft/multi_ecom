@extends('layouts.auth')
@section('title', __('OTP Verification'))
@section('heading', __('Verify Your Mobile'))
@section('subheading')
    আমরা <strong>{{ substr($branch->mobile, 0, 3) }}*****{{ substr($branch->mobile, -3) }}</strong> নম্বরে একটি {{ config('shataj.otp.length') }}-সংখ্যার OTP পাঠিয়েছি।
@endsection
@section('auth_kind', 'register')

@section('content')
@if (session('dev_otp'))
    <div class="alert alert-warning small text-center py-2">
        <strong>Test mode:</strong> SMS gateway বন্ধ। আপনার OTP: <strong class="fs-5">{{ session('dev_otp') }}</strong>
    </div>
@endif

<form method="POST" action="{{ route('otp.verify') }}" id="otpForm">
    @csrf
    <input type="hidden" name="otp" id="otpValue">
    <div class="text-center mb-1"><i class="fa-solid fa-mobile-screen-button" style="font-size:40px;color:#6366f1"></i></div>
    <div class="au-otp">
        @for ($i = 0; $i < config('shataj.otp.length'); $i++)
            <input type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="OTP digit {{ $i + 1 }}">
        @endfor
    </div>
    @error('otp')<div class="au-err text-center" style="margin-top:0">{{ $message }}</div>@enderror
    <button class="au-btn"><i class="fa-solid fa-shield-halved"></i> {{ __('Verify & Go Live') }}</button>
</form>

<form method="POST" action="{{ route('otp.resend') }}" class="au-links">
    @csrf
    OTP পাননি?
    <button class="btn btn-link btn-sm p-0 align-baseline" id="resendBtn" @disabled($wait > 0)>Resend OTP <span id="timer">{{ $wait > 0 ? "({$wait}s)" : '' }}</span></button>
</form>

<div class="au-note text-center">ভেরিফাই হলে আপনার স্টোর সরাসরি লাইভ হবে: <code>{{ preg_replace('#^https?://#', '', $branch->url) }}</code></div>

<form method="POST" action="{{ route('logout') }}" class="text-center mt-2">@csrf
    <button class="btn btn-link btn-sm text-muted">{{ __('Logout') }}</button>
</form>
@endsection

@push('scripts')
<script>
(() => {
    const digits = [...document.querySelectorAll('.au-otp input')];
    digits[0].focus();
    digits.forEach((d, i) => {
        d.addEventListener('input', () => { d.value = d.value.replace(/\D/g, ''); if (d.value && digits[i + 1]) digits[i + 1].focus(); });
        d.addEventListener('keydown', e => { if (e.key === 'Backspace' && !d.value && digits[i - 1]) digits[i - 1].focus(); });
        d.addEventListener('paste', e => {
            const v = (e.clipboardData.getData('text') || '').replace(/\D/g, '');
            if (!v) return; e.preventDefault();
            digits.forEach((x, j) => x.value = v[j] || '');
            (digits[v.length] || digits[digits.length - 1]).focus();
        });
    });
    document.getElementById('otpForm').addEventListener('submit', () => {
        document.getElementById('otpValue').value = digits.map(d => d.value).join('');
    });
    let wait = {{ (int) $wait }};
    const btn = document.getElementById('resendBtn'), timer = document.getElementById('timer');
    if (wait > 0) {
        const iv = setInterval(() => {
            wait--; timer.textContent = wait > 0 ? `(${wait}s)` : '';
            if (wait <= 0) { btn.disabled = false; clearInterval(iv); }
        }, 1000);
    }
})();
</script>
@endpush
