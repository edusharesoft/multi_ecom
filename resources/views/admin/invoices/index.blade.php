@extends('layouts.admin')
@section('title', 'Invoices & Payments')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    @foreach (['' => 'All', 'pending_verification' => 'To verify', 'unpaid' => 'Unpaid', 'paid' => 'Paid', 'failed' => 'Failed', 'cancelled' => 'Cancelled'] as $k => $v)
        <a href="{{ route('admin.invoices.index', array_filter(['status' => $k])) }}" class="btn btn-sm {{ request('status', '') === $k ? 'btn-dark' : 'btn-light' }}">{{ $v }}</a>
    @endforeach
    <span class="ms-auto small">Outstanding: <strong>৳ {{ number_format($pendingTotal, 2) }}</strong></span>
    <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#newInv"><i class="fa-solid fa-plus"></i> Issue Invoice</button>
</div>
<div class="collapse" id="newInv">
    <form method="POST" action="{{ route('admin.invoices.store') }}" class="panel mb-3 panel-body row g-2">@csrf
        <div class="col-md-3"><select name="branch_id" class="form-select form-select-sm" required><option value="">Branch…</option>@foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->store_name ?: $b->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><select name="plan_id" class="form-select form-select-sm" required>@foreach ($plans as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="billing_cycle" class="form-select form-select-sm"><option>monthly</option><option>yearly</option><option>lifetime</option></select></div>
        <div class="col-md-2"><input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm" placeholder="Custom amount"></div>
        <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Create</button></div>
    </form>
</div>
<div class="panel">
    <form class="panel-head gap-2 justify-content-start" method="GET">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:220px" placeholder="Invoice no / TrxID">
        <select name="branch" class="form-select form-select-sm" style="width:180px"><option value="">All branches</option>@foreach ($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->store_name ?: $b->name }}</option>@endforeach</select>
        <button class="btn btn-sm btn-dark">Filter</button>
    </form>
    <div class="table-responsive">
        <table class="table mb-0 small align-middle">
            <thead class="table-light"><tr><th>Invoice</th><th>Branch</th><th>Plan</th><th>Total</th><th>Payment</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($invoices as $inv)
                <tr>
                    <td><a href="{{ route('admin.invoices.show', $inv) }}" class="fw-semibold">{{ $inv->number }}</a><br><span class="text-muted">{{ $inv->created_at->format('d M Y') }} · {{ $inv->type }}</span></td>
                    <td>{{ $inv->branch->display_name }}</td>
                    <td>{{ $inv->plan?->name }} <span class="text-muted">{{ $inv->billing_cycle }}</span></td>
                    <td>৳ {{ number_format($inv->total, 2) }}@if($inv->discount > 0)<br><span class="text-success">−{{ number_format($inv->discount) }}</span>@endif</td>
                    <td>{{ $inv->payment_method ?? '—' }}@if($inv->transaction_id)<br><code>{{ $inv->transaction_id }}</code>@endif @if($inv->sender_number)<br><span class="text-muted">{{ $inv->sender_number }}</span>@endif</td>
                    <td><span class="badge bg-{{ $inv->status_color }}">{{ str_replace('_', ' ', $inv->status) }}</span>@if($inv->paid_at)<br><span class="text-muted">{{ $inv->paid_at->format('d M') }}</span>@endif</td>
                    <td class="text-end text-nowrap">
                        @if (in_array($inv->status, ['pending_verification', 'unpaid', 'failed'], true))
                            <form method="POST" action="{{ route('admin.invoices.approve', $inv) }}" class="d-inline" onsubmit="return confirm('Mark {{ $inv->number }} as PAID and activate the plan?')">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Approve</button></form>
                        @endif
                        @if ($inv->status === 'pending_verification')
                            <form method="POST" action="{{ route('admin.invoices.reject', $inv) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger">Reject</button></form>
                        @endif
                        @if (in_array($inv->status, ['unpaid', 'failed'], true))
                            <form method="POST" action="{{ route('admin.invoices.cancel', $inv) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Cancel</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5">No invoices.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $invoices->links() }}</div>
</div>
@endsection
