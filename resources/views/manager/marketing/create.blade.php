@extends('layouts.manager')
@section('title', 'New Campaign')

@section('content')
@include('manager.marketing._nav')
<div class="row justify-content-center"><div class="col-xl-8">
<form method="POST" action="{{ route('manager.marketing.store') }}" class="panel" id="campForm">@csrf
    <div class="panel-head">নতুন ক্যাম্পেইন</div>
    <div class="panel-body small">
        <label class="form-label mb-0">Campaign name *</label>
        <input name="name" value="{{ old('name') }}" class="form-control form-control-sm mb-2" placeholder="e.g. ঈদ অফার ২০%" required>
        <label class="form-label mb-0">Channel</label>
        <div class="d-flex gap-3 mb-2">
            <label><input type="radio" name="channel" value="sms" class="form-check-input" @checked(old('channel', 'sms') === 'sms') @disabled(! $canSms)> SMS @unless($canSms)<i class="fa-solid fa-lock"></i>@endunless</label>
            <label><input type="radio" name="channel" value="email" class="form-check-input" @checked(old('channel') === 'email') @disabled(! $canEmail)> Email @unless($canEmail)<i class="fa-solid fa-lock"></i>@endunless</label>
        </div>
        <label class="form-label mb-0">Audience</label>
        <div class="d-flex gap-2 mb-1">
            <select name="audience" id="audience" class="form-select form-select-sm">@foreach (\App\Services\Messaging\CampaignService::AUDIENCES as $k => $l)<option value="{{ $k }}" @selected(old('audience') === $k)>{{ $l }}</option>@endforeach</select>
            <span class="badge bg-light text-dark border align-self-center" id="audCount">…</span>
        </div>
        <textarea name="custom_numbers" id="customNumbers" rows="3" class="form-control form-control-sm mb-2" placeholder="নম্বর/ইমেইল — প্রতি লাইনে একটি বা কমা দিয়ে">{{ old('custom_numbers') }}</textarea>
        <div id="subjectBox"><label class="form-label mb-0">Email subject *</label><input name="subject" value="{{ old('subject') }}" class="form-control form-control-sm mb-2"></div>
        @if ($tenant->hasFeature('ai'))
        <div class="border rounded p-2 bg-light mb-2">
            <div class="d-flex gap-2 flex-wrap"><input id="aiGoal" class="form-control form-control-sm" style="max-width:420px" placeholder="✨ AI: অফার/উদ্দেশ্য লিখুন — যেমন “সব পাঞ্জাবিতে ২০% ছাড়, শুধু শুক্রবার”">
                <select id="aiCampLang" class="form-select form-select-sm" style="width:auto"><option value="bn">বাংলা</option><option value="en">English</option></select>
                <button type="button" id="aiCampBtn" class="btn btn-sm btn-dark">✨ ৩টি লেখা দাও</button></div>
            <div id="aiVariants" class="mt-2"></div>
        </div>
        @endif
        <label class="form-label mb-0">Message *</label>
        <textarea name="message" id="msg" rows="5" class="form-control form-control-sm" required>{{ old('message', "প্রিয় {name}, {store} এ চলছে বিশেষ অফার! আজই অর্ডার করুন: {link}") }}</textarea>
        <div class="d-flex justify-content-between text-muted mt-1"><span>Placeholders: {name} {store} {link} {phone}</span><span id="msgInfo"></span></div>
        <p class="text-muted mt-2 mb-3">Marketing বন্ধ রাখা (opt-out) কাস্টমারদের পাঠানো হবে না। সেভ করার পর প্রিভিউ দেখে “Send” চাপুন।</p>
        <button class="btn btn-brand">Save & Preview</button>
    </div>
</form>
</div></div>
@endsection

@include('manager.ai._js')

@push('scripts')
<script>
(() => {
    const f = document.getElementById('campForm'), aud = document.getElementById('audience'), cn = document.getElementById('customNumbers'), msg = document.getElementById('msg');
    const ch = () => f.querySelector('[name=channel]:checked')?.value || 'sms';
    const ui = () => {
        cn.style.display = aud.value === 'custom' ? '' : 'none';
        document.getElementById('subjectBox').style.display = ch() === 'email' ? '' : 'none';
        const t = msg.value, uni = /[^\x00-\x7F]/.test(t), n = [...t].length;
        const parts = uni ? (n <= 70 ? 1 : Math.ceil(n / 67)) : (n <= 160 ? 1 : Math.ceil(n / 153));
        document.getElementById('msgInfo').textContent = ch() === 'sms' ? `${n} chars · ~${parts} SMS${uni ? ' (Unicode)' : ''} / recipient` : `${n} chars`;
    };
    let t;
    const count = () => { clearTimeout(t); t = setTimeout(async () => {
        const fd = new FormData(); fd.append('_token', @json(csrf_token())); fd.append('audience', aud.value); fd.append('channel', ch()); fd.append('custom_numbers', cn.value);
        const r = await fetch(@json(route('manager.marketing.preview')), {method: 'POST', body: fd, headers: {Accept: 'application/json'}});
        const d = await r.json().catch(() => ({}));
        document.getElementById('audCount').textContent = (d.count ?? '?') + ' recipients';
    }, 400); };
    f.addEventListener('change', () => { ui(); count(); });
    cn.addEventListener('input', count); msg.addEventListener('input', ui);
    ui(); count();
    document.getElementById('aiCampBtn')?.addEventListener('click', async function () {
        const goal = document.getElementById('aiGoal').value.trim();
        if (!goal) { alert('অফার/উদ্দেশ্য লিখুন।'); return; }
        const d = await mbAi(@json(route('manager.ai.campaign')), {goal, channel: ch(), lang: document.getElementById('aiCampLang').value}, this);
        if (!d) return;
        const box = document.getElementById('aiVariants'); box.innerHTML = '';
        (d.variants || []).forEach(v => {
            const b = document.createElement('button'); b.type = 'button'; b.className = 'btn btn-sm btn-light border text-start d-block w-100 mb-1';
            b.textContent = v; b.onclick = () => { msg.value = v; if (d.subject) { const s = f.querySelector('[name=subject]'); if (s && !s.value) s.value = d.subject; } ui(); };
            box.appendChild(b);
        });
        if (d.variants?.length) box.insertAdjacentHTML('afterbegin', '<div class="text-muted mb-1">একটিতে ক্লিক করলে Message ঘরে বসবে:</div>');
    });
})();
</script>
@endpush
