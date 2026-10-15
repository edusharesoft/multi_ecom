@extends('layouts.manager')
@section('title', 'Slider & Banner')

@section('content')
@include('manager.website._nav')
<p class="small text-muted">Slider-এর ছবিগুলো হোমপেজের একদম উপরে একটার পর একটা ঘুরবে (1600×500)। <b>ছবির উপরে কোনো লেখা বসবে না</b>, তাই লেখা থাকলে ছবির ভেতরেই ডিজাইন করে দিন। Banner হোমপেজের মাঝখানে দেখাবে (সর্বোচ্চ ৩টি, 1200×520)।</p>
@perm('website.edit')
<form method="POST" action="{{ route('manager.sliders.store') }}" enctype="multipart/form-data" class="panel mb-4">
    @csrf
    <div class="panel-body row g-2">
        <div class="col-md-2"><select name="position" class="form-select form-select-sm"><option value="slider">Slider</option><option value="banner">Banner</option></select></div>
        <div class="col-md-4"><input type="file" name="image" accept="image/*" class="form-control form-control-sm" required></div>
        <div class="col-md-4"><input name="link" class="form-control form-control-sm" placeholder="Link, ঐচ্ছিক (e.g. {{ url($tenant->slug.'/products?sale=1') }})"></div>
        <div class="col-md-1"><input type="number" name="sort" value="0" class="form-control form-control-sm" placeholder="Sort" title="Sort"></div>
        <div class="col-md-1"><button class="btn btn-brand btn-sm w-100"><i class="fa-solid fa-plus"></i> Add</button></div>
        <div class="col-md-6"><input name="title" class="form-control form-control-sm" placeholder="ছবির নাম (শুধু SEO-র জন্য, ওয়েবসাইটে দেখাবে না)"></div>
    </div>
</form>
@endperm
<div class="row g-3">
    @forelse ($sliders as $s)
        <div class="col-md-6 col-xl-4">
            <div class="panel h-100">
                <img src="{{ $s->image_url }}" class="w-100 rounded-top" style="height:140px;object-fit:cover">
                <form method="POST" action="{{ route('manager.sliders.update', $s) }}" enctype="multipart/form-data" class="panel-body small">@csrf @method('PUT')
                    <div class="d-flex justify-content-between mb-2"><span class="badge bg-{{ $s->position === 'slider' ? 'primary' : 'info' }}">{{ ucfirst($s->position) }}</span>
                        <div class="form-check form-switch m-0"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($s->is_active)></div></div>
                    <input type="hidden" name="position" value="{{ $s->position }}">
                    <input name="title" value="{{ $s->title }}" class="form-control form-control-sm mb-1" placeholder="ছবির নাম (দেখাবে না)">
                    <div class="d-flex gap-1 mb-2"><input name="link" value="{{ $s->link }}" class="form-control form-control-sm" placeholder="Link (ঐচ্ছিক)"><input type="number" name="sort" value="{{ $s->sort }}" class="form-control form-control-sm" style="width:70px" title="Sort"></div>
                    <input type="file" name="image" accept="image/*" class="form-control form-control-sm mb-2">
                    @perm('website.edit')<button class="btn btn-sm btn-dark">Save</button>
                    <button type="submit" form="del-sl-{{ $s->id }}" class="btn btn-sm btn-link text-danger">Delete</button>@endperm
                </form>
                <form method="POST" action="{{ route('manager.sliders.destroy', $s) }}" id="del-sl-{{ $s->id }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')</form>
            </div>
        </div>
    @empty
        <div class="col-12 text-muted small">কোনো স্লাইডার নেই — না থাকলে Settings-এর Banner ছবিটি দেখাবে।</div>
    @endforelse
</div>
@endsection
