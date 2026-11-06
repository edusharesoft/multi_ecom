@extends('layouts.auth')
@section('title', __('Create Your Store'))
@section('heading', __('Create Your Store'))
@section('subheading', __('Fill in the information below to open your online shop.'))
@section('auth_kind', 'register')
@section('auth_size', 'wide')
@section('auth_brand', 'platform')

@section('content')
@php
    $bad = fn ($name) => $errors->has($name);
    $base = preg_replace('#^https?://#', '', url('/'));
@endphp
<form method="POST" action="{{ route('register') }}" novalidate>
    @csrf
    <div>
        <div>
            <div @class(['au-field', 'is-invalid' => $bad('name')])><span class="ic"><i class="fa-regular fa-user"></i></span><input name="name" value="{{ old('name') }}" placeholder="{{ __('Your name') }} *" required></div>
            @error('name')<div class="au-err">{{ $message }}</div>@enderror
        </div>
        <div>
            <div @class(['au-field', 'is-invalid' => $bad('mobile')])><span class="ic"><i class="fa-solid fa-mobile-screen"></i></span><input name="mobile" value="{{ old('mobile') }}" placeholder="{{ __('Mobile') }} (01XXXXXXXXX) *" inputmode="tel" required></div>
            @error('mobile')<div class="au-err">{{ $message }}</div>@enderror
            @unless ($bad('mobile'))<div class="au-help">এই নম্বরে ভেরিফিকেশন OTP যাবে।</div>@endunless
        </div>
    </div>
    <div @class(['au-field', 'is-invalid' => $bad('email')])><span class="ic"><i class="fa-regular fa-envelope"></i></span><input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Email address') }} *" required></div>
    @error('email')<div class="au-err">{{ $message }}</div>@enderror

    <div @class(['au-field', 'is-invalid' => $bad('slug')])><span class="ic"><i class="fa-solid fa-globe"></i></span><span class="pre">{{ $base }}/</span><input name="slug" id="slug" value="{{ old('slug') }}" placeholder="your-shop-name *" autocomplete="off" autocapitalize="off" spellcheck="false" inputmode="url" required></div>
    @error('slug')<div class="au-err">{{ $message }}</div>@enderror
    <div class="au-help" id="slugHelp">ইংরেজি অক্ষর (a-z), সংখ্যা ও হাইফেন (-)। বাংলায় লিখলে নিজে থেকেই ইংরেজি হয়ে যাবে, যেমন "ঢাকা শপ" → dhaka-shop।</div>

    <div @class(['au-field', 'is-invalid' => $bad('address')])><span class="ic"><i class="fa-solid fa-location-dot"></i></span><textarea name="address" rows="2" placeholder="{{ __('Full address') }} *" required>{{ old('address') }}</textarea></div>
    @error('address')<div class="au-err">{{ $message }}</div>@enderror

    <div class="row g-2">
        <div class="col-sm-6">
            <div @class(['au-field', 'is-invalid' => $bad('password')])><span class="ic"><i class="fa-solid fa-lock"></i></span><input type="password" name="password" placeholder="{{ __('Password') }} *" autocomplete="new-password" required><button type="button" class="eye" tabindex="-1"><i class="fa-regular fa-eye-slash"></i></button></div>
            @error('password')<div class="au-err">{{ $message }}</div>@enderror
            @unless ($errors->has('password'))<div class="au-help">{{ __('Password (min 8)') }}</div>@endunless
        </div>
        <div class="col-sm-6">
            <div class="au-field"><span class="ic"><i class="fa-solid fa-lock"></i></span><input type="password" name="password_confirmation" placeholder="{{ __('Confirm password') }} *" autocomplete="new-password" required><button type="button" class="eye" tabindex="-1"><i class="fa-regular fa-eye-slash"></i></button></div>
        </div>
    </div>

    <div class="form-check mb-3">
        <input @class(['form-check-input', 'is-invalid' => $bad('terms')]) type="checkbox" name="terms" value="1" id="terms" @checked(old('terms'))>
        <label class="form-check-label" for="terms">I agree to the <a href="#">Terms &amp; Conditions</a> and <a href="#">Privacy Policy</a>.</label>
        @error('terms')<div class="au-err mt-1">{{ $message }}</div>@enderror
    </div>
    <button class="au-btn"><i class="fa-solid fa-store"></i> {{ __('Create My Store') }}</button>
    <a href="{{ route('login') }}" class="au-btn-2">{{ __('Already have an account? Login') }}</a>
    <div class="au-note"><i class="fa-solid fa-circle-info text-primary"></i> {{ __('Your online shop address will be') }}: <code id="previewUrl">{{ $base }}/{{ old('slug') ?: 'your-shop-name' }}</code></div>
</form>
@endsection

@push('scripts')
<script src="{{ asset('js/slugify-bn.js') }}?v=1"></script>
<script>
(() => {
    const input = document.getElementById('slug'), help = document.getElementById('slugHelp'), preview = document.getElementById('previewUrl');
    const base = @json(preg_replace('#^https?://#', '', url('/')));
    const hint = help.textContent;
    let t, composing = false;
    const run = () => {
        const raw = input.value;
        const clean = window.slugifyBn ? window.slugifyBn(raw, true) : raw.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
        if (clean !== raw) {
            input.value = clean;
            try { input.setSelectionRange(clean.length, clean.length); } catch (e) {}
        }
        preview.textContent = base + '/' + (clean.replace(/-+$/, '') || 'your-shop-name');
        clearTimeout(t);
        const slug = clean.replace(/^-+|-+$/g, '');
        if (slug.length < 3) { help.className = 'au-help'; help.textContent = slug.length ? 'কমপক্ষে ৩ অক্ষর।' : hint; return; }
        t = setTimeout(async () => {
            try {
                const r = await fetch(@json(route('register.check-slug')) + '?slug=' + encodeURIComponent(slug));
                const j = await r.json();
                help.className = 'au-help ' + (j.available ? 'text-success' : 'text-danger');
                help.textContent = j.available ? '✓ ' + base + '/' + j.slug + ' পাওয়া যাচ্ছে' : (j.valid ? '✗ এই URL টি আগেই নেওয়া হয়েছে, অন্য নাম দিন' : '✗ শুধু ইংরেজি অক্ষর, সংখ্যা ও হাইফেন');
            } catch (e) {}
        }, 350);
    };
    // Bangla / phonetic keyboards compose several keystrokes into one letter — wait until the letter is finished
    input.addEventListener('compositionstart', () => { composing = true; });
    input.addEventListener('compositionend', () => { composing = false; run(); });
    input.addEventListener('input', () => { if (!composing) run(); });
    input.addEventListener('blur', () => { input.value = input.value.replace(/^-+|-+$/g, ''); run(); });
    if (input.value) run();
})();
</script>
@endpush
