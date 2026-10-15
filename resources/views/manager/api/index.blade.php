@extends('layouts.manager')
@section('title', __('REST API & Webhooks'))

@section('content')
@unless ($b->hasFeature('api'))
    <div class="alert alert-warning small"><i class="fa-solid fa-lock"></i> REST API আপনার প্ল্যানে নেই — token তৈরি করা যাবে, কিন্তু API কল কাজ করবে না। <a href="{{ route('manager.billing.plans') }}">আপগ্রেড করুন</a>।</div>
@endunless
@if (session('plain_token'))
    <div class="alert alert-success small">
        <strong>নতুন API token (শুধু একবার দেখানো হচ্ছে):</strong>
        <div class="input-group mt-1"><input id="plainTok" readonly class="form-control font-monospace" value="{{ session('plain_token') }}"><button class="btn btn-dark" type="button" onclick="navigator.clipboard.writeText(document.getElementById('plainTok').value);this.textContent='Copied'">Copy</button></div>
    </div>
@endif
<div class="row g-4">
    <div class="col-xl-7">
        <div class="panel mb-3">
            <div class="panel-head">API tokens</div>
            <div class="table-responsive"><table class="table table-sm mb-0 small align-middle">
                <thead class="table-light"><tr><th>Name</th><th>Token</th><th>Abilities</th><th>Last used</th><th>Expires</th><th></th></tr></thead>
                @forelse ($tokens as $t)
                    <tr class="{{ $t->expired() ? 'text-muted' : '' }}">
                        <td><strong>{{ $t->name }}</strong><br><span class="text-muted">{{ $t->created_by }}</span></td>
                        <td><code>{{ $t->prefix }}…</code></td>
                        <td>@foreach ($t->abilities ?? [] as $a)<span class="badge bg-light text-dark border">{{ $a }}</span> @endforeach</td>
                        <td>{{ $t->last_used_at?->diffForHumans() ?? '—' }}</td>
                        <td>{{ $t->expires_at ? $t->expires_at->format('d M Y') : 'Never' }} @if($t->expired())<span class="badge bg-danger">expired</span>@endif</td>
                        <td><form method="POST" action="{{ route('manager.api.tokens.destroy', $t) }}" onsubmit="return confirm('Token বাতিল করবেন? এটি দিয়ে চলা সব ইন্টিগ্রেশন বন্ধ হবে।')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No tokens yet.</td></tr>
                @endforelse
            </table></div>
        </div>
        <form method="POST" action="{{ route('manager.api.tokens.store') }}" class="panel mb-3">@csrf
            <div class="panel-head">Create token</div>
            <div class="panel-body small">
                <div class="d-flex gap-2 mb-2"><input name="name" class="form-control form-control-sm" placeholder="e.g. Mobile app / Facebook shop sync" required>
                    <select name="expires" class="form-select form-select-sm" style="width:150px"><option value="never">No expiry</option><option value="30">30 days</option><option value="90">90 days</option><option value="365">1 year</option></select></div>
                <div class="d-flex flex-wrap gap-3 mb-2">@foreach (\App\Models\ApiToken::ABILITIES as $k => $l)<label><input type="checkbox" name="abilities[]" value="{{ $k }}" class="form-check-input" @checked(str_ends_with($k, '.read'))> {{ $l }}</label>@endforeach</div>
                <button class="btn btn-sm btn-brand">Create token</button>
            </div>
        </form>
        <div class="panel">
            <div class="panel-head">API documentation (v1)</div>
            <div class="panel-body small">
                <p>Base URL: <code>{{ $base }}</code> · Header: <code>Authorization: Bearer &lt;token&gt;</code> · <code>Accept: application/json</code> · Rate limit 120 req/min · JSON, paginated with <code>?page=</code> <code>&amp;per_page=</code> (max 100)</p>
                <table class="table table-sm table-bordered">
                    <thead class="table-light"><tr><th>Method</th><th>Endpoint</th><th>Ability</th><th>Notes</th></tr></thead>
                    @foreach ([
                        ['GET', '/store', 'store.read', 'Store info'],
                        ['GET', '/categories', 'store.read', 'Global category list'],
                        ['GET', '/products', 'products.read', '?q= &category_id= &active=1 &updated_since=2026-09-01'],
                        ['GET', '/products/{id}', 'products.read', 'with variants'],
                        ['POST', '/products', 'products.write', 'name, price, sku, barcode, category_id, description, stock, is_active…'],
                        ['PUT', '/products/{id}', 'products.write', 'partial update (stock ignored)'],
                        ['POST', '/products/{id}/stock', 'products.write', '{"quantity": -2} or {"set": 20}, optional variant_id — writes the stock ledger'],
                        ['GET', '/orders', 'orders.read', '?status= &payment_status= &from= &to= &phone='],
                        ['GET', '/orders/{order_number}', 'orders.read', 'items + shipment'],
                        ['PATCH', '/orders/{order_number}/status', 'orders.write', '{"status": "confirmed", "note": "..."}'],
                        ['GET', '/customers', 'customers.read', '?q='],
                        ['GET', '/customers/{id}', 'customers.read', ''],
                        ['POST', '/customers', 'customers.write', 'name, phone (01XXXXXXXXX), email'],
                    ] as [$m, $e, $a, $n])
                        <tr><td><span class="badge bg-{{ ['GET' => 'success', 'POST' => 'primary', 'PUT' => 'warning text-dark', 'PATCH' => 'warning text-dark'][$m] }}">{{ $m }}</span></td><td><code>{{ $e }}</code></td><td><code>{{ $a }}</code></td><td>{{ $n }}</td></tr>
                    @endforeach
                </table>
                <div class="fw-semibold">Example</div>
