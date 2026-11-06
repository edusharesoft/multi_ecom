@extends('layouts.store')
@section('title', 'Contact — '.$branch->display_name)
@section('content')
<section class="section py-4">
    <div class="container" style="max-width:1120px">
        <div class="text-center mb-4">
            <div class="text-brand fw-bold small text-uppercase">{{ __('Contact') }}</div>
            <h1 class="fw-bold mb-1">{{ __('যোগাযোগ করুন') }}</h1>
            <p class="text-muted mb-0">{{ $branch->display_name }} — {{ __('আপনার মতামত আমাদের কাছে গুরুত্বপূর্ণ') }}</p>
        </div>
        @include('partials.flash')
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-5">
                <div class="bg-white rounded border p-4 h-100 shadow-sm">
                    <h2 class="h5 fw-bold mb-3">{{ __('দোকানের তথ্য') }}</h2>
                    <div class="d-flex gap-3 mb-3"><i class="fa-solid fa-location-dot text-brand mt-1"></i><div><div class="small text-muted">{{ __('ঠিকানা') }}</div><div>{{ $branch->address ?: __('ঠিকানা আপডেট করা হয়নি') }}</div></div></div>
                    <div class="d-flex gap-3 mb-3"><i class="fa-solid fa-phone text-brand mt-1"></i><div><div class="small text-muted">{{ __('মোবাইল') }}</div><a href="tel:{{ preg_replace('/[^\d+]/', '', $branch->store_phone ?: $branch->mobile) }}">{{ $branch->store_phone ?: $branch->mobile }}</a></div></div>
                    <div class="d-flex gap-3 mb-3"><i class="fa-regular fa-envelope text-brand mt-1"></i><div><div class="small text-muted">{{ __('ইমেইল') }}</div><a href="mailto:{{ $branch->email }}">{{ $branch->email }}</a></div></div>
                    @if ($branch->opening_hours)<div class="d-flex gap-3"><i class="fa-regular fa-clock text-brand mt-1"></i><div><div class="small text-muted">{{ __('খোলার সময়') }}</div><div>{{ $branch->opening_hours }}</div></div></div>@endif
                </div>
            </div>
            <div class="col-lg-7">
                <div class="bg-white rounded border p-4 h-100 shadow-sm">
                    <h2 class="h5 fw-bold mb-1">{{ __('ফিডব্যাক পাঠান') }}</h2>
                    <p class="text-muted small mb-3">{{ __('আপনার প্রশ্ন বা পরামর্শ লিখুন।') }}</p>
                    <form method="POST" action="{{ route('store.contact.submit', $branch) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">{{ __('নাম') }}</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">{{ __('ইমেইল') }}</label><input type="email" name="email" value="{{ old('email') }}" class="form-control" required></div>
                            <div class="col-12"><label class="form-label">{{ __('বিষয়') }}</label><input name="subject" value="{{ old('subject') }}" class="form-control" required></div>
                            <div class="col-12"><label class="form-label">{{ __('আপনার বার্তা') }}</label><textarea name="message" rows="5" class="form-control" required>{{ old('message') }}</textarea></div>
                            <div class="col-12"><button class="btn btn-brand px-4"><i class="fa-regular fa-paper-plane me-1"></i>{{ __('বার্তা পাঠান') }}</button></div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-12"><div class="bg-white rounded border overflow-hidden shadow-sm"><iframe title="{{ __('দোকানের Google Map') }}" src="{{ $branch->map_url ?: 'https://maps.google.com/maps?q='.urlencode($branch->address ?: $branch->display_name). '&output=embed' }}" style="width:100%;height:380px;border:0" loading="lazy" allowfullscreen></iframe><a class="d-block p-3 border-top text-brand" href="{{ $branch->map_url ?: 'https://www.google.com/maps/search/?api=1&query='.urlencode($branch->address ?: $branch->display_name) }}" target="_blank" rel="noopener">{{ __('Google Maps-এ পথ দেখুন') }} <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i></a></div></div>
        </div>
    </div>
</section>
@endsection
