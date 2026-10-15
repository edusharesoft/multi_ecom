@extends('layouts.admin')
@section('title', 'Custom Domains')

@section('content')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel">
            <form class="panel-head justify-content-start gap-2" method="GET">
                <select name="status" class="form-select form-select-sm" style="width:180px"><option value="">All statuses</option>@foreach (\App\Models\Domain::STATUS as $k => [$l])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
                <button class="btn btn-sm btn-dark">Filter</button>
            </form>
            <div class="table-responsive"><table class="table table-sm mb-0 small align-middle">
                <thead class="table-light"><tr><th>Domain</th><th>Branch</th><th>Status</th><th>Last check</th><th></th></tr></thead>
                @forelse ($domains as $d)
                    <tr>
                        <td><a href="https://{{ $d->host }}" target="_blank" rel="noopener">{{ $d->host }}</a> @if($d->is_primary)<span class="badge bg-light text-dark border">primary</span>@endif</td>
                        <td>{{ $d->branch?->name }}</td>
                        <td><span class="badge bg-{{ \App\Models\Domain::STATUS[$d->status][1] }}">{{ \App\Models\Domain::STATUS[$d->status][0] }}</span></td>
                        <td class="text-muted">{{ $d->last_checked_at?->diffForHumans() }}<br>{{ \Illuminate\Support\Str::limit($d->check_message, 80) }}</td>
                        <td class="text-end text-nowrap">
                            @foreach (['recheck' => ['Re-check', 'outline-primary'], 'activate' => ['Activate', 'success'], 'reject' => ['Reject', 'outline-danger']] as $a => [$l, $c])
                                <form method="POST" action="{{ route('admin.domains.status', $d) }}" class="d-inline">@csrf @method('PATCH')<button name="action" value="{{ $a }}" class="btn btn-sm btn-{{ $c }}">{{ $l }}</button></form>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No custom domains yet.</td></tr>
                @endforelse
            </table></div>
            <div class="panel-body">{{ $domains->links() }}</div>
        </div>
    </div>
    <div class="col-xl-4">
        <form method="POST" action="{{ route('admin.domains.settings') }}" class="panel">@csrf
            <div class="panel-head">Domain settings</div>
            <div class="panel-body small">
                <label class="form-label mb-0">Server public IP (A record target)</label>
                <input name="server_ip" value="{{ $settings['server_ip'] }}" class="form-control form-control-sm mb-2" placeholder="203.0.113.10">
                <label class="form-label mb-0">CNAME target</label>
                <input name="domain_cname_target" value="{{ $settings['domain_cname_target'] }}" class="form-control form-control-sm mb-2" placeholder="{{ parse_url(config('app.url'), PHP_URL_HOST) }}">
                <div class="form-check mb-2"><input type="hidden" name="domain_manual_approval" value="0"><input type="checkbox" class="form-check-input" name="domain_manual_approval" value="1" @checked($settings['domain_manual_approval'])><label class="form-check-label">DNS ভেরিফাই হলেও আমি নিজে Activate করব</label></div>
                <hr>
                <div class="form-check mb-2"><input type="hidden" name="store_subdomains" value="0"><input type="checkbox" class="form-check-input" name="store_subdomains" value="1" @checked($settings['store_subdomains'])><label class="form-check-label">Store sub-domains চালু (slug.yourplatform.com)</label></div>
                <label class="form-label mb-0">Base domain for sub-domains</label>
                <input name="store_base_domain" value="{{ $settings['store_base_domain'] }}" class="form-control form-control-sm mb-2" placeholder="yourplatform.com">
                <p class="text-muted">Sub-domain এর জন্য DNS এ wildcard <code>*.yourplatform.com</code> → server IP এবং ওয়েব সার্ভারে wildcard vhost + SSL লাগবে। Custom domain এর জন্য সার্ভার সব host গ্রহণ করতে হবে (catch-all vhost) — Cloudflare / Caddy on-demand TLS সহজ সমাধান।</p>
                <button class="btn btn-sm btn-brand">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
