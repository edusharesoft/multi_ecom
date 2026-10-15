{{-- window.mbAi(url, payload, btn) → Promise<json|null>; shows errors as alert. --}}
@once
@push('scripts')
<script>
window.mbAi = async function (url, payload, btn) {
    const old = btn ? btn.innerHTML : null;
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> AI লিখছে…'; }
    try {
        const r = await fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify(payload || {})});
        const d = await r.json().catch(() => ({message: 'AI ফিচার আপনার প্ল্যানে নেই বা সেশন শেষ — পাতা রিফ্রেশ করুন।'}));
        if (!r.ok) { alert(d.message || ('Error ' + r.status)); return null; }
        return d;
    } catch (e) { alert('Network error'); return null; }
    finally { if (btn) { btn.disabled = false; btn.innerHTML = old; } }
};
</script>
@endpush
@endonce
