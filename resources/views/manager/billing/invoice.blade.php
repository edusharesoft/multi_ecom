@extends(($adminView ?? false) ? 'layouts.admin' : 'layouts.manager')
@section('title', 'Invoice '.$invoice->number)

@section('content')
@php($admin = $adminView ?? false)
<div class="row g-4">
    <div class="{{ $invoice->isPayable() && ! $admin ? 'col-xl-7' : 'col-xl-9' }}">
        <div class="panel" id="printable">
            <div class="panel-body p-4">
                <div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
                    <div>
                        <h4 class="fw-bold text-brand mb-0">{{ \App\Models\Setting::siteName() }}</h4>
                        <div class="small text-muted">{{ \App\Models\Setting::get('site_address') }}<br>{{ \App\Models\Setting::get('site_phone') }} · {{ \App\Models\Setting::get('site_email') }}</div>
                    </div>
                    <div class="text-end">
                        <h5 class="fw-bold mb-0">INVOICE</h5>
                        <div class="small">{{ $invoice->number }}<br>Date: {{ $invoice->created_at->format('d M Y') }}@if($invoice->due_date)<br>Due: {{ $invoice->due_date->format('d M Y') }}@endif</div>
                        <span class="badge bg-{{ $invoice->status_color }} mt-1 text-uppercase">{{ str_replace('_', ' ', $invoice->status) }}</span>
                    </div>
                </div>
                <div class="small mb-4"><div class="text-muted">Bill to</div><strong>{{ $invoice->branch->display_name }}</strong><br>{{ $invoice->branch->name }} · {{ $invoice->branch->mobile }} · {{ $invoice->branch->email }}<br>{{ $invoice->branch->address }}</div>
                <table class="table small">
                    <thead class="table-light"><tr><th>Description</th><th>Period</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        <tr><td>{{ $invoice->plan?->name }} plan — {{ ucfirst($invoice->billing_cycle) }} ({{ $invoice->type }})</td>
                            <td>{{ $invoice->period_start?->format('d M Y') }} – {{ $invoice->period_end?->format('d M Y') ?? 'Lifetime' }}</td>
                            <td class="text-end">৳ {{ number_format($invoice->amount, 2) }}</td></tr>
                        @if ($invoice->discount > 0)<tr><td colspan="2">Credit / Discount</td><td class="text-end text-success">− ৳ {{ number_format($invoice->discount, 2) }}</td></tr>@endif
                    </tbody>
                    <tfoot><tr><th colspan="2" class="text-end">Total</th><th class="text-end">৳ {{ number_format($invoice->total, 2) }}</th></tr></tfoot>
                </table>
                @if ($invoice->notes)<p class="small text-muted">{{ $invoice->notes }}</p>@endif
                @if ($invoice->paid_at)<p class="small text-success mb-0">Paid {{ $invoice->paid_at->format('d M Y, h:i A') }} via {{ $invoice->payment_method }}{{ $invoice->transaction_id ? ' · TrxID '.$invoice->transaction_id : '' }}</p>@endif
                @if ($invoice->status === 'pending_verification')<p class="small text-warning mb-0">{{ $invoice->payment_method }} payment submitted — sender {{ $invoice->sender_number }}, TrxID {{ $invoice->transaction_id }}. Awaiting verification.</p>@endif
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-print"></i> Print / PDF</button>
            <a href="{{ $admin ? route('admin.invoices.index') : route('manager.billing') }}" class="btn btn-sm btn-link">← Back</a>
        </div>
    </div>
    @if ($invoice->isPayable() && ! $admin)
        @perm('subscription.edit')
        <div class="col-xl-5">
            <div class="panel"><div class="panel-head">Pay this invoice</div><div class="panel-body">@include('manager.billing._pay-form', ['invoice' => $invoice, 'pay' => $pay])</div></div>
        </div>
        @endperm
    @endif
</div>
@push('scripts')
<style>@media print{.mg-sidebar,.mg-topbar,.btn,.alert{display:none!important}.mg-main{margin:0}.panel{box-shadow:none}}</style>
@endpush
@endsection
