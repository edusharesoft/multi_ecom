@extends('layouts.admin')
@section('title', __('Payment & SMS Gateways'))

@section('content')
@unless ($ready)
    <div class="alert alert-warning small">ডাটাবেস টেবিল এখনো তৈরি হয়নি। টার্মিনালে <code>php artisan migrate</code> চালান।</div>
@endunless
<div class="row g-4">
    {{-- ================= ShatajPay ================= --}}
    <div class="col-xl-6">
        <form method="POST" action="{{ route('admin.gateways.pay') }}" class="panel mb-3">@csrf
            <div class="panel-head d-flex align-items-center gap-2">
                <span class="shataj px-2 py-0">ShatajPay</span> Platform account
                <span class="ms-auto badge bg-{{ $pay['mode'] === 'live' ? 'success' : ($pay['mode'] === 'sandbox' ? 'info' : 'warning text-dark') }}">{{ strtoupper($pay['mode']) }}</span>
            </div>
            <div class="panel-body small row g-2">
                <div class="col-12 text-muted">SaaS সাবস্ক্রিপশন ইনভয়েস সবসময় এই অ্যাকাউন্টে জমা হবে। যে ব্রাঞ্চ নিজের ShatajPay অ্যাকাউন্ট দেয়নি, তার স্টোরের অর্ডারের টাকাও এখানে আসবে।
                    রেজিস্ট্রেশন: <a href="https://pay.shataj.com/register" target="_blank" rel="noopener">pay.shataj.com/register</a></div>
                <div class="col-md-6">
                    <label class="form-label mb-0">Mode</label>
                    <select name="mode" class="form-select form-select-sm">
                        @foreach (['fake' => 'Test (built-in test page, no API)', 'sandbox' => 'Sandbox', 'live' => 'Live / Production'] as $k => $l)
                            <option value="{{ $k }}" @selected(old('mode', $row?->mode ?? $pay['mode']) === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label mb-0">Display name</label><input name="display_name" value="{{ old('display_name', $row?->display_name ?? 'ShatajPay') }}" class="form-control form-control-sm"></div>
                <div class="col-12"><label class="form-label mb-0">API Base URL</label><input name="base_url" value="{{ old('base_url', $row?->base_url) }}" class="form-control form-control-sm" placeholder="{{ config('shataj.pay.base_url') }}"></div>
                <div class="col-md-6"><label class="form-label mb-0">App Username</label><input name="app_username" value="{{ old('app_username', $row?->app_username) }}" class="form-control form-control-sm" autocomplete="off"></div>
                <div class="col-md-6"><label class="form-label mb-0">Gateway ID</label><input name="gateway_id" value="{{ old('gateway_id', $row?->gateway_id) }}" class="form-control form-control-sm" autocomplete="off"></div>
                @foreach (['app_password' => 'App Password', 'app_key' => 'App Key', 'app_secret' => 'App Secret'] as $f => $l)
                    <div class="col-md-4">
                        <label class="form-label mb-0">{{ $l }} @if ($row && filled($row->$f))<span class="text-success">✓</span>@endif</label>
                        <input type="password" name="{{ $f }}" class="form-control form-control-sm" autocomplete="new-password" placeholder="{{ $row && filled($row->$f) ? 'খালি = আগেরটা থাকবে' : '' }}">
                    </div>
                @endforeach
                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="status" value="1" id="pst" @checked(old('status', $row?->status ?? true))><label class="form-check-label" for="pst">Active</label></div></div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-sm btn-brand">Save</button>
                    @if ($row && $row->isComplete() && $row->mode !== 'fake')<button formaction="{{ route('admin.gateways.pay.test') }}" class="btn btn-sm btn-outline-dark">Test connection</button>@endif
                </div>
            </div>
            <div class="panel-body border-top small">
                <div class="fw-semibold mb-1">ShatajPay মার্চেন্ট প্যানেলে এই Callback URL টি বসান:</div>
                <div class="input-group input-group-sm">
                    <input class="form-control" value="{{ route('payment.callback.management') }}" readonly id="cburl">
                    <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('cburl').value)">Copy</button>
                </div>
                <div class="text-muted mt-1">ShatajPay এই URL এ <code>?status=Complete|Failed&amp;orderId=…</code> পাঠায়। নিজের অ্যাকাউন্ট ব্যবহার করা ব্রাঞ্চগুলোকেও একই URL বসাতে হবে।</div>
                <div class="text-muted mt-1">নিজের ShatajPay অ্যাকাউন্ট চালু আছে এমন ব্রাঞ্চ: <strong>{{ $branchAccounts }}</strong></div>
            </div>
        </form>

        <div class="panel">
            <div class="panel-head">সাম্প্রতিক ShatajPay পেমেন্ট</div>
            <table class="table table-sm mb-0 small">
                <thead class="table-light"><tr><th>তারিখ</th><th>রেফারেন্স</th><th>অ্যাকাউন্ট</th><th class="text-end">টাকা</th><th>স্ট্যাটাস</th></tr></thead>
                @forelse ($recent as $p)
                    <tr>
                        <td class="text-nowrap">{{ $p->created_at?->format('d M h:i A') }}</td>
                        <td>{{ $p->invoice?->number ?? $p->order?->order_number ?? '—' }}</td>
                        <td>{{ ['branch' => 'Branch', 'platform' => 'Platform', 'env' => '.env'][data_get($p->payload, 'account', '')] ?? '—' }}@if (data_get($p->payload, 'mode') === 'fake') <span class="badge bg-warning text-dark">test</span>@endif</td>
                        <td class="text-end">৳{{ number_format($p->amount, 2) }}</td>
                        <td><span class="badge bg-{{ ['success' => 'success', 'failed' => 'danger', 'cancelled' => 'secondary', 'pending' => 'info'][$p->status] ?? 'light text-dark' }}">{{ $p->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">এখনো কোনো পেমেন্ট হয়নি।</td></tr>
                @endforelse
            </table>
        </div>
    </div>

    {{-- ================= Shataj SMS ================= --}}
    <div class="col-xl-6">
        <form method="POST" action="{{ route('admin.gateways.sms') }}" class="panel mb-3">@csrf
            <div class="panel-head d-flex align-items-center">Shataj SMS Gateway
                <span class="ms-auto badge bg-{{ $sms['driver'] === 'shataj' && $smsHasKey ? 'success' : 'warning text-dark' }}">{{ $sms['driver'] === 'shataj' ? ($smsHasKey ? 'LIVE' : 'KEY MISSING') : 'LOG (test)' }}</span>
            </div>
            <div class="panel-body small row g-2">
                <div class="col-12 text-muted">OTP, পাসওয়ার্ড রিসেট, অর্ডার নোটিফিকেশন, সাবস্ক্রিপশন রিমাইন্ডার আর ব্রাঞ্চের মার্কেটিং ক্যাম্পেইন, সব SMS এই গেটওয়ে দিয়ে যাবে। ব্রাঞ্চের মাসিক SMS লিমিট প্ল্যান থেকে নিয়ন্ত্রণ হয়।</div>
                <div class="col-md-6">
                    <label class="form-label mb-0">Driver</label>
                    <select name="sms_driver" class="form-select form-select-sm">
                        <option value="shataj" @selected($sms['driver'] === 'shataj')>Shataj SMS (real SMS)</option>
                        <option value="log" @selected($sms['driver'] !== 'shataj')>Log only (testing)</option>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label mb-0">Server</label><input name="sms_server" value="{{ old('sms_server', \App\Models\Setting::get('sms_server')) }}" class="form-control form-control-sm" placeholder="{{ config('shataj.sms.server') }}"></div>
                <div class="col-md-8">
                    <label class="form-label mb-0">API Key @if ($smsHasKey)<span class="text-success">✓ saved (encrypted)</span>@endif</label>
                    <input type="password" name="sms_api_key" class="form-control form-control-sm" autocomplete="new-password" placeholder="{{ $smsHasKey ? 'খালি = আগেরটা থাকবে' : 'sms.shataj.com → API key' }}">
                    @if ($smsHasKey && \App\Models\Setting::get('sms_api_key'))<label class="mt-1"><input type="checkbox" name="remove_sms_key" value="1" class="form-check-input"> Remove saved key</label>@endif
                </div>
                <div class="col-md-4"><label class="form-label mb-0">Device ID</label><input name="sms_device" value="{{ old('sms_device', \App\Models\Setting::get('sms_device')) }}" class="form-control form-control-sm" placeholder="0 = primary"></div>
                <div class="col-12 text-muted">Device ID খালি বা 0 দিলে primary ডিভাইস থেকে যাবে। নির্দিষ্ট SIM চাইলে <code>1|0</code> (ডিভাইস 1, SIM স্লট 1) লিখুন।</div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-sm btn-brand">Save</button>
                    @if ($smsHasKey)<button formaction="{{ route('admin.gateways.sms.status') }}" class="btn btn-sm btn-outline-dark">Balance &amp; devices</button>@endif
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.gateways.sms.test') }}" class="panel">@csrf
            <div class="panel-head">Test SMS</div>
            <div class="panel-body small d-flex gap-2">
                <input name="mobile" class="form-control form-control-sm" placeholder="01XXXXXXXXX" required>
                <button class="btn btn-sm btn-dark text-nowrap">Send Test SMS</button>
            </div>
        </form>
    </div>
</div>
@endsection
