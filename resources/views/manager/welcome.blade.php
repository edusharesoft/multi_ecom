@extends('layouts.manager')
@section('title', 'Welcome')

@section('content')
<div class="panel" style="max-width:640px">
    <div class="panel-body text-center py-5">
        <i class="fa-regular fa-hand fa-2x text-brand mb-3"></i>
        <h5 class="fw-bold">স্বাগতম, {{ $actor->name }}</h5>
        <p class="text-muted small mb-0">বাম পাশের মেনু থেকে আপনার অনুমতিপ্রাপ্ত মডিউলগুলো ব্যবহার করুন।</p>
    </div>
</div>
@endsection
