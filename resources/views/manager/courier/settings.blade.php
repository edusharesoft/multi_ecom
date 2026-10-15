@extends('layouts.manager')
@section('title', 'Courier Settings')

@section('content')
@unless ($hasApi)
    <div class="alert alert-warning small"><i class="fa-solid fa-lock"></i> Courier API (Steadfast, Pathao, RedX) আপনার প্ল্যানে নেই — <a href="{{ route('manager.billing.plans') }}">আপগ্রেড করুন</a>। Manual courier (ট্র্যাকিং নম্বর দিয়ে) সব প্ল্যানে ব্যবহার করা যাবে।</div>
@endunless
<p class="small text-muted">API তথ্য কুরিয়ারের মার্চেন্ট প্যানেল থেকে নিন। <strong>Demo</strong> মোডে কোনো API কল হয় না — পুরো ফ্লো (বুক → Sync → Delivered) টেস্ট করতে পারবেন। গোপন তথ্য এনক্রিপ্ট করে রাখা হয়; ফাঁকা রাখলে আগেরটাই থাকবে।</p>
<div class="row g-4">
    @foreach (\App\Models\CourierAccount::COURIERS as $key => [$label, $fields])
        @continue($key === 'manual')
        @php $acc = $accounts[$key] ?? null; @endphp
        <div class="col-xl-4 col-md-6">
            <form method="POST" action="{{ route('manager.courier.save', $key) }}" class="panel h-100">@csrf
                <div class="panel-head"><span>{{ $label }}</span>
                    @if ($acc?->is_active)<span class="badge bg-success">Active{{ $acc->is_default ? ' · default' : '' }}</span>@else<span class="badge bg-secondary">Off</span>@endif</div>
                <div class="panel-body small">
                    <label class="form-label mb-0">Mode</label>
                    <select name="mode" class="form-select form-select-sm mb-2">
                        @foreach (['demo' => 'Demo (simulate, no API)', 'sandbox' => 'Sandbox (courier test server)', 'live' => 'Live'] as $m => $ml)
                            @continue($key === 'steadfast' && $m === 'sandbox')
                            <option value="{{ $m }}" @selected(($acc?->mode ?? 'demo') === $m)>{{ $ml }}</option>
                        @endforeach
                    </select>
                    @foreach ($fields as $f => $fl)
                        <label class="form-label mb-0">{{ $fl }} @if($acc?->cred($f))<span class="text-success">✓ saved</span>@endif</label>
                        <input name="cred[{{ $f }}]" type="{{ in_array($f, ['password', 'secret_key', 'client_secret', 'access_token', 'api_key'], true) ? 'password' : 'text' }}"
                               value="{{ in_array($f, ['store_id', 'username', 'client_id'], true) ? $acc?->cred($f) : '' }}" class="form-control form-control-sm mb-2" autocomplete="off">
                    @endforeach
                    <label class="form-label mb-0">Custom API base URL (optional)</label>
                    <input name="cred[base_url]" value="{{ $acc?->cred('base_url') }}" class="form-control form-control-sm mb-2" placeholder="leave blank for default">
                    <div class="d-flex gap-3 mb-2">
                        <div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($acc?->is_active)><label class="form-check-label">Enable</label></div>
                        <div class="form-check"><input type="hidden" name="is_default" value="0"><input class="form-check-input" type="checkbox" name="is_default" value="1" @checked($acc?->is_default)><label class="form-check-label">Default courier</label></div>
                    </div>
                    @if ($acc)
                        <label class="form-label mb-0">Webhook URL (কুরিয়ার প্যানেলে বসান)</label>
                        <input readonly class="form-control form-control-sm mb-2" value="{{ route('webhooks.courier', $acc->webhook_token) }}" onclick="this.select()">
                    @endif
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-brand" @disabled(! $hasApi)>Save</button>
                        @if ($acc)<button formaction="{{ route('manager.courier.test', $key) }}" class="btn btn-sm btn-outline-dark">Test connection</button>@endif
                    </div>
                </div>
            </form>
        </div>
    @endforeach
</div>
<div class="panel mt-4"><div class="panel-body small">
    <strong>কীভাবে কাজ করে:</strong> অর্ডার পাতায় “Send to Courier” চাপলে পার্সেল বুক হয়, অর্ডার <em>Shipped</em> হয় এবং কাস্টমার ট্র্যাকিং SMS পায়।
    প্রতি ৩০ মিনিটে (scheduler চালু থাকলে) বা “Sync” চাপলে কুরিয়ার থেকে স্ট্যাটাস এনে অর্ডার <em>On the way → Delivered</em> হয়। Return/Cancel হলে আপনাকে নোটিফিকেশন দেওয়া হবে।
    Pathao-তে সিটি/জোন লাগে না — পূর্ণ ঠিকানা (রাস্তা, থানা, জেলা) দিন। RedX-এ ডেলিভারি এরিয়া বেছে নিতে হয়।
</div></div>
@endsection
