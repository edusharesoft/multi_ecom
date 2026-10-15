@extends('layouts.store')
@section('title', 'Profile — '.$store->display_name)

@section('content')
<section class="section pt-2">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">@include('store.account._nav')</div>
            <div class="col-lg-9">
                @if ($errors->any())<div class="alert alert-danger small"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                <div class="row g-3">
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('store.account.profile.update', $store) }}" class="pd-box">
                            @csrf @method('PUT')
                            <h6 class="fw-bold">Profile</h6>
                            <div class="mb-2"><label class="form-label small">Name</label><input name="name" value="{{ old('name', $customer->name) }}" class="form-control form-control-sm" required></div>
                            <div class="mb-2"><label class="form-label small">Mobile</label><input value="{{ $customer->phone }}" class="form-control form-control-sm" disabled></div>
                            <div class="mb-3"><label class="form-label small">Email</label><input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control form-control-sm"></div>
                            <button class="btn btn-brand btn-sm">Save</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="POST" action="{{ route('store.account.password', $store) }}" class="pd-box">
                            @csrf @method('PUT')
                            <h6 class="fw-bold">Change Password</h6>
                            <div class="mb-2"><input type="password" name="current_password" class="form-control form-control-sm" placeholder="Current password" required></div>
                            <div class="mb-2"><input type="password" name="password" class="form-control form-control-sm" placeholder="New password" required></div>
                            <div class="mb-3"><input type="password" name="password_confirmation" class="form-control form-control-sm" placeholder="Confirm new password" required></div>
                            <button class="btn btn-dark btn-sm">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
