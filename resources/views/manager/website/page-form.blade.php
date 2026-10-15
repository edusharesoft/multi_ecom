@extends('layouts.manager')
@section('title', $page->exists ? 'Edit page: '.$page->title : 'New page')

@section('content')
@include('manager.website._nav')
<form method="POST" action="{{ $page->exists ? route('manager.pages.update', $page) : route('manager.pages.store') }}">@csrf @if($page->exists) @method('PUT') @endif
<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel"><div class="panel-body">
            <input name="title" value="{{ old('title', $page->title) }}" class="form-control form-control-lg mb-2" placeholder="Page title" required>
            <div class="input-group input-group-sm mb-3"><span class="input-group-text">/{{ \App\Support\Tenancy::branch()->slug }}/page/</span><input name="slug" value="{{ old('slug', $page->slug) }}" class="form-control" placeholder="auto from title"></div>
            @include('manager.website._editor', ['name' => 'content', 'value' => old('content', $page->content), 'height' => 420])
        </div></div>
    </div>
    <div class="col-xl-4">
        <div class="panel mb-3"><div class="panel-body small">
            @foreach (['is_published' => 'Published', 'show_in_header' => 'Show in header menu', 'show_in_footer' => 'Show in footer'] as $k => $l)
                <div class="form-check mb-1"><input type="hidden" name="{{ $k }}" value="0"><input type="checkbox" class="form-check-input" name="{{ $k }}" value="1" @checked(old($k, $page->{$k}))><label class="form-check-label">{{ $l }}</label></div>
            @endforeach
            <label class="form-label mb-0 mt-2">Sort order</label><input type="number" name="sort" value="{{ old('sort', $page->sort ?? 0) }}" class="form-control form-control-sm" min="0">
        </div></div>
        <div class="panel mb-3"><div class="panel-head">SEO</div><div class="panel-body small">
            <label class="form-label mb-0">Meta title <span class="text-muted">(≤60)</span></label><input name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" maxlength="70" class="form-control form-control-sm mb-2">
            <label class="form-label mb-0">Meta description <span class="text-muted">(≤160)</span></label><textarea name="meta_description" rows="3" maxlength="170" class="form-control form-control-sm">{{ old('meta_description', $page->meta_description) }}</textarea>
        </div></div>
        <button class="btn btn-brand w-100 mb-2">{{ $page->exists ? 'Update page' : 'Create page' }}</button>
        @if ($page->exists)<a href="{{ route('store.page', [\App\Support\Tenancy::branch(), $page->slug]) }}" target="_blank" class="btn btn-light border w-100 mb-2">View</a>@endif
    </div>
</div>
</form>
@if ($page->exists)
    <form method="POST" action="{{ route('manager.pages.destroy', $page) }}" onsubmit="return confirm('পেজটি মুছবেন?')">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0">Delete page</button></form>
@endif
@endsection
