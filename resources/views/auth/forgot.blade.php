@extends('layouts.auth')
@section('title', __('Forgot your password?'))
@section('heading', __('Forgot your password?'))
@section('subheading', 'ইউজার নেম, ইমেইল বা মোবাইল দিন — রেজিস্টার্ড মোবাইলে একটি কোড যাবে।')

@section('content')
<form method="POST" action="{{ route('password.forgot') }}">
    @csrf
    <div @class(['au-field', 'is-invalid' => $errors->has('login')])>
        <span class="ic"><i class="fa-regular fa-user"></i></span>
        <input name="login" value="{{ old('login', request('branch')) }}" placeholder="{{ __('Username, Email or Mobile') }}" required autofocus>
    </div>
    @error('login')<div class="au-err">{{ $message }}</div>@enderror
    <button class="au-btn"><i class="fa-solid fa-paper-plane"></i> {{ __('Send code') }}</button>
    <a href="{{ route('login') }}" class="au-btn-2"><i class="fa-solid fa-arrow-left"></i> {{ __('Back to login') }}</a>
</form>
<div class="au-note">স্টাফ অ্যাকাউন্টের পাসওয়ার্ড ভুলে গেলে দোকানের মালিক (Staff & Roles) থেকে নতুন পাসওয়ার্ড দিতে পারবেন।</div>
@endsection
