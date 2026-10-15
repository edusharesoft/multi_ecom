@extends($layout)
@section('title', __('Security — Two-factor authentication'))

@section('content')
<div class="row justify-content-center"><div class="col-xl-7">
    <div class="panel mb-3">
        <div class="panel-head"><span><i class="fa-solid fa-shield-halved"></i> Two-factor authentication (2FA)</span>
            <span class="badge bg-{{ $enabled ? 'success' : 'secondary' }}">{{ $enabled ? __('Enabled') : __('Disabled') }}</span></div>
        <div class="panel-body small">
            <p>লগইনের সময় পাসওয়ার্ডের পর ফোনের Authenticator অ্যাপের (Google Authenticator, Microsoft Authenticator, Authy) ৬ অঙ্কের কোড লাগবে — পাসওয়ার্ড চুরি হলেও অ্যাকাউন্ট নিরাপদ থাকবে।</p>

            @if (session('recovery_codes'))
                <div class="alert alert-warning">
                    <strong>Recovery codes — এখনই লিখে বা প্রিন্ট করে রাখুন।</strong> ফোন হারালে এগুলোর একটি দিয়ে লগইন করা যাবে (প্রতিটি একবার)।
                    <div class="row g-1 mt-2 font-monospace">@foreach (session('recovery_codes') as $c)<div class="col-6">{{ $c }}</div>@endforeach</div>
                </div>
            @endif

            @if ($enabled)
                <p class="mb-2">চালু আছে {{ $user->two_factor_confirmed_at?->format('d M Y') }} থেকে · বাকি Recovery code: <strong>{{ $codesLeft }}</strong></p>
                <div class="row g-3">
                    <form method="POST" action="{{ route($prefix.'.recovery') }}" class="col-md-6">@csrf
                        <label class="form-label mb-0">Password</label>
                        <input type="password" name="password" class="form-control form-control-sm mb-2" required>
                        <button class="btn btn-sm btn-outline-dark">নতুন Recovery code তৈরি করুন</button>
                    </form>
                    <form method="POST" action="{{ route($prefix.'.disable') }}" class="col-md-6" onsubmit="return confirm('2FA বন্ধ করবেন?')">@csrf
                        <label class="form-label mb-0">Password</label>
                        <input type="password" name="password" class="form-control form-control-sm mb-2" required>
                        <button class="btn btn-sm btn-outline-danger">2FA বন্ধ করুন</button>
                    </form>
                </div>
                @error('password')<div class="text-danger mt-2">{{ $message }}</div>@enderror
            @else
                <ol class="ps-3">
                    <li>ফোনে Authenticator অ্যাপ খুলে নিচের QR কোড স্ক্যান করুন (অথবা Key টি টাইপ করুন)।</li>
                    <li>অ্যাপে দেখানো ৬ অঙ্কের কোড ও আপনার পাসওয়ার্ড দিয়ে “Enable” চাপুন।</li>
                </ol>
                <div class="d-flex gap-4 align-items-center flex-wrap mb-3">
                    <div id="qr" class="bg-white p-2 border rounded"></div>
                    <div><div class="text-muted">Setup key</div><code class="fs-6" style="word-break:break-all">{{ trim(chunk_split($secret, 4, ' ')) }}</code></div>
                </div>
                <form method="POST" action="{{ route($prefix.'.enable') }}" class="row g-2">@csrf
                    <div class="col-md-4"><input name="code" class="form-control @error('code') is-invalid @enderror" inputmode="numeric" placeholder="6-digit code" required>@error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-5"><input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Current password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-3"><button class="btn btn-brand w-100">Enable</button></div>
                </form>
            @endif
        </div>
    </div>
    @if ($prefix === 'manager.security' && \App\Support\Tenancy::isOwner())
    <div class="panel"><div class="panel-body small d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <span><strong>Export my store data</strong><br><span class="text-muted">পণ্য, কাস্টমার, অর্ডার, সাপ্লায়ার, হিসাব, কর্মচারী — সব CSV (zip) আকারে ডাউনলোড।</span></span>
        <form method="POST" action="{{ route('manager.export') }}">@csrf<button class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-file-zipper"></i> Download</button></form>
    </div></div>
    @endif
</div></div>
@endsection

@if ($uri)
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>new QRCode(document.getElementById('qr'), {text: @json($uri), width: 180, height: 180, correctLevel: QRCode.CorrectLevel.M});</script>
@endpush
@endif
