@extends('layouts.manager')
@section('title', 'Choose a Plan')

@section('content')
<p class="text-muted small text-center">আপগ্রেড সাথে সাথে কার্যকর হয় (বর্তমান প্ল্যানের বাকি দিনের মূল্য ক্রেডিট হিসেবে কাটা হবে)। ডাউনগ্রেড বর্তমান মেয়াদ শেষে কার্যকর হয়।</p>
@include('manager.billing._plan-cards', ['plans' => $plans, 'action' => route('manager.billing.change'), 'sub' => $sub, 'branch' => $branch])
@endsection
