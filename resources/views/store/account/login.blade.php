@extends('layouts.auth')
@section('title', __('Login'))
@section('heading', __('Login To Your Account'))
@section('subheading', $store->display_name)

@section('content')
<form method="POST" action="{{ route('store.account.login', $store) }}">
    @csrf
    <div @class(['au-field', 'is-invalid' => $errors->has('login')])>
        <span class="ic"><i class="fa-regular fa-user"></i></span>
        <input name="login" value="{{ old('login') }}" placeholder="{{ __('Mobile or Email') }}" autocomplete="username" required autofocus>
    </div>
    @error('login')<div class="au-err">{{ $message }}</div>@enderror
    <div class="au-field">
        <span class="ic"><i class="fa-solid fa-lock"></i></span>
        <input type="password" name="password" placeholder="{{ __('Enter Password') }}" autocomplete="current-password" required>
        <button type="button" class="eye" tabindex="-1" aria-label="Show password"><i class="fa-regular fa-eye-slash"></i></button>
    </div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" value="1" id="rem"><label class="form-check-label" for="rem">{{ __('Remember me') }}</label></div>
    <button class="au-btn">{{ __('Login') }}</button>
    <button type="button" class="au-btn-2" data-bs-toggle="collapse" data-bs-target="#forgotHelp">{{ __('Forgot your password?') }}</button>
    <div id="forgotHelp" class="collapse au-note">পাসওয়ার্ড ভুলে গেলে দোকানে যোগাযোগ করুন: <a href="tel:{{ $store->store_phone ?: $store->mobile }}"><strong>{{ $store->store_phone ?: $store->mobile }}</strong></a> — তারা নতুন পাসওয়ার্ড সেট করে দেবে।</div>
</form>
<div class="au-links">নতুন? <a href="{{ route('store.account.register', $store) }}">অ্যাকাউন্ট খুলুন</a></div>
@endsection
