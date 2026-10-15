@extends('manager.onboarding.layout')
@section('title', 'Create Store')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('manager.onboarding.store.save') }}" enctype="multipart/form-data" class="panel">
            @csrf
            <div class="panel-head">আপনার স্টোর তৈরি করুন <span class="small text-muted fw-normal">{{ $branch->url }}</span></div>
            <div class="panel-body row g-3">
                <div class="col-md-6"><label class="form-label small fw-semibold">Business / Store Name *</label><input name="store_name" value="{{ old('store_name', $branch->store_name) }}" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Store Phone</label><input name="store_phone" value="{{ old('store_phone', $branch->store_phone ?? $branch->mobile) }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Logo</label><input type="file" name="logo" accept="image/*" class="form-control"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Favicon</label><input type="file" name="favicon" accept=".png,.ico,.jpg,.jpeg,.webp" class="form-control"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Banner (1600×500)</label><input type="file" name="banner" accept="image/*" class="form-control"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Banner Title</label><input name="hero_title" value="{{ old('hero_title', $branch->hero_title) }}" class="form-control" placeholder="{{ $branch->store_name }}"></div>
                <div class="col-md-6"><label class="form-label small fw-semibold">Banner Subtitle</label><input name="hero_subtitle" value="{{ old('hero_subtitle', $branch->hero_subtitle) }}" class="form-control" placeholder="সব পণ্যে ১০% ছাড়"></div>
                <div class="col-12"><label class="form-label small fw-semibold">About</label><textarea name="about" rows="2" class="form-control">{{ old('about', $branch->about) }}</textarea></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Delivery Charge (৳) *</label><input type="number" step="0.01" min="0" name="shipping_charge" value="{{ old('shipping_charge', $branch->shipping_charge ?? 60) }}" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">bKash (Send Money)</label><input name="bkash_number" value="{{ old('bkash_number', $branch->bkash_number) }}" class="form-control" placeholder="01XXXXXXXXX"></div>
                <div class="col-md-4"><label class="form-label small fw-semibold">Nagad (Send Money)</label><input name="nagad_number" value="{{ old('nagad_number', $branch->nagad_number) }}" class="form-control" placeholder="01XXXXXXXXX"></div>
                <div class="col-12"><label class="form-label small fw-semibold">Facebook Page</label><input name="facebook_url" value="{{ old('facebook_url', $branch->facebook_url) }}" class="form-control" placeholder="https://facebook.com/..."></div>
            </div>
            <div class="panel-body border-top text-end"><button class="btn btn-brand px-5 py-2 fw-semibold"><i class="fa-solid fa-rocket"></i> Create My Store</button></div>
        </form>
    </div>
</div>
@endsection
