@extends('layouts.manager')
@section('title', 'Subscription & Billing')

@section('content')
@if ($openInvoice)
    <div class="alert alert-{{ $openInvoice->status === 'pending_verification' ? 'info' : 'warning' }} d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>
            @if ($openInvoice->status === 'pending_verification')
                <i class="fa-solid fa-hourglass-half"></i> ইনভয়েস <strong>{{ $openInvoice->number }}</strong> এর পেমেন্ট ভেরিফিকেশনের অপেক্ষায় (TrxID {{ $openInvoice->transaction_id }})।
            @else
                <i class="fa-solid fa-file-invoice"></i> ইনভয়েস <strong>{{ $openInvoice->number }}</strong> — ৳ {{ number_format($openInvoice->total, 2) }} পরিশোধ বাকি{{ $openInvoice->due_date ? ' (শেষ তারিখ '.$openInvoice->due_date->format('d M Y').')' : '' }}।
            @endif
        </span>
        <a href="{{ route('manager.billing.invoice', $openInvoice) }}" class="btn btn-sm btn-dark">{{ $openInvoice->isPayable() ? 'Pay Now' : 'View' }}</a>
    </div>
@endif

<div class="row g-4">
    <div class="col-xl-5">
        <div class="panel mb-4">
            <div class="panel-head">Current Plan
                @if ($sub)<span class="badge bg-{{ $sub->status_color }}">{{ $sub->status_label }}</span>@endif
            </div>
            <div class="panel-body">
                <h3 class="fw-bold mb-0">{{ $plan->name }}</h3>
                @if ($sub && ! $sub->isUsable() && $sub->plan_id !== $plan->id)
                    <div class="small text-danger">নির্বাচিত প্ল্যান: {{ $sub->plan->name }} — পেমেন্ট না হওয়া পর্যন্ত Free লিমিট চলছে।</div>
                @endif
                @if ($sub)
                    <table class="table table-sm small mt-3 mb-0">
                        <tr><td class="text-muted">Billing cycle</td><td>{{ ucfirst($sub->billing_cycle) }} @if($sub->amount > 0)· ৳ {{ number_format($sub->amount, 2) }}@endif</td></tr>
                        <tr><td class="text-muted">Start date</td><td>{{ $sub->starts_at?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><td class="text-muted">{{ $sub->status === 'trial' ? 'Trial ends' : 'Expiry date' }}</td><td>{{ $sub->ends_at?->format('d M Y') ?? 'Never' }} @if($sub->daysLeft() !== null && $sub->isUsable())<span class="badge bg-light text-dark">{{ $sub->daysLeft() }} দিন বাকি</span>@endif</td></tr>
                        @if ($sub->status === 'grace')<tr><td class="text-muted">Grace period ends</td><td class="text-danger fw-semibold">{{ $sub->grace_ends_at?->format('d M Y') }}</td></tr>@endif
                        <tr><td class="text-muted">Auto renewal</td><td>{!! $sub->auto_renew ? '<span class="text-success">On</span>' : '<span class="text-danger">Off</span>' !!}</td></tr>
                        @if ($sub->nextPlan)<tr><td class="text-muted">Scheduled change</td><td>{{ $sub->nextPlan->name }} ({{ $sub->next_cycle }}) — {{ $sub->ends_at?->format('d M Y') }} থেকে</td></tr>@endif
                    </table>
                @endif
                @perm('subscription.edit')
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="{{ route('manager.billing.plans') }}" class="btn btn-brand btn-sm"><i class="fa-solid fa-arrow-up"></i> Upgrade / Change Plan</a>
                    @if ($sub && ! $sub->plan->is_free && $sub->billing_cycle !== 'lifetime')
                        <form method="POST" action="{{ route('manager.billing.renew') }}">@csrf<button class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-rotate"></i> Renew</button></form>
                        @if ($sub->isUsable())
                            @if ($sub->auto_renew)
                                <form method="POST" action="{{ route('manager.billing.cancel') }}" onsubmit="return confirm('অটো-রিনিউয়াল বন্ধ করবেন? বর্তমান মেয়াদ শেষে প্ল্যান বন্ধ হবে।')">@csrf<button class="btn btn-outline-danger btn-sm">Cancel Subscription</button></form>
                            @else
                                <form method="POST" action="{{ route('manager.billing.resume') }}">@csrf<button class="btn btn-outline-success btn-sm">Resume Auto-renewal</button></form>
                            @endif
                        @endif
                    @endif
                </div>
                @endperm
            </div>
        </div>

        <div class="panel">
            <div class="panel-head">Features</div>
            <div class="panel-body small">
                <div class="row g-2">
                    @foreach (\App\Models\Plan::FEATURES as $key => $label)
                        <div class="col-6">
                            @if ($plan->hasFeature($key))<i class="fa-solid fa-circle-check text-success"></i> {{ $label }}
                            @else<span class="text-muted"><i class="fa-solid fa-lock"></i> {{ $label }}</span>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-7">
        <div class="panel mb-4">
            <div class="panel-head">Usage</div>
            <div class="panel-body">
                @foreach ($usage as $key => $u)
                    @php
                        $unlimited = $u['limit'] < 0;
                        $pct = $unlimited ? 0 : ($u['limit'] > 0 ? min(100, $u['used'] / $u['limit'] * 100) : 100);
                        $color = $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : 'success');
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small"><span>{{ $u['label'] }}</span>
                            <span>{{ $key === 'monthly_sales_limit' ? '৳ '.number_format($u['used']) : $u['used'] }} / {{ \App\Models\Plan::limitLabel($u['limit'], $key) }}</span></div>
                        <div class="progress" style="height:7px"><div class="progress-bar bg-{{ $unlimited ? 'info' : $color }}" style="width:{{ $unlimited ? 8 : $pct }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="panel mb-4">
            <div class="panel-head">Invoices & Payment History</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 small">
                    <thead class="table-light"><tr><th>Invoice</th><th>Plan</th><th>Period</th><th>Total</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($invoices as $inv)
                        <tr>
                            <td><strong>{{ $inv->number }}</strong><br><span class="text-muted">{{ $inv->created_at->format('d M Y') }} · {{ $inv->type }}</span></td>
                            <td>{{ $inv->plan?->name }}<br><span class="text-muted">{{ $inv->billing_cycle }}</span></td>
                            <td>{{ $inv->period_start?->format('d M y') }} – {{ $inv->period_end?->format('d M y') ?? '∞' }}</td>
                            <td>৳ {{ number_format($inv->total, 2) }}</td>
                            <td><span class="badge bg-{{ $inv->status_color }}">{{ str_replace('_', ' ', $inv->status) }}</span>@if($inv->payment_method)<br><span class="text-muted">{{ $inv->payment_method }}</span>@endif</td>
                            <td class="text-end"><a href="{{ route('manager.billing.invoice', $inv) }}" class="btn btn-sm btn-light">{{ $inv->isPayable() ? 'Pay' : 'View' }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">কোনো ইনভয়েস নেই।</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="panel-body py-2">{{ $invoices->links() }}</div>
        </div>

        <div class="panel">
            <div class="panel-head">Subscription History</div>
            <ul class="list-group list-group-flush small">
                @forelse ($history as $h)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $h->description }}</span><span class="text-muted text-nowrap ms-2">{{ $h->created_at->format('d M Y, h:i A') }}</span></li>
                @empty
                    <li class="list-group-item text-muted">—</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
