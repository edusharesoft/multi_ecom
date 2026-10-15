@extends('manager.onboarding.layout')
@section('title', 'Payment')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-head">ইনভয়েস {{ $invoice->number }}</div>
            <div class="panel-body">
                <div class="summary-row"><span>{{ $invoice->plan?->name }} — {{ ucfirst($invoice->billing_cycle) }}</span><span>৳ {{ number_format($invoice->amount, 2) }}</span></div>
                @if ($invoice->discount > 0)<div class="summary-row text-success"><span>Credit</span><span>− ৳ {{ number_format($invoice->discount, 2) }}</span></div>@endif
                <div class="summary-row total"><span>মোট</span><span>৳ {{ number_format($invoice->total, 2) }}</span></div>
                <hr>
                @include('manager.billing._pay-form', ['invoice' => $invoice, 'pay' => $pay])
                <form method="POST" action="{{ route('manager.onboarding.payment.skip', $invoice) }}" class="text-center mt-3">@csrf
                    <button class="btn btn-link btn-sm text-muted">পরে পেমেন্ট করব — এখন Free লিমিটে স্টোর সেটআপ করি →</button>
                </form>
                <div class="text-center"><a href="{{ route('manager.onboarding.plan') }}" class="small">← প্ল্যান পরিবর্তন</a></div>
            </div>
        </div>
    </div>
</div>
@endsection
