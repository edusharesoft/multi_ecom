@php $offset = ($store->option('whatsapp_button') && $store->option('whatsapp_number') ? 64 : 0) + ($store->option('messenger_url') ? 64 : 0); @endphp
<button type="button" id="mbChatBtn" aria-label="Chat" style="position:fixed;right:18px;bottom:{{ 18 + $offset }}px;z-index:1051;width:54px;height:54px;border-radius:50%;background:var(--brand);color:#fff;border:0;font-size:24px;box-shadow:0 4px 12px rgba(0,0,0,.25)"><i class="fa-solid fa-comments"></i></button>
<div id="mbChat" class="shadow" style="position:fixed;right:18px;bottom:{{ 82 + $offset }}px;z-index:1052;width:330px;max-width:calc(100vw - 36px);height:440px;max-height:70vh;background:#fff;border-radius:14px;display:none;flex-direction:column;overflow:hidden">
    <div style="background:var(--brand);color:#fff" class="p-2 px-3 d-flex justify-content-between align-items-center">
        <strong><i class="fa-solid fa-robot"></i> {{ $store->display_name }}</strong><button type="button" class="btn-close btn-close-white" id="mbChatClose"></button>
    </div>
    <div id="mbChatLog" class="p-2 small flex-fill" style="overflow-y:auto;background:#f7f7f9">
        <div class="mb-2"><span class="d-inline-block bg-white border rounded p-2">আসসালামু আলাইকুম! পণ্য, দাম, ডেলিভারি বা রিটার্ন নিয়ে কিছু জানতে চান? 🙂</span></div>
    </div>
    <form id="mbChatForm" class="d-flex border-top p-2 gap-1">
        <input id="mbChatIn" class="form-control form-control-sm" maxlength="500" placeholder="প্রশ্ন লিখুন…" autocomplete="off">
        <button class="btn btn-sm btn-brand"><i class="fa-solid fa-paper-plane"></i></button>
    </form>
    <div class="text-center text-muted" style="font-size:10px;padding-bottom:4px">AI উত্তর ভুলও হতে পারে — অর্ডারের জন্য কল করুন {{ $store->store_phone ?: $store->mobile }}</div>
</div>
<script>
(() => {
    const box = document.getElementById('mbChat'), log = document.getElementById('mbChatLog'), inp = document.getElementById('mbChatIn');
    document.getElementById('mbChatBtn').onclick = () => { box.style.display = box.style.display === 'flex' ? 'none' : 'flex'; inp.focus(); };
    document.getElementById('mbChatClose').onclick = () => box.style.display = 'none';
    const add = (text, me) => {
        const w = document.createElement('div'); w.className = 'mb-2 ' + (me ? 'text-end' : '');
        const s = document.createElement('span'); s.className = 'd-inline-block rounded p-2 ' + (me ? 'text-white' : 'bg-white border');
        if (me) s.style.background = 'var(--brand)';
        s.style.whiteSpace = 'pre-wrap'; s.style.maxWidth = '85%'; s.style.textAlign = 'left';
        // plain text only; turn URLs into safe links
        text.split(/(https?:\/\/[^\s)]+)/g).forEach(part => {
            if (/^https?:\/\//.test(part)) { const a = document.createElement('a'); a.href = part; a.textContent = 'লিংক'; a.target = '_blank'; a.rel = 'noopener'; s.appendChild(a); }
            else s.appendChild(document.createTextNode(part));
        });
        w.appendChild(s); log.appendChild(w); log.scrollTop = log.scrollHeight; return s;
    };
    document.getElementById('mbChatForm').onsubmit = async e => {
        e.preventDefault();
        const q = inp.value.trim(); if (!q) return;
        inp.value = ''; add(q, true); const wait = add('…', false);
        try {
            const r = await fetch(@json(route('store.chat', $store)), {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())}, body: JSON.stringify({message: q})});
            const d = await r.json().catch(() => ({}));
            wait.parentNode.remove(); add(d.reply || d.message || 'দুঃখিত, এখন উত্তর দেওয়া যাচ্ছে না।', false);
        } catch (err) { wait.parentNode.remove(); add('Network error', false); }
    };
})();
</script>
