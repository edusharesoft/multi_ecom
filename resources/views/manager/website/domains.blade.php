@extends('layouts.manager')
@section('title', 'Website — Domain')

@section('content')
@include('manager.website._nav')
<div class="row g-4">
    <div class="col-xl-5">
        <div class="panel mb-3"><div class="panel-head">আপনার স্টোরের ঠিকানা</div><div class="panel-body small">
            <div>Platform URL: <a href="{{ route('store.home', $b) }}" target="_blank">{{ route('store.home', $b) }}</a></div>
            @if ($subdomain)<div>Sub-domain: <a href="https://{{ $subdomain }}" target="_blank">{{ $subdomain }}</a></div>@endif
            @foreach ($domains->where('status', 'active') as $d)<div>Custom domain: <a href="https://{{ $d->host }}" target="_blank">{{ $d->host }}</a> @if($d->is_primary)<span class="badge bg-success">primary</span>@endif</div>@endforeach
        </div></div>
        @if ($canCustom)
            <form method="POST" action="{{ route('manager.domains.store') }}" class="panel">@csrf
                <div class="panel-head">নিজের ডোমেইন যুক্ত করুন</div>
                <div class="panel-body small">
                    <input name="host" value="{{ old('host') }}" class="form-control mb-2" placeholder="shop.yourbrand.com অথবা yourbrand.com" required>
                    <button class="btn btn-brand btn-sm">Add domain</button>
                    <p class="text-muted mt-2 mb-0">ডোমেইন যুক্ত করার পর আপনার ডোমেইন প্রোভাইডারের (Namecheap, GoDaddy, Cloudflare…) DNS এ দুটি রেকর্ড বসাতে হবে — নিচে দেখানো হবে।</p>
                </div>
            </form>
        @else
            <div class="alert alert-warning small"><i class="fa-solid fa-lock"></i> Custom domain আপনার প্ল্যানে নেই — <a href="{{ route('manager.billing.plans') }}">আপগ্রেড করুন</a>।</div>
        @endif
    </div>
    <div class="col-xl-7">
        @forelse ($domains as $d)
            <div class="panel mb-3">
                <div class="panel-head"><span><i class="fa-solid fa-globe"></i> {{ $d->host }}</span><span class="badge bg-{{ \App\Models\Domain::STATUS[$d->status][1] }}">{{ \App\Models\Domain::STATUS[$d->status][0] }}</span></div>
                <div class="panel-body small">
                    <div class="table-responsive"><table class="table table-sm table-bordered mb-2">
                        <thead class="table-light"><tr><th>Type</th><th>Name / Host</th><th>Value</th></tr></thead>
                        <tr><td>TXT</td><td><code>{{ $d->txtName() }}</code></td><td><code>{{ $d->verify_token }}</code></td></tr>
                        <tr><td>CNAME <span class="text-muted">(sub-domain, e.g. shop.)</span></td><td><code>{{ $d->host }}</code></td><td><code>{{ $target }}</code></td></tr>
                        <tr><td>A <span class="text-muted">(root domain)</span></td><td><code>{{ $d->host }}</code></td><td><code>{{ $serverIp ?: 'Server IP — Super Admin থেকে জানুন' }}</code></td></tr>
                    </table></div>
                    <p class="text-muted mb-2">CNAME অথবা A — যেকোনো একটি দিন। অনেক প্রোভাইডারে Name ঘরে শুধু ডোমেইনের আগের অংশ লিখতে হয় (যেমন <code>_mbverify.shop</code>)।</p>
                    @if ($d->check_message)<div class="text-muted mb-2">Last check {{ $d->last_checked_at?->diffForHumans() }}: {{ $d->check_message }}</div>@endif
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        @if ($d->status !== 'rejected')<form method="POST" action="{{ route('manager.domains.verify', $d) }}">@csrf<button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-rotate"></i> Verify DNS</button></form>@endif
                        <form method="POST" action="{{ route('manager.domains.update', $d) }}" class="d-flex gap-2 align-items-center">@csrf @method('PATCH')
                            <label><input type="hidden" name="is_primary" value="0"><input type="checkbox" name="is_primary" value="1" class="form-check-input" @checked($d->is_primary)> Primary</label>
                            <label title="platform URL ভিজিট করলে এই ডোমেইনে পাঠাবে"><input type="hidden" name="redirect_to_domain" value="0"><input type="checkbox" name="redirect_to_domain" value="1" class="form-check-input" @checked($d->redirect_to_domain) @disabled($d->status !== 'active')> Redirect platform URL here</label>
                            <button class="btn btn-sm btn-light border">Save</button>
                        </form>
                        <form method="POST" action="{{ route('manager.domains.destroy', $d) }}" class="ms-auto" onsubmit="return confirm('ডোমেইন মুছবেন?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>
                    </div>
                </div>
            </div>
        @empty
            <div class="panel"><div class="panel-body small text-muted">
                <strong>কীভাবে কাজ করে:</strong> ডোমেইন যোগ করুন → DNS এ TXT (মালিকানা প্রমাণ) ও CNAME/A (আমাদের সার্ভারে পয়েন্ট) রেকর্ড দিন → “Verify DNS” চাপুন। ভেরিফাই হলে <code>yourdomain.com</code> এ আপনার স্টোর খুলবে। HTTPS (SSL) এর জন্য সার্ভারে সার্টিফিকেট লাগবে — Cloudflare ব্যবহার করলে সবচেয়ে সহজ।
            </div></div>
        @endforelse
    </div>
</div>
@endsection
