@extends('layouts.manager')
@section('title', 'Product Sales & Profit')

@section('content')
@include('manager.reports._nav')
@include('manager.reports._filter', ['fields' => [
    'category' => ['' => 'All categories'] + $categories->pluck('name', 'id')->all(),
    'sort' => ['revenue' => 'Sort: Revenue', 'qty' => 'Sort: Qty sold', 'profit' => 'Sort: Profit', 'margin' => 'Sort: Margin %'],
]])

@php
    $rev = $rows->sum('revenue'); $cost = $rows->sum('cost'); $profit = $rev - $cost;
@endphp
<div class="row g-3 mb-3">
    @foreach ([['Products sold', $rows->count()], ['Units sold', number_format($rows->sum('qty'))], ['Revenue (item price)', '৳ '.number_format($rev, 2)], ['Cost (COGS)', '৳ '.number_format($cost, 2)], ['Gross profit', '৳ '.number_format($profit, 2)], ['Margin', ($rev > 0 ? round($profit / $rev * 100, 1) : 0).'%']] as [$l, $v])
        <div class="col-6 col-lg-2"><div class="stat-card"><div><div class="v">{{ $v }}</div><div class="l">{{ $l }}</div></div></div></div>
    @endforeach
</div>

<div class="panel">
    <div class="panel-head">Top selling products · {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
        <span class="small text-muted fw-normal">কস্ট = বিক্রির সময়ের ক্রয়মূল্য (স্টক লেজার থেকে)</span></div>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0 small align-middle">
        <thead class="table-light"><tr><th>#</th><th>Product</th><th>Category</th><th class="text-end">Qty</th><th class="text-end">Orders</th><th class="text-end">Revenue</th><th class="text-end">Cost</th><th class="text-end">Profit</th><th class="text-end">Margin</th></tr></thead>
        @forelse ($rows as $r)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $r->name }} <span class="text-muted">{{ $r->sku }}</span></td>
                <td>{{ $r->category ?? '—' }}</td>
                <td class="text-end">{{ (int) $r->qty }}</td>
                <td class="text-end">{{ (int) $r->orders }}</td>
                <td class="text-end">{{ number_format($r->revenue, 2) }}</td>
                <td class="text-end">{{ number_format($r->cost, 2) }}</td>
                <td class="text-end fw-semibold {{ $r->profit < 0 ? 'text-danger' : 'text-success' }}">{{ number_format($r->profit, 2) }}</td>
                <td class="text-end">{{ $r->margin }}%</td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-muted py-5">এই সময়ে কোনো বিক্রি নেই।</td></tr>
        @endforelse
    </table></div>
</div>
@endsection
