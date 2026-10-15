@extends('layouts.auth')
@section('title', __('Set a new password'))
@section('heading', __('Set a new password'))
@section('subheading')
    @if ($hint)
        কোড পাঠানো হয়েছে <strong>{{ $hint }}</strong> নম্বরে।
    @else
        রেজিস্টার্ড মোবাইলে আসা কোডটি দিন।
    @endif
@endsection
@section('auth_video', 'off')

@section('content')
@if (session('dev_otp'))
    <div class="alert alert-warning small text-center py-2"><strong>Test mode:</strong> কোড: <strong class="fs-5">{{ session('dev_otp') }}</strong></div>
@endif
<form method="POST" action="{{ route('password.reset') }}">
    @csrf
    <div @class(['au-field', 'is-invalid' => $errors->has('code')])>
        <span class="ic"><i class="fa-solid fa-key"></i></span>
        <input name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="{{ __('Code from SMS') }}" required autofocus style="letter-spacing:3px">
    </div>
    @error('code')<div class="au-err">{{ $message }}</div>@enderror
    <div @class(['au-field', 'is-invalid' => $errors->has('password')])>
        <span class="ic"><i class="fa-solid fa-lock"></i></span>
        <input type="password" name="password" placeholder="{{ __('New password (min 8)') }}" autocomplete="new-password" required>
        <button type="button" class="eye" tabindex="-1"><i class="fa-regular fa-eye-slash"></i></button>
    </div>
    @error('password')<div class="au-err">{{ $message }}</div>@enderror
    <div class="au-field">
        <span class="ic"><i class="fa-solid fa-lock"></i></span>
        <input type="password" name="password_confirmation" placeholder="{{ __('Confirm password') }}" autocomplete="new-password" required>
        <button type="button" class="eye" tabindex="-1"><i class="fa-regular fa-eye-slash"></i></button>
    </div>
    <button class="au-btn"><i class="fa-solid fa-check"></i> {{ __('Change password') }}</button>
    <a href="{{ route('password.forgot') }}" class="au-btn-2">{{ __('Send a new code') }}</a>
</form>
@endsection
