{{-- Rich text editor (Quill). Usage: @include('manager.website._editor', ['name' => 'content', 'value' => $html, 'height' => 360]) --}}
@php $eid = 'ed_'.\Illuminate\Support\Str::random(6); @endphp
<div id="{{ $eid }}" class="bg-white" style="min-height:{{ $height ?? 320 }}px">{!! \App\Support\HtmlSanitizer::clean($value) !!}</div>
<textarea name="{{ $name }}" id="{{ $eid }}_in" class="d-none">{{ $value }}</textarea>
@once
@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
window.mbEditor = function (id) {
    const q = new Quill('#' + id, {theme: 'snow', modules: {toolbar: {
        container: [[{header: [2, 3, 4, false]}], ['bold', 'italic', 'underline', 'strike'], [{list: 'ordered'}, {list: 'bullet'}], [{align: []}], ['blockquote', 'link', 'image', 'video'], ['clean']],
        handlers: {image: function () {
            const inp = document.createElement('input'); inp.type = 'file'; inp.accept = 'image/*';
            inp.onchange = async () => {
                const fd = new FormData(); fd.append('image', inp.files[0]); fd.append('_token', @json(csrf_token()));
                const r = await fetch(@json(route('manager.editor.image')), {method: 'POST', body: fd, headers: {Accept: 'application/json'}});
                const d = await r.json().catch(() => ({}));
                if (!r.ok) { alert(d.message || 'Upload failed'); return; }
                const range = q.getSelection(true); q.insertEmbed(range.index, 'image', d.url);
            };
            inp.click();
        }},
    }}});
    const input = document.getElementById(id + '_in');
    input.closest('form')?.addEventListener('submit', () => { input.value = q.root.innerHTML === '<p><br></p>' ? '' : q.root.innerHTML; });
};
</script>
@endpush
@endonce
@push('scripts')
<script>mbEditor(@json($eid));</script>
@endpush
