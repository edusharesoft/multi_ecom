@extends('layouts.manager')
@section('title', 'Customer Report')

@section('content')
@include('manager.reports._nav')
@include('manager.reports._filter')
<div class="row g-3 mb-3">
    @foreach ([['New registered customers', $new], ['Unique buyers', $buyers], ['Repeat buyers (2+ orders)', $repeat], ['Repeat rate', ($buyers ? round($repeat / $buyers * 100, 1) : 0).'%']] as [$l, $v])
        <div class="col-6 col-lg-3"><div class="stat-card"><div><div class="v">{{ $v }}</div><div class="l">{{ $l }}</div></div></div></div>
    @endforeach
</div>
<div class="panel">
    <div class="panel-head">Top customers · {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0 small">
        <thead class="table-light"><tr><th>#</th><th>Customer</th><th>Phone</th><th class="text-end">Orders</th><th class="text-end">Spent (net)</th><th>Last order</th></tr></thead>
        @forelse ($top as $r)
            <tr><td>{{ $loop->iteration }}</td>
                <td>@if($r->cid)<a href="{{ route('manager.customers.show', $r->cid) }}">{{ $r->name }}</a>@else{{ $r->name }} <span class="badge bg-light text-dark">guest</span>@endif</td>
                <td>{{ $r->phone }}</td><td class="text-end">{{ $r->c }}</td><td class="text-end fw-semibold">{{ number_format($r->spent, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($r->last_at)->format('d M Y') }}</td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No orders.</td></tr>
        @endforelse
    </table></div>
</div>
@endsection
