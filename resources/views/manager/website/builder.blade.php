@extends('layouts.manager')
@section('title', 'Website — Homepage Builder')

@section('content')
@include('manager.website._nav')
<p class="small text-muted">সেকশনগুলো টেনে (<i class="fa-solid fa-grip-vertical"></i>) ক্রম বদলান, টিক দিয়ে চালু/বন্ধ করুন, শিরোনাম ও কতটি পণ্য দেখাবে ঠিক করুন। নিজের টেক্সট বা ছবির ব্যানারও যোগ করা যায়।</p>
<form method="POST" action="{{ route('manager.website.builder.save') }}" enctype="multipart/form-data" id="builderForm">@csrf
    <div class="panel mb-3">
        <div id="secList" class="list-group list-group-flush">
            @foreach ($sections as $sec)
                @include('manager.website._section-row', ['sec' => $sec])
            @endforeach
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-dark" data-add="text"><i class="fa-solid fa-plus"></i> Text / HTML block</button>
        <button type="button" class="btn btn-sm btn-outline-dark" data-add="image_banner"><i class="fa-solid fa-plus"></i> Image banner</button>
        @perm('website.edit')
        <button class="btn btn-brand ms-auto">Save layout</button>
        @endperm
    </div>
</form>
@perm('website.edit')
<form method="POST" action="{{ route('manager.website.builder.reset') }}" class="mt-3" onsubmit="return confirm('ডিফল্ট লেআউটে ফিরবেন? নিজের ব্লকগুলো মুছে যাবে।')">@csrf<button class="btn btn-sm btn-link text-danger p-0">Reset to default layout</button></form>
@endperm

<template id="tpl-text">@include('manager.website._section-row', ['sec' => \App\Support\StoreTheme::blank('text')])</template>
<template id="tpl-image_banner">@include('manager.website._section-row', ['sec' => \App\Support\StoreTheme::blank('image_banner')])</template>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(() => {
    const list = document.getElementById('secList'), form = document.getElementById('builderForm');
    new Sortable(list, {handle: '.drag', animation: 150});
    const rand = () => Math.random().toString(36).slice(2, 8).padEnd(6, '0');
    document.querySelectorAll('[data-add]').forEach(b => b.addEventListener('click', () => {
        const type = b.dataset.add;
        const node = document.getElementById('tpl-' + type).content.firstElementChild.cloneNode(true);
        node.querySelector('[data-f=id]').value = type + '-' + rand();
        list.appendChild(node);
        node.scrollIntoView({behavior: 'smooth', block: 'center'});
    }));
    list.addEventListener('click', e => { if (e.target.closest('.rm') && confirm('এই ব্লক মুছবেন?')) e.target.closest('.list-group-item').remove(); });
    // rename inputs to sections[i][field] in the current order
    form.addEventListener('submit', () => {
        [...list.children].forEach((row, i) => row.querySelectorAll('[data-f]').forEach(inp => inp.name = `sections[${i}][${inp.dataset.f}]`));
    });
})();
</script>
@endpush
