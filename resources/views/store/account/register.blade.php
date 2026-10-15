@extends('layouts.auth')
@section('title', __('Register'))
@section('heading', __('Create Account'))
@section('subheading', $store->display_name)
@section('auth_kind', 'register')

@section('content')
@if ($errors->any())<div class="alert alert-danger small py-2"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('store.account.register', $store) }}">
    @csrf
    <div @class(['au-field', 'is-invalid' => $errors->has('name')])><span class="ic"><i class="fa-regular fa-user"></i></span><input name="name" value="{{ old('name') }}" placeholder="{{ __('Full Name') }} *" required></div>
    <div @class(['au-field', 'is-invalid' => $errors->has('phone')])><span class="ic"><i class="fa-solid fa-mobile-screen"></i></span><input name="phone" value="{{ old('phone') }}" placeholder="{{ __('Mobile') }} (01XXXXXXXXX) *" inputmode="tel" required></div>
    <div @class(['au-field', 'is-invalid' => $errors->has('email')])><span class="ic"><i class="fa-regular fa-envelope"></i></span><input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Email (optional)') }}"></div>
    <div @class(['au-field', 'is-invalid' => $errors->has('password')])><span class="ic"><i class="fa-solid fa-lock"></i></span><input type="password" name="password" placeholder="{{ __('Password') }} *" autocomplete="new-password" required><button type="button" class="eye" tabindex="-1"><i class="fa-regular fa-eye-slash"></i></button></div>
    <div class="au-field"><span class="ic"><i class="fa-solid fa-lock"></i></span><input type="password" name="password_confirmation" placeholder="{{ __('Confirm password') }} *" autocomplete="new-password" required><button type="button" class="eye" tabindex="-1"><i class="fa-regular fa-eye-slash"></i></button></div>
    <button class="au-btn">{{ __('Register') }}</button>
    <a href="{{ route('store.account.login', $store) }}" class="au-btn-2">{{ __('Already have an account? Login') }}</a>
</form>
@endsection
