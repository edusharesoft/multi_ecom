@extends('layouts.admin')
@section('title', 'Categories')

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data" class="panel">
            @csrf
            <div class="panel-head">Add Category / Subcategory</div>
            <div class="panel-body">
                <p class="small text-muted">ক্যাটাগরি ও সাবক্যাটাগরি সব ব্রাঞ্চে একসাথে ব্যবহৃত হয় (মেনু, ফিল্টার ও কুপনে)।</p>
                <div class="mb-2"><label class="form-label small fw-semibold">Parent</label>
                    <select name="parent_id" class="form-select"><option value="">— Top level category —</option>@foreach ($parents as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
                <div class="mb-2"><label class="form-label small fw-semibold">Name *</label><input name="name" class="form-control" required></div>
                <div class="mb-2"><label class="form-label small fw-semibold">Tagline</label><input name="tagline" class="form-control" placeholder="Latest trends"></div>
                <div class="mb-2"><label class="form-label small fw-semibold">Sort order</label><input type="number" name="sort" value="0" class="form-control"></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Image</label><input type="file" name="image" accept="image/*" class="form-control"></div>
                <button class="btn btn-primary w-100">Add</button>
            </div>
        </form>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <div class="panel-head">All Categories</div>
            <div class="table-responsive">
                <table class="table mb-0 small align-middle">
                    <thead class="table-light"><tr><th></th><th>Name / Tagline / Parent</th><th>Sort</th><th>Products</th><th class="text-end"></th></tr></thead>
                    <tbody>
                    @foreach ($categories->flatMap(fn ($c) => collect([$c])->merge($c->children)) as $c)
                        <tr class="{{ $c->parent_id ? '' : 'table-light' }}">
                            <td>@if($c->parent_id)<span class="text-muted ms-2">↳</span>@else<img src="{{ $c->image_url }}" class="thumb-sm">@endif</td>
                            <td>
                                <form method="POST" action="{{ route('admin.categories.update', $c) }}" enctype="multipart/form-data" id="cat-{{ $c->id }}" class="d-flex gap-1 flex-wrap">
                                    @csrf @method('PUT')
                                    <input name="name" value="{{ $c->name }}" class="form-control form-control-sm {{ $c->parent_id ? '' : 'fw-bold' }}" style="max-width:160px" required>
                                    <input name="tagline" value="{{ $c->tagline }}" class="form-control form-control-sm" style="max-width:150px" placeholder="Tagline">
                                    @if ($c->parent_id || $c->children->isEmpty())
                                        <select name="parent_id" class="form-select form-select-sm" style="max-width:150px"><option value="">Top level</option>@foreach ($parents as $p)@continue($p->id === $c->id)<option value="{{ $p->id }}" @selected($c->parent_id === $p->id)>{{ $p->name }}</option>@endforeach</select>
                                    @endif
                                    <input type="file" name="image" accept="image/*" class="form-control form-control-sm" style="max-width:170px">
                                </form>
                            </td>
                            <td><input type="number" name="sort" value="{{ $c->sort }}" form="cat-{{ $c->id }}" class="form-control form-control-sm" style="width:64px"></td>
                            <td>{{ $c->products_count }}</td>
                            <td class="text-end text-nowrap">
                                <button form="cat-{{ $c->id }}" class="btn btn-sm btn-light"><i class="fa-solid fa-floppy-disk"></i></button>
                                <form method="POST" action="{{ route('admin.categories.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Delete? Subcategories are deleted too; products become uncategorised.')">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
