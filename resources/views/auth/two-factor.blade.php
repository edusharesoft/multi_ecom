@extends('layouts.auth')
@section('title', __('Two-factor verification'))
@section('heading', __('Two-factor verification'))
@section('subheading', 'Authenticator অ্যাপের ৬ অঙ্কের কোড দিন, অথবা একটি Recovery code।')
@section('auth_video', 'off')
@if (! empty($platform))
    @section('auth_brand', 'platform')
@endif

@section('content')
<form method="POST" action="{{ $action }}">@csrf
    <div @class(['au-field', 'is-invalid' => $errors->has('code')])>
        <span class="ic"><i class="fa-solid fa-shield-halved"></i></span>
        <input name="code" inputmode="numeric" autocomplete="one-time-code" placeholder="123456" autofocus required style="letter-spacing:4px;font-size:18px;text-align:center">
    </div>
    @error('code')<div class="au-err">{{ $message }}</div>@enderror
    <button class="au-btn">{{ __('Verify') }}</button>
</form>
@endsection