<pre class="bg-dark text-white p-2 rounded small mb-2">curl -H "Authorization: Bearer mb_..." -H "Accept: application/json" {{ $base }}/orders?status=new</pre>
                <div>Errors: <code>401</code> invalid token · <code>402</code> plan has no API · <code>403</code> missing ability · <code>404</code> not found · <code>422</code> validation (<code>errors</code> object) · <code>429</code> rate limit.</div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <form method="POST" action="{{ route('manager.api.webhook') }}" class="panel mb-3">@csrf
            <div class="panel-head">Webhooks (আপনার সার্ভারে ইভেন্ট পাঠানো)</div>
            <div class="panel-body small">
                <label class="form-label mb-0">Endpoint URL</label>
                <input name="webhook_url" value="{{ $b->option('webhook_url') }}" class="form-control form-control-sm mb-2" placeholder="https://your-server.com/webhooks/shop">
                @foreach (\App\Services\WebhookService::EVENTS as $k => $l)
                    <label class="d-block"><input type="checkbox" name="webhook_events[]" value="{{ $k }}" class="form-check-input" @checked(in_array($k, (array) $b->option('webhook_events', []), true))> <code>{{ $k }}</code> — {{ $l }}</label>
                @endforeach
                @if ($b->option('webhook_secret'))
                    <label class="form-label mb-0 mt-2">Signing secret</label>
                    <input readonly class="form-control form-control-sm font-monospace" value="{{ $b->option('webhook_secret') }}" onclick="this.select()">
                    <label class="mt-1"><input type="checkbox" name="regenerate_secret" value="1" class="form-check-input"> নতুন secret তৈরি করুন</label>
                @endif
                <p class="text-muted mt-2 mb-2">প্রতিটি অনুরোধে <code>X-MB-Signature: sha256=HMAC(body, secret)</code> থাকে — আপনার সার্ভারে যাচাই করুন।</p>
                <div class="d-flex gap-2"><button class="btn btn-sm btn-brand">Save</button><button formaction="{{ route('manager.api.webhook.test') }}" class="btn btn-sm btn-outline-dark">Send test</button></div>
            </div>
        </form>
        <div class="panel"><div class="panel-head">Recent deliveries</div>
            <table class="table table-sm mb-0 small">
                @forelse ($deliveries as $d)
                    <tr><td>{{ $d->created_at?->format('d M h:i A') }}</td><td><code>{{ $d->event }}</code></td><td><span class="badge bg-{{ $d->success ? 'success' : 'danger' }}">{{ $d->status_code ?? 'ERR' }}</span></td><td class="text-muted">{{ $d->duration_ms }} ms</td></tr>
                @empty
                    <tr><td class="text-muted text-center py-3">No deliveries yet.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection
