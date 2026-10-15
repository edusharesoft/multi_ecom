@extends('layouts.admin')
@section('title', 'Settings & Gateways')

@section('content')
<div class="row g-4">
    <div class="col-xl-7">
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="panel">
            @csrf @method('PUT')
            <div class="panel-head">Main Website (Landing Page)</div>
            <div class="panel-body row g-3">
                <div class="col-md-6"><label class="form-label small fw-semibold">Site Name</label><input name="site_name" value="{{ $s['site_name'] }}" class="form-control" placeholder="{{ config('app.name') }}"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Default language (বাংলা / English)</label><select name="default_locale" class="form-select">@foreach (\App\Http\Middleware\SetLocale::LOCALES as $k => $l)<option value="{{ $k }}" @selected((\App\Models\Setting::get('default_locale') ?: config('app.locale')) === $k)>{{ $l }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Phone</label><input name="site_phone" value="{{ $s['site_phone'] }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Email</label><input type="email" name="site_email" value="{{ $s['site_email'] }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Address</label><input name="site_address" value="{{ $s['site_address'] }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Hero Title</label><input name="hero_title" value="{{ $s['hero_title'] }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Hero Badge</label><input name="hero_badge" value="{{ $s['hero_badge'] }}" class="form-control"></div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Hero Image (1600×500) <span class="fw-normal text-muted">— শুধু তখনই দেখাবে যখন <a href="{{ route('admin.sliders.index') }}">Home Slider</a>-এ কোনো ছবি নেই</span></label>
                    @if ($s['hero_image'])<img src="{{ asset('storage/'.$s['hero_image']) }}" class="d-block rounded mb-2" style="height:60px">@endif
                    <input type="file" name="hero_image" accept="image/*" class="form-control">
                </div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Promo Title</label><input name="promo_title" value="{{ $s['promo_title'] }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Facebook URL</label><input name="facebook_url" value="{{ $s['facebook_url'] }}" class="form-control"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Promo Text</label><input name="promo_text" value="{{ $s['promo_text'] }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Footer Text</label><input name="footer_text" value="{{ $s['footer_text'] }}" class="form-control"></div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="allow_registration" value="1" id="reg" @checked($s['allow_registration'])><label class="form-check-label small" for="reg">নতুন ব্রাঞ্চ রেজিস্ট্রেশন চালু</label></div>
                </div>
            </div>
            <div class="panel-head border-top"><span><i class="fa-solid fa-shoe-prints text-primary"></i> Footer (মেইন ওয়েবসাইট)</span></div>
            <div class="panel-body row g-3">
                <div class="col-12"><label class="form-label small fw-semibold">About text (ফুটারে লোগোর নিচে)</label>
                    <textarea name="footer_about" rows="2" maxlength="400" class="form-control">{{ old('footer_about', $s['footer_about'] ?? '') }}</textarea></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Opening hours</label><input name="footer_hours" value="{{ old('footer_hours', $s['footer_hours'] ?? '') }}" class="form-control" placeholder="শনি–বৃহস্পতি, সকাল ১০টা – রাত ৯টা"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">WhatsApp (নম্বর বা লিংক)</label><input name="footer_whatsapp" value="{{ old('footer_whatsapp', $s['footer_whatsapp'] ?? '') }}" class="form-control" placeholder="01XXXXXXXXX"></div>
                @foreach (['footer_instagram' => 'Instagram', 'footer_youtube' => 'YouTube', 'footer_tiktok' => 'TikTok'] as $k => $l)
                    <div class="col-md-4"><label class="form-label small fw-semibold">{{ $l }} URL</label><input name="{{ $k }}" value="{{ old($k, $s[$k] ?? '') }}" class="form-control" placeholder="https://"></div>
                @endforeach
                <div class="col-md-7"><label class="form-label small fw-semibold">Quick links (প্রতি লাইনে একটি: <code>নাম | লিংক</code>)</label>
                    <textarea name="footer_links" rows="5" class="form-control font-monospace small" placeholder="About Us | /about&#10;Privacy Policy | https://example.com/privacy">{{ old('footer_links', \App\Support\SiteFooter::linksToText(\App\Support\SiteFooter::platformLinks())) }}</textarea>
                    <div class="form-text">খালি রাখলে Home, Our Branches, How to Buy, Open a Branch দেখাবে। সর্বোচ্চ {{ \App\Support\SiteFooter::MAX_LINKS }}টি।</div></div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Copyright text</label><input name="footer_copyright" value="{{ old('footer_copyright', $s['footer_copyright'] ?? '') }}" class="form-control mb-1" placeholder="© {{ date('Y') }} {{ \App\Models\Setting::siteName() }}. All rights reserved.">
                    <div class="form-text mb-2">ঠিকানা, ফোন, ইমেইল, Facebook ও "Developed by" লেখা উপরের ঘরগুলো থেকে আসে।</div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="footer_show_tiles" value="1" id="fst" @checked(($s['footer_show_tiles'] ?? '1') !== '0')><label class="form-check-label small" for="fst">রঙিন ৪টি বক্স দেখাবে</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="footer_show_pay" value="1" id="fsp" @checked(($s['footer_show_pay'] ?? '1') !== '0')><label class="form-check-label small" for="fsp">পেমেন্ট লোগো দেখাবে</label></div>
                </div>
            </div>
            <div class="panel-head border-top"><span><i class="fa-solid fa-image text-primary"></i> Logo & Login / Registration Page</span></div>
            <div class="panel-body row g-3">
                @php
                    $img = fn ($key, $label, $hint) => ['key' => $key, 'label' => $label, 'hint' => $hint, 'path' => $s[$key] ?? null];
                @endphp
                @foreach ([
                    $img('site_logo', 'Platform Logo', 'PNG / WebP, transparent, about 400×120 — main website header, login pages, admin panel (stores with their own logo show theirs)'),
                    $img('auth_login_bg', 'Login page background', 'wide photo, 1920×1080, max 4 MB'),
                    $img('auth_register_bg', 'Registration page background', 'empty = same as login'),
                ] as $f)
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">{{ $f['label'] }}</label>
                        @if ($f['path'])
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <img src="{{ asset('storage/'.$f['path']) }}" class="rounded border" style="height:54px;max-width:140px;object-fit:{{ $f['key'] === 'site_logo' ? 'contain' : 'cover' }};background:#f3f4f6">
                                <div class="form-check small"><input class="form-check-input" type="checkbox" name="remove_{{ $f['key'] }}" value="1" id="rm_{{ $f['key'] }}"><label class="form-check-label text-danger" for="rm_{{ $f['key'] }}">Remove</label></div>
                            </div>
                        @endif
                        <input type="file" name="{{ $f['key'] }}" accept="image/*" class="form-control form-control-sm">
                        <div class="form-text">{{ $f['hint'] }}</div>
                    </div>
                @endforeach
                <div class="col-md-6"><label class="form-label small fw-semibold"><i class="fa-brands fa-youtube text-danger"></i> Login page YouTube video</label>
                    <input name="auth_login_video" value="{{ old('auth_login_video', $s['auth_login_video'] ?? '') }}" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
                    @if ($id = \App\Support\AuthTheme::youtubeId($s['auth_login_video'] ?? ''))<div class="form-text text-success">✓ Video ID: {{ $id }}</div>@endif</div>
                <div class="col-md-6"><label class="form-label small fw-semibold"><i class="fa-brands fa-youtube text-danger"></i> Registration page YouTube video</label>
                    <input name="auth_register_video" value="{{ old('auth_register_video', $s['auth_register_video'] ?? '') }}" class="form-control" placeholder="empty = same as login">
                    @if ($id = \App\Support\AuthTheme::youtubeId($s['auth_register_video'] ?? ''))<div class="form-text text-success">✓ Video ID: {{ $id }}</div>@endif</div>
                <div class="col-12 d-flex flex-wrap gap-3 align-items-center">
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="auth_show_video" value="1" id="asv" @checked(($s['auth_show_video'] ?? '1') !== '0')><label class="form-check-label small" for="asv">লগইন / রেজিস্ট্রেশন পেইজে ভিডিও দেখাবে</label></div>
                    <a href="{{ route('login') }}" target="_blank" class="small"><i class="fa-solid fa-arrow-up-right-from-square"></i> Login page দেখুন</a>
                    <a href="{{ route('register') }}" target="_blank" class="small"><i class="fa-solid fa-arrow-up-right-from-square"></i> Registration page দেখুন</a>
                </div>
            </div>
            <div class="panel-head border-top">SaaS Billing & Automation</div>
            <div class="panel-body row g-3">
                <div class="col-md-4"><label class="form-label small fw-semibold">Grace period (days)</label><input type="number" min="0" name="grace_days" value="{{ old('grace_days', $s['grace_days']) }}" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">After grace period</label>
                    <select name="expiry_action" class="form-select">
                        @foreach (['downgrade' => 'Auto-downgrade to Free plan', 'readonly' => 'Read-only mode (store open, no new orders)', 'suspend' => 'Suspend account'] as $k => $v)
                            <option value="{{ $k }}" @selected(old('expiry_action', $s['expiry_action']) === $k)>{{ $v }}</option>
                        @endforeach
                    </select></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Reminder days before expiry</label><input name="reminder_days" value="{{ old('reminder_days', $s['reminder_days']) }}" class="form-control" placeholder="7,3,1,0" required></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Subscription bKash number</label><input name="saas_bkash_number" value="{{ old('saas_bkash_number', $s['saas_bkash_number']) }}" class="form-control" placeholder="empty = not offered"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Subscription Nagad number</label><input name="saas_nagad_number" value="{{ old('saas_nagad_number', $s['saas_nagad_number']) }}" class="form-control" placeholder="empty = not offered"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Bank transfer details</label><textarea name="saas_bank_details" rows="2" class="form-control" placeholder="Bank, A/C name, A/C no, branch, routing">{{ old('saas_bank_details', $s['saas_bank_details']) }}</textarea></div>
                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sms_notifications" value="1" id="smsn" @checked($s['sms_notifications'])><label class="form-check-label small" for="smsn">Expiry / grace reminders by SMS (Shataj SMS)</label></div></div>
                <div class="col-12 small text-muted">অটোমেশন চালাতে সার্ভারে <code>php artisan schedule:work</code> চালু রাখুন (অথবা প্রতি মিনিটে <code>php artisan schedule:run</code> cron)। Subscriptions পেজ থেকে "Run automation now" দিয়েও চালানো যায়।</div>
            </div>
            <div class="panel-body border-top text-end"><button class="btn btn-primary px-4">Save</button></div>
        </form>
    </div>

    <div class="col-xl-5">
        <div class="panel mb-3">
            <div class="panel-head">ShatajPay &amp; Shataj SMS</div>
            <div class="panel-body small">
                <div>ShatajPay: <span class="badge bg-{{ $pay['mode'] === 'live' ? 'success' : ($pay['mode'] === 'sandbox' ? 'info' : 'warning text-dark') }}">{{ strtoupper($pay['mode']) }}</span>
                    · SMS: <span class="badge bg-{{ $sms['driver'] === 'shataj' && filled($sms['api_key']) ? 'success' : 'warning text-dark' }}">{{ $sms['driver'] === 'shataj' ? 'LIVE' : 'LOG' }}</span></div>
                <div class="text-muted mt-1">Credential, callback URL, test SMS ও ব্যালেন্স এখন আলাদা পেজে।</div>
                <a href="{{ route('admin.gateways') }}" class="btn btn-sm btn-dark mt-2"><i class="fa-solid fa-credit-card"></i> Payment &amp; SMS Gateways</a>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.settings.account') }}" class="panel">
            @csrf @method('PUT')
            <div class="panel-head">My Admin Account</div>
            <div class="panel-body">
                <div class="mb-2"><input name="name" value="{{ auth('web')->user()->name }}" class="form-control form-control-sm" required></div>
                <div class="mb-2"><input type="email" name="email" value="{{ auth('web')->user()->email }}" class="form-control form-control-sm" required></div>
                <div class="mb-2"><input type="password" name="password" class="form-control form-control-sm" placeholder="New password (optional)" autocomplete="new-password"></div>
                <div class="mb-2"><input type="password" name="password_confirmation" class="form-control form-control-sm" placeholder="Confirm new password"></div>
                <div class="mb-3"><input type="password" name="current_password" class="form-control form-control-sm" placeholder="Current password *" required></div>
                <button class="btn btn-sm btn-dark w-100">Update Account</button>
            </div>
        </form>
    </div>
</div>
@endsection
