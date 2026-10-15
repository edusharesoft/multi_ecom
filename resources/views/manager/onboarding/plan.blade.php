@extends('manager.onboarding.layout')
@section('title', 'Select Subscription')

@section('content')
<div class="text-center mb-4">
    <h2 class="fw-bold">আপনার ব্যবসার জন্য প্ল্যান বেছে নিন</h2>
    <p class="text-muted">স্বাগতম {{ $branch->name }}! যেকোনো সময় আপগ্রেড বা ডাউনগ্রেড করতে পারবেন।</p>
</div>
@include('manager.billing._plan-cards', ['plans' => $plans, 'action' => route('manager.onboarding.plan.choose'), 'sub' => null, 'branch' => $branch])
@endsection
