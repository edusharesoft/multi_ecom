@extends('layouts.store')
@section('title', 'Addresses — '.$store->display_name)

@section('content')
<section class="section pt-2">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">@include('store.account._nav')</div>
            <div class="col-lg-9">
                @if ($errors->any())<div class="alert alert-danger small"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                <div class="row g-3 mb-4">
                    @forelse ($addresses as $a)
                        <div class="col-md-6">
                            <div class="pd-box h-100 small">
                                <div class="d-flex justify-content-between"><strong>{{ $a->label }} @if($a->is_default)<span class="badge bg-success">Default</span>@endif</strong>
                                    <form method="POST" action="{{ route('store.account.addresses.delete', [$store, $a]) }}" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger p-0">Delete</button></form></div>
                                <div>{{ $a->name }} · {{ $a->phone }}</div>
                                <div class="text-muted">{{ $a->fullAddress() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted small">কোনো ঠিকানা সেভ করা নেই।</div>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('store.account.addresses.save', $store) }}" class="pd-box">
                    @csrf
                    <h6 class="fw-bold">Add Address</h6>
                    <div class="row g-2">
                        <div class="col-md-3"><select name="label" class="form-select form-select-sm"><option>Home</option><option>Office</option><option>Other</option></select></div>
                        <div class="col-md-5"><input name="name" value="{{ $storeCustomer->name }}" class="form-control form-control-sm" placeholder="Receiver name" required></div>
                        <div class="col-md-4"><input name="phone" value="{{ $storeCustomer->phone }}" class="form-control form-control-sm" placeholder="Mobile" required></div>
                        <div class="col-md-4"><input name="district" class="form-control form-control-sm" placeholder="জেলা (District)"></div>
                        <div class="col-md-4"><input name="upazila" class="form-control form-control-sm" placeholder="উপজেলা / থানা"></div>
                        <div class="col-md-4"><input name="area" class="form-control form-control-sm" placeholder="এলাকা / ইউনিয়ন"></div>
                        <div class="col-12"><textarea name="address" rows="2" class="form-control form-control-sm" placeholder="বাড়ি, রোড, বিস্তারিত ঠিকানা" required></textarea></div>
                        <div class="col-12 d-flex justify-content-between"><div class="form-check small"><input class="form-check-input" type="checkbox" name="is_default" value="1" id="def"><label class="form-check-label" for="def">Default address</label></div><button class="btn btn-brand btn-sm">Save Address</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
