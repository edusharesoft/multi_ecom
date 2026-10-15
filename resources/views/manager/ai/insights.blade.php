@extends('layouts.manager')
@section('title', __('AI Business Insights'))

@section('content')
@php $g = $m['growth']; $arrow = fn ($v) => $v === null ? '' : ($v >= 0 ? '▲ '.$v.'%' : '▼ '.abs($v).'%'); @endphp
<div class="row g-3 mb-3">
    @foreach ([['Net sales (30 days)', '৳ '.number_format($m['cur']['net'], 0), $g['sales']], ['Orders', $m['cur']['orders'], $g['orders']], ['Average order', '৳ '.number_format($m['cur']['avg'], 0), $g['avg']], ['Cancelled / failed', $m['cur']['cancelled'], null]] as [$l, $v, $gr])
        <div class="col-6 col-lg-3"><div class="stat-card"><div><div class="v">{{ $v }}</div><div class="l">{{ __($l) }} @if($gr !== null)<span class="{{ $gr >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">{{ $arrow($gr) }}</span>@endif</div></div></div></div>
    @endforeach
</div>
<div class="row g-4">
    <div class="col-xl-7">
        <div class="panel mb-3">
            <div class="panel-head">✨ AI business review
                @if ($available)
                    <div class="d-flex gap-2 align-items-center"><select id="insLang" class="form-select form-select-sm" style="width:auto"><option value="bn">বাংলা</option><option value="en">English</option></select>
                        <button type="button" id="insBtn" class="btn btn-sm btn-brand">✨ Analyse my shop</button></div>
                @endif
            </div>
            <div class="panel-body small">
                @if (! $configured)
                    <p class="text-muted mb-0">AI এখনো চালু করা হয়নি — Super Admin → AI Settings এ API key দিতে হবে।</p>
                @elseif (! $available)
                    <p class="text-muted mb-0"><i class="fa-solid fa-lock"></i> AI Assistant আপনার প্ল্যানে নেই — <a href="{{ route('manager.billing.plans') }}">আপগ্রেড করুন</a>।</p>
                @else
                    <div id="insOut" class="text-muted" style="white-space:pre-wrap">গত ৩০ দিনের বিক্রি, সেরা পণ্য, পেমেন্ট ও স্টকের তথ্য বিশ্লেষণ করে পরামর্শ ও আগামী মাসের সম্ভাব্য বিক্রির ধারণা পেতে বাটনে চাপুন।</div>
                    <div class="text-muted mt-2" style="font-size:11px">বাকি AI requests: {{ $left ?? 'Unlimited' }} · AI এর পূর্বাভাস শুধু ধারণা — সিদ্ধান্তের আগে নিজের বিচার ব্যবহার করুন।</div>
                @endif
            </div>
        </div>
        <div class="panel">
            <div class="panel-head">Top products (30 days)</div>
            <table class="table table-sm mb-0 small">
                <thead class="table-light"><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Revenue</th><th class="text-end">Profit</th></tr></thead>
                @forelse ($m['top'] as $r)<tr><td>{{ $r->name }}</td><td class="text-end">{{ (int) $r->qty }}</td><td class="text-end">{{ number_format($r->revenue) }}</td><td class="text-end {{ $r->profit < 0 ? 'text-danger' : '' }}">{{ number_format($r->profit) }}</td></tr>
                @empty<tr><td colspan="4" class="text-muted text-center py-3">No sales yet.</td></tr>@endforelse
            </table>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="panel">
            <div class="panel-head">Reorder soon <span class="small text-muted fw-normal">বিক্রির গতি অনুযায়ী ১৪ দিনের কম স্টক</span></div>
            <table class="table table-sm mb-0 small">
                <thead class="table-light"><tr><th>Product</th><th class="text-end">Stock</th><th class="text-end">Sold/day</th><th class="text-end">Days left</th><th class="text-end">Buy ~30d</th></tr></thead>
                @forelse ($m['reorder'] as $p)
                    <tr><td>{{ $p->name }}</td><td class="text-end">{{ $p->stock }}</td><td class="text-end">{{ $p->per_day }}</td><td class="text-end {{ $p->days_left <= 3 ? 'text-danger fw-bold' : '' }}">{{ $p->days_left }}</td><td class="text-end">{{ max(0, $p->suggest) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-muted text-center py-3">কোনো পণ্যের স্টক শিগগির শেষ হচ্ছে না 👍</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
@endsection

@include('manager.ai._js')
@push('scripts')
<script>
document.getElementById('insBtn')?.addEventListener('click', async function () {
    const d = await mbAi(@json(route('manager.ai.insights.generate')), {lang: document.getElementById('insLang').value}, this);
    if (d?.text) { const o = document.getElementById('insOut'); o.classList.remove('text-muted'); o.textContent = d.text; }
});
</script>
@endpush
