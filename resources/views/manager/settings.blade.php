@extends('layouts.manager')
@section('title', 'Setting')

@section('content')
<div class="row g-4">
    @perm('settings.view')
    <div class="col-xl-8">
        <form method="POST" action="{{ route('manager.settings.update') }}" enctype="multipart/form-data" class="panel">
            @csrf @method('PUT')
            <div class="panel-head">Store Settings</div>
            <div class="panel-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Store Name *</label><input name="store_name" value="{{ old('store_name', $branch->store_name) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Manager Name *</label><input name="name" value="{{ old('name', $branch->name) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Email *</label><input type="email" name="email" value="{{ old('email', $branch->email) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Mobile (verified)</label><input value="{{ $branch->mobile }}" class="form-control" disabled></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Store URL</label><input value="{{ url($branch->slug) }}" class="form-control" disabled></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Store Phone</label><input name="store_phone" value="{{ old('store_phone', $branch->store_phone) }}" class="form-control"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Address *</label><textarea name="address" rows="2" class="form-control" required>{{ old('address', $branch->address) }}</textarea></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Opening Hours</label><input name="opening_hours" value="{{ old('opening_hours', $branch->opening_hours) }}" class="form-control" placeholder="10:00 AM – 10:00 PM"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Google Map URL</label><input name="map_url" value="{{ old('map_url', $branch->map_url) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Facebook Page URL</label><input name="facebook_url" value="{{ old('facebook_url', $branch->facebook_url) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Store Status</label>
                        <select name="status" class="form-select">
                            <option value="active" @selected(old('status', $branch->status) === 'active')>Active (visible)</option>
                            <option value="inactive" @selected(old('status', $branch->status) === 'inactive')>Inactive (hidden)</option>
                        </select>
                    </div>

                    <div class="col-12"><hr class="my-1"><h6 class="fw-bold mt-2">Home Page</h6></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Banner Title</label><input name="hero_title" value="{{ old('hero_title', $branch->hero_title) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Banner Subtitle</label><input name="hero_subtitle" value="{{ old('hero_subtitle', $branch->hero_subtitle) }}" class="form-control"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">About</label><textarea name="about" rows="3" class="form-control">{{ old('about', $branch->about) }}</textarea></div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Logo</label>
                        @if ($branch->logo_url)<img src="{{ $branch->logo_url }}" class="d-block mb-2" style="height:48px">@endif
                        <input type="file" name="logo" accept="image/*" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Banner (1600×500)</label>
                        <img src="{{ $branch->banner_url }}" class="d-block mb-2 rounded" style="height:48px;width:150px;object-fit:cover">
                        <input type="file" name="banner" accept="image/*" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Favicon</label>
                        @if ($branch->favicon_url)<img src="{{ $branch->favicon_url }}" class="d-block mb-2" style="height:32px">@endif
                        <input type="file" name="favicon" accept=".png,.ico,.jpg,.jpeg,.webp" class="form-control">
                    </div>

                    <div class="col-12"><hr class="my-1"><h6 class="fw-bold mt-2">Payment & Delivery</h6></div>
                    <div class="col-md-6">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="shatajpay_enabled" value="1" id="sp" @checked(old('shatajpay_enabled', $branch->shatajpay_enabled))><label class="form-check-label small" for="sp">ShatajPay (online) চালু</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="cod_enabled" value="1" id="cod" @checked(old('cod_enabled', $branch->cod_enabled))><label class="form-check-label small" for="cod">Cash on Delivery চালু</label></div>
                    </div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">Shipping Charge (৳)</label><input type="number" step="0.01" min="0" name="shipping_charge" value="{{ old('shipping_charge', $branch->shipping_charge) }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">Tax (%)</label><input type="number" step="0.01" min="0" name="tax_percent" value="{{ old('tax_percent', $branch->tax_percent) }}" class="form-control" required></div>
                    <div class="col-12"><hr class="my-1"><h6 class="fw-bold mt-2">Store Options</h6></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">Points per ৳100</label><input type="number" step="0.1" min="0" name="options[points_per_100]" value="{{ old('options.points_per_100', $branch->option('points_per_100')) }}" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">Return window (days)</label><input type="number" min="0" name="options[return_days]" value="{{ old('options.return_days', $branch->option('return_days')) }}" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">"High value" customer ৳</label><input type="number" min="0" name="options[high_value_amount]" value="{{ old('options.high_value_amount', $branch->option('high_value_amount')) }}" class="form-control"></div>
                    <div class="col-md-3 d-flex flex-column justify-content-end">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="options[review_auto_approve]" value="1" id="rva" @checked($branch->option('review_auto_approve'))><label class="form-check-label small" for="rva">Auto-approve reviews</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="options[allow_guest_checkout]" value="1" id="agc" @checked($branch->option('allow_guest_checkout'))><label class="form-check-label small" for="agc">Guest checkout</label></div>
                    </div>
                    <div class="col-12"><label class="form-label small fw-semibold">Invoice footer note</label><input name="options[invoice_note]" value="{{ old('options.invoice_note', $branch->option('invoice_note')) }}" class="form-control"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">bKash Number (Send Money)</label><input name="bkash_number" value="{{ old('bkash_number', $branch->bkash_number) }}" class="form-control" placeholder="খালি রাখলে bKash বন্ধ"></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Nagad Number (Send Money)</label><input name="nagad_number" value="{{ old('nagad_number', $branch->nagad_number) }}" class="form-control" placeholder="খালি রাখলে Nagad বন্ধ"></div>
                </div>
            </div>
            @perm('settings.edit')<div class="panel-body border-top text-end"><button class="btn btn-brand px-4">Save Settings</button></div>@endperm
        </form>
    </div>
    @endperm
    <div class="col-xl-4">
        <form method="POST" action="{{ route('manager.settings.password') }}" class="panel">
            @csrf @method('PUT')
            <div class="panel-head">Change Password</div>
            <div class="panel-body">
                <div class="mb-2"><label class="form-label small fw-semibold">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
                <div class="mb-2"><label class="form-label small fw-semibold">New Password</label><input type="password" name="password" class="form-control" required></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Confirm New Password</label><input type="password" name="password_confirmation" class="form-control" required></div>
                <button class="btn btn-dark w-100">Update Password</button>
            </div>
        </form>
        @if ($isOwner && ($gatewayReady ?? false))
        <form method="POST" action="{{ route('manager.settings.shatajpay') }}" class="panel mt-3">@csrf @method('PUT')
            <div class="panel-head d-flex align-items-center">নিজের ShatajPay অ্যাকাউন্ট
                <span class="ms-auto badge bg-{{ $gateway?->isUsable() ? 'success' : 'secondary' }}">{{ $gateway?->isUsable() ? 'চালু' : 'প্ল্যাটফর্ম অ্যাকাউন্ট' }}</span>
            </div>
            <div class="panel-body small">
                <p class="text-muted mb-2">অনলাইন পেমেন্টের টাকা সরাসরি আপনার ShatajPay অ্যাকাউন্টে নিতে চাইলে credential দিন। খালি রাখলে প্ল্যাটফর্মের ShatajPay অ্যাকাউন্টে জমা হবে।
                    অ্যাকাউন্ট খুলুন: <a href="https://pay.shataj.com/register" target="_blank" rel="noopener">pay.shataj.com</a></p>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label mb-0">Mode</label>
                        <select name="mode" class="form-select form-select-sm">
                            <option value="live" @selected(($gateway?->mode ?? 'live') === 'live')>Live</option>
                            <option value="sandbox" @selected($gateway?->mode === 'sandbox')>Sandbox</option>
                        </select></div>
                    <div class="col-6"><label class="form-label mb-0">Gateway ID</label><input name="gateway_id" value="{{ old('gateway_id', $gateway?->gateway_id) }}" class="form-control form-control-sm" autocomplete="off"></div>
                    <div class="col-12"><label class="form-label mb-0">App Username</label><input name="app_username" value="{{ old('app_username', $gateway?->app_username) }}" class="form-control form-control-sm" autocomplete="off"></div>
                    @foreach (['app_password' => 'App Password', 'app_key' => 'App Key', 'app_secret' => 'App Secret'] as $f => $l)
                        <div class="col-12"><label class="form-label mb-0">{{ $l }} @if ($gateway && filled($gateway->$f))<span class="text-success">✓</span>@endif</label>
                            <input type="password" name="{{ $f }}" class="form-control form-control-sm" autocomplete="new-password" placeholder="{{ $gateway && filled($gateway->$f) ? 'খালি = আগেরটা থাকবে' : '' }}"></div>
                    @endforeach
                    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="status" value="1" id="ownsp" @checked($gateway?->status ?? true)><label class="form-check-label" for="ownsp">চালু রাখুন</label></div></div>
                </div>
                <div class="mt-2 p-2 bg-light rounded">ShatajPay প্যানেলে Callback URL দিন:<br><code class="user-select-all">{{ route('payment.callback.management') }}</code></div>
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-brand">Save</button>
                    <button name="test" value="1" class="btn btn-sm btn-outline-dark">Save &amp; Test</button>
                    @if ($gateway)<button name="remove" value="1" class="btn btn-sm btn-outline-danger ms-auto" onclick="return confirm('নিজের ShatajPay অ্যাকাউন্ট সরাবেন?')">Remove</button>@endif
                </div>
            </div>
        </form>
        @endif
        @if ($isOwner)
        <div class="panel mt-3">
            <div class="panel-head">Login Info</div>
            <div class="panel-body small">
                Username: <code>{{ $branch->slug }}</code><br>
                Email: <code>{{ $branch->email }}</code><br>
                Verified: {{ $branch->mobile_verified_at?->format('d M Y') }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
