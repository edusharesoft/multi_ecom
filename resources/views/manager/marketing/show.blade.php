@extends('layouts.manager')
@section('title', 'Campaign: '.$c->name)

@section('content')
@include('manager.marketing._nav')
<div class="row g-4">
    <div class="col-xl-5">
        <div class="panel mb-3">
            <div class="panel-head">{{ $c->name }} <span class="badge bg-{{ ['draft' => 'secondary', 'sending' => 'warning text-dark', 'sent' => 'success', 'cancelled' => 'dark'][$c->status] ?? 'light' }}" id="stBadge">{{ ucfirst($c->status) }}</span></div>
            <div class="panel-body small">
                <div>Channel: <strong>{{ strtoupper($c->channel) }}</strong> · Audience: {{ \App\Services\Messaging\CampaignService::AUDIENCES[$c->audience] ?? $c->audience }}</div>
                @if ($c->subject)<div>Subject: {{ $c->subject }}</div>@endif
                <div class="fw-semibold mt-2">Preview</div>
                <div class="border rounded p-2 bg-light" style="white-space:pre-wrap">{{ $sample }}</div>
                @if ($c->channel === 'sms')<div class="text-muted mt-1">{{ mb_strlen($sample) }} chars · {{ \App\Services\Messaging\MessageService::segments($sample) }} SMS / recipient · বাকি কোটা: {{ $smsLeft ?? 'Unlimited' }}</div>@endif

                @if ($c->status !== 'draft')
                    <div class="mt-3">
                        <div class="progress" style="height:14px"><div class="progress-bar bg-success" id="bar" style="width:{{ $c->total ? round(($c->sent + $c->failed) / $c->total * 100) : 0 }}%"></div></div>
                        <div class="d-flex justify-content-between mt-1"><span>Sent <strong id="sent">{{ $c->sent }}</strong> · Failed <strong id="failed" class="text-danger">{{ $c->failed }}</strong></span><span>Total {{ $c->total }}</span></div>
                        <div id="sendNote" class="text-muted">{{ $c->status === 'sending' ? 'পাঠানো চলছে — এই পাতা খোলা রাখুন…' : '' }}</div>
                    </div>
                @endif

                @perm('marketing.add')
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    @if ($c->status === 'draft')
                        <form method="POST" action="{{ route('manager.marketing.start', $c) }}" onsubmit="return confirm('এখনই সবাইকে পাঠাবেন?')">@csrf<button class="btn btn-brand btn-sm"><i class="fa-solid fa-paper-plane"></i> Send now</button></form>
                    @endif
                    @if (in_array($c->status, ['draft', 'sending'], true))
                        <form method="POST" action="{{ route('manager.marketing.cancel', $c) }}" onsubmit="return confirm('বন্ধ করবেন?')">@csrf<button class="btn btn-outline-danger btn-sm">Cancel</button></form>
                    @endif
                    <form method="POST" action="{{ route('manager.marketing.duplicate', $c) }}">@csrf<button class="btn btn-light border btn-sm"><i class="fa-regular fa-copy"></i> Duplicate</button></form>
                </div>
                @endperm
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="panel">
            <div class="panel-head">Recipients (first 100)</div>
            <div class="table-responsive"><table class="table table-sm mb-0 small">
                <thead class="table-light"><tr><th>To</th><th>Name</th><th>Status</th><th>Time</th></tr></thead>
                @forelse ($recipients as $r)
                    <tr><td>{{ $r->to }}</td><td>{{ $r->name }}</td><td><span class="badge bg-{{ ['sent' => 'success', 'failed' => 'danger', 'pending' => 'secondary', 'sending' => 'warning text-dark'][$r->status] ?? 'light' }}">{{ $r->status }}</span> <span class="text-muted">{{ $r->error }}</span></td><td>{{ $r->sent_at?->format('d M h:i A') }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Send চাপলে অডিয়েন্স থেকে তালিকা তৈরি হবে।</td></tr>
                @endforelse
            </table></div>
        </div>
    </div>
</div>
@endsection

@if ($c->status === 'sending' && \App\Support\Tenancy::can('marketing.add'))
@push('scripts')
<script>
(async () => {
    const url = @json(route('manager.marketing.step', $c)), token = @json(csrf_token());
    while (true) {
        let d;
        try {
            const r = await fetch(url, {method: 'POST', headers: {'X-CSRF-TOKEN': token, Accept: 'application/json'}});
            d = await r.json();
            if (!r.ok) { document.getElementById('sendNote').textContent = d.message || ('Error ' + r.status); break; }
        } catch (e) { await new Promise(res => setTimeout(res, 3000)); continue; }
        document.getElementById('sent').textContent = d.sent; document.getElementById('failed').textContent = d.failed;
        document.getElementById('bar').style.width = (d.total ? Math.round((d.sent + d.failed) / d.total * 100) : 100) + '%';
        if (d.status !== 'sending' || d.left === 0) { document.getElementById('sendNote').textContent = 'সম্পন্ন ✓'; document.getElementById('stBadge').textContent = 'Sent'; setTimeout(() => location.reload(), 1200); break; }
    }
})();
</script>
@endpush
@endif
