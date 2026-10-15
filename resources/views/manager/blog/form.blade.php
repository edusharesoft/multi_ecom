@extends('layouts.manager')
@section('title', $post->exists ? 'Edit post' : 'New post')

@section('content')
<a href="{{ route('manager.blog.index') }}" class="small text-muted"><i class="fa-solid fa-arrow-left"></i> Blog</a>
<form method="POST" action="{{ $post->exists ? route('manager.blog.update', $post) : route('manager.blog.store') }}" enctype="multipart/form-data" class="mt-2">@csrf @if($post->exists) @method('PUT') @endif
<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel"><div class="panel-body">
            @if ($tenant->hasFeature('ai'))
            <details class="mb-2 small"><summary class="text-primary">✨ AI দিয়ে পুরো পোস্ট লিখুন</summary>
                <div class="border rounded p-2 bg-light mt-1">
                    <input id="aiTopic" class="form-control form-control-sm mb-2" placeholder="বিষয়: যেমন “ঈদে কোন শাড়ি মানাবে — ২০২৬ ট্রেন্ড”">
                    <div class="d-flex gap-2 flex-wrap">
                        <input id="aiKw" class="form-control form-control-sm" style="max-width:260px" placeholder="SEO keywords (optional)">
                        <select id="aiBlogLang" class="form-select form-select-sm" style="width:auto"><option value="bn">বাংলা</option><option value="en">English</option></select>
                        <select id="aiLen" class="form-select form-select-sm" style="width:auto"><option value="short">Short</option><option value="medium" selected>Medium</option><option value="long">Long</option></select>
                        <button type="button" id="aiBlogBtn" class="btn btn-sm btn-dark">✨ Generate</button>
                    </div>
                    <div class="text-muted mt-1">তৈরি লেখা এডিটরে বসবে — প্রকাশের আগে অবশ্যই পড়ে তথ্য যাচাই করুন।</div>
                </div>
            </details>
            @endif
            <input name="title" value="{{ old('title', $post->title) }}" class="form-control form-control-lg mb-2" placeholder="Post title" required>
            <div class="input-group input-group-sm mb-3"><span class="input-group-text">/{{ \App\Support\Tenancy::branch()->slug }}/blog/</span><input name="slug" value="{{ old('slug', $post->slug) }}" class="form-control" placeholder="auto from title"></div>
            @include('manager.website._editor', ['name' => 'content', 'value' => old('content', $post->content), 'height' => 460])
            <label class="form-label small mb-0 mt-3">Excerpt (list এ দেখাবে)</label>
            <textarea name="excerpt" rows="2" maxlength="500" class="form-control form-control-sm">{{ old('excerpt', $post->excerpt) }}</textarea>
        </div></div>
    </div>
    <div class="col-xl-4">
        <div class="panel mb-3"><div class="panel-head">Publish</div><div class="panel-body small">
            <select name="status" class="form-select form-select-sm mb-2"><option value="draft" @selected(old('status', $post->status) === 'draft')>Draft</option><option value="published" @selected(old('status', $post->status) === 'published')>Published</option></select>
            <label class="form-label mb-0">Publish date (ভবিষ্যৎ দিলে scheduled)</label>
            <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="form-control form-control-sm mb-2">
            <label class="form-label mb-0">Category</label>
            <select name="blog_category_id" class="form-select form-select-sm mb-2"><option value="">—</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('blog_category_id', $post->blog_category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select>
            <label class="form-label mb-0">Tags (comma separated)</label>
            <input name="tags" value="{{ old('tags', $post->tags) }}" class="form-control form-control-sm mb-2" placeholder="saree, eid, style">
            <label class="form-label mb-0">Author</label>
            <input name="author_name" value="{{ old('author_name', $post->author_name) }}" class="form-control form-control-sm mb-3">
            <button class="btn btn-brand w-100">{{ $post->exists ? 'Update' : 'Save' }}</button>
            @if ($post->exists)<a href="{{ $post->url() }}" target="_blank" class="btn btn-light border w-100 mt-2">{{ $post->isLive() ? 'View' : 'Preview' }}</a>@endif
        </div></div>
        <div class="panel mb-3"><div class="panel-head">Cover image</div><div class="panel-body small">
            @if ($post->cover_url)<img src="{{ $post->cover_url }}" alt="" class="w-100 rounded mb-2"><label class="mb-2"><input type="checkbox" name="remove_cover" value="1" class="form-check-input"> Remove</label>@endif
            <input type="file" name="cover_file" accept="image/*" class="form-control form-control-sm">
        </div></div>
        <div class="panel"><div class="panel-head">SEO</div><div class="panel-body small">
            <label class="form-label mb-0">Meta title</label><input name="meta_title" value="{{ old('meta_title', $post->meta_title) }}" maxlength="70" class="form-control form-control-sm mb-2">
            <label class="form-label mb-0">Meta description</label><textarea name="meta_description" rows="3" maxlength="170" class="form-control form-control-sm">{{ old('meta_description', $post->meta_description) }}</textarea>
        </div></div>
    </div>
</div>
</form>
@if ($post->exists)
    @perm('blog.delete')<form method="POST" action="{{ route('manager.blog.destroy', $post) }}" onsubmit="return confirm('পোস্ট মুছবেন?')">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0">Delete post</button></form>@endperm
@endif
@endsection

@include('manager.ai._js')
@push('scripts')
<script>
document.getElementById('aiBlogBtn')?.addEventListener('click', async function () {
    const topic = document.getElementById('aiTopic').value.trim();
    if (!topic) { alert('বিষয় লিখুন।'); return; }
    const d = await mbAi(@json(route('manager.ai.blog')), {topic, keywords: document.getElementById('aiKw').value, lang: document.getElementById('aiBlogLang').value, length: document.getElementById('aiLen').value}, this);
    if (!d) return;
    const f = this.closest('form'), set = (n, v) => { const el = f.querySelector(`[name="${n}"]`); if (el && v) el.value = v; };
    set('title', d.title); set('excerpt', d.excerpt); set('meta_title', d.meta_title); set('meta_description', d.meta_description); set('tags', d.tags);
    const ed = f.querySelector('.ql-editor');
    if (ed && d.content) { ed.innerHTML = d.content; }
});
</script>
@endpush
