@extends('layouts.manager')
@section('title', 'Cash Book')

@section('content')
@include('manager.accounts._nav')
<form class="panel mb-3 d-print-none" method="GET"><div class="panel-body d-flex flex-wrap gap-2 small align-items-center">
    <select name="account" class="form-select form-select-sm" style="width:170px"><option value="">All accounts (Day book)</option>@foreach ($accounts as $a)<option value="{{ $a->id }}" @selected($accountId === $a->id)>{{ $a->name }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control form-control-sm" style="width:140px">
    <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control form-control-sm" style="width:140px">
    <button class="btn btn-sm btn-dark">Show</button>
    @perm('reports.export')<a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-csv"></i> CSV</a>@endperm
    <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-print"></i> Print</button>
</div></form>

<div class="row g-3 mb-3">
    @foreach ([['Opening balance', $opening, '#e0f2fe', '#0369a1'], ['Total in', $in, '#dcfce7', '#15803d'], ['Total out', $out, '#fee2e2', '#b91c1c'], ['Closing balance', $closing, '#fef3c7', '#b45309']] as [$l, $v, $bg, $c])
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="ic" style="background:{{ $bg }};color:{{ $c }}"><i class="fa-solid fa-coins"></i></div><div><div class="v">৳ {{ number_format($v, 2) }}</div><div class="l">{{ $l }}</div></div></div></div>
    @endforeach
</div>

<div class="panel">
    <div class="panel-head">{{ $accountId ? $accounts->firstWhere('id', $accountId)?->name.' — Cash Book' : 'Day Book (all accounts)' }} · {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div>
    <div class="table-responsive"><table class="table table-sm mb-0 small">
        <thead class="table-light"><tr><th>Date</th>@unless($accountId)<th>Account</th>@endunless<th>Type</th><th>Description</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-end">Balance</th></tr></thead>
        <tr class="table-light fw-semibold"><td colspan="{{ $accountId ? 5 : 6 }}">Opening balance</td><td class="text-end">{{ number_format($opening, 2) }}</td></tr>
        @forelse ($rows as $r)
            <tr>
                <td class="text-nowrap">{{ $r->txn_date->format('d M Y') }}</td>
                @unless($accountId)<td>{{ $r->account?->name }}</td>@endunless
                <td><span class="badge bg-light text-dark border">{{ \App\Models\AccountTransaction::CATEGORIES[$r->category] ?? $r->category }}</span></td>
                <td>{{ $r->description }} <span class="text-muted">{{ $r->actor_name }}</span></td>
                <td class="text-end text-success">{{ $r->direction === 'in' ? number_format($r->amount, 2) : '' }}</td>
                <td class="text-end text-danger">{{ $r->direction === 'out' ? number_format($r->amount, 2) : '' }}</td>
                <td class="text-end fw-semibold">{{ number_format($r->running, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">এই সময়ে কোনো লেনদেন নেই।</td></tr>
        @endforelse
        <tfoot><tr class="fw-bold"><td colspan="{{ $accountId ? 3 : 4 }}" class="text-end">Total / Closing</td><td class="text-end">{{ number_format($in, 2) }}</td><td class="text-end">{{ number_format($out, 2) }}</td><td class="text-end">{{ number_format($closing, 2) }}</td></tr></tfoot>
    </table></div>
</div>
@endsection
