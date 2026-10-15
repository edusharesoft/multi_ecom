@extends('layouts.auth')
@section('title', 'Super Admin Login')
@section('heading', __('Super Admin Login'))
@section('subheading', __('Sign in to manage all branches.'))
@section('auth_brand', 'platform')

@section('content')
<form method="POST" action="{{ route('admin.login') }}">
    @csrf
    <div @class(['au-field', 'is-invalid' => $errors->has('email')])>
        <span class="ic"><i class="fa-regular fa-envelope"></i></span>
        <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Enter your Email') }}" autocomplete="username" required autofocus>
    </div>
    @error('email')<div class="au-err">{{ $message }}</div>@enderror
    <div class="au-field">
        <span class="ic"><i class="fa-solid fa-lock"></i></span>
        <input type="password" name="password" placeholder="{{ __('Enter Password') }}" autocomplete="current-password" required>
        <button type="button" class="eye" tabindex="-1" aria-label="Show password"><i class="fa-regular fa-eye-slash"></i></button>
    </div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" value="1" id="rm"><label class="form-check-label" for="rm">{{ __('Remember me') }}</label></div>
    <button class="au-btn">{{ __('Login') }}</button>
</form>
@endsection
