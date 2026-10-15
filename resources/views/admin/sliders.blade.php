@extends('layouts.admin')
@section('title', __('Home Slider'))

@section('content')
<div class="panel mb-4">
    <div class="panel-head"><span><i class="fa-solid fa-images text-primary"></i> {{ __('Main website banner slideshow') }}</span></div>
    <div class="panel-body">
        <p class="small text-muted mb-3">মেইন ওয়েবসাইটের একদম উপরে ছবিগুলো একটার পর একটা ঘুরবে। ছবির উপরে কোনো লেখা বসবে না, তাই লেখা থাকলে ছবির ভেতরেই ডিজাইন করে দিন। মাপ <b>1600×500</b> পিক্সেল (JPG / PNG / WebP, সর্বোচ্চ 4 MB), সর্বোচ্চ {{ $max }}টি ছবি। কোনো ছবি না থাকলে Settings-এর Hero Image দেখাবে।</p>
        <form method="POST" action="{{ route('admin.sliders.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-4"><label class="form-label small fw-semibold">{{ __('Image') }} *</label><input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm" required></div>
            <div class="col-md-5"><label class="form-label small fw-semibold">{{ __('Link (optional)') }}</label><input name="link" value="{{ old('link') }}" class="form-control form-control-sm" placeholder="https://… অথবা /barishal"></div>
            <div class="col-md-1"><label class="form-label small fw-semibold">{{ __('Sort') }}</label><input type="number" name="sort" value="{{ count($slides) }}" min="0" class="form-control form-control-sm"></div>
            <div class="col-md-2"><button class="btn btn-brand btn-sm w-100" @disabled(count($slides) >= $max)><i class="fa-solid fa-plus"></i> {{ __('Add') }}</button></div>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse ($slides as $s)
        <div class="col-md-6 col-xl-4">
            <div class="panel h-100">
                <img src="{{ asset('storage/'.$s['image']) }}" alt="" class="w-100 rounded-top" style="aspect-ratio:16/5;object-fit:cover;background:#f3f4f6">
                <form method="POST" action="{{ route('admin.sliders.update', $s['id']) }}" enctype="multipart/form-data" class="panel-body small">
                    @csrf @method('PUT')
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-{{ ! empty($s['active']) ? 'success' : 'secondary' }}">{{ ! empty($s['active']) ? __('Active') : __('Hidden') }}</span>
                        <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" name="active" value="1" @checked(! empty($s['active'])) title="{{ __('Show on website') }}"></div>
                    </div>
                    <input name="link" value="{{ $s['link'] ?? '' }}" class="form-control form-control-sm mb-1" placeholder="{{ __('Link (optional)') }}">
                    <div class="d-flex gap-1 mb-2">
                        <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm" title="{{ __('Replace image') }}">
                        <input type="number" name="sort" value="{{ (int) ($s['sort'] ?? 0) }}" min="0" class="form-control form-control-sm" style="width:70px" title="{{ __('Sort') }}">
                    </div>
                    <button class="btn btn-sm btn-dark">{{ __('Save') }}</button>
                    <button type="submit" form="del-hs-{{ $loop->index }}" class="btn btn-sm btn-link text-danger">{{ __('Delete') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.sliders.destroy', $s['id']) }}" id="del-hs-{{ $loop->index }}" onsubmit="return confirm('Delete this slide?')">@csrf @method('DELETE')</form>
            </div>
        </div>
    @empty
        <div class="col-12 text-muted small">এখনো কোনো স্লাইড নেই।</div>
    @endforelse
</div>
@endsection
