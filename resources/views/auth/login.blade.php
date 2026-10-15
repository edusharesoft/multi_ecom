@extends('layouts.auth')
@section('title', __('Login'))
@section('heading', __('Login To Your Account'))

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div @class(['au-field', 'is-invalid' => $errors->has('email')])>
        <span class="ic"><i class="fa-regular fa-user"></i></span>
        <input name="email" value="{{ old('email') }}" placeholder="{{ __('Enter your Username or Email') }}" autocomplete="username" required autofocus>
    </div>
    @error('email')<div class="au-err">{{ $message }}</div>@enderror
    <div class="au-field">
        <span class="ic"><i class="fa-solid fa-lock"></i></span>
        <input type="password" name="password" placeholder="{{ __('Enter Password') }}" autocomplete="current-password" required>
        <button type="button" class="eye" tabindex="-1" aria-label="Show password"><i class="fa-regular fa-eye-slash"></i></button>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
        <label class="form-check-label" for="remember">{{ __('Remember me') }}</label>
    </div>
    <button class="au-btn">{{ __('Login') }}</button>
    <a href="{{ route('password.forgot') }}" class="au-btn-2">{{ __('Forgot your password?') }}</a>
</form>
@if (\App\Models\Setting::get('allow_registration', '1'))
    <div class="au-links">{{ __("Don't have a store?") }} <a href="{{ route('register') }}">{{ __('Create your store') }}</a></div>
@endif
@endsection
