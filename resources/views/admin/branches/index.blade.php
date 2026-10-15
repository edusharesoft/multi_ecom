@extends('layouts.admin')
@section('title', 'Branches')

@section('content')
<div class="panel">
    <div class="panel-head flex-wrap gap-2">
        <form class="d-flex gap-2 flex-wrap" method="GET">
            <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Name / USER / email / mobile" style="width:240px">
            <select name="state" class="form-select form-select-sm" style="width:150px">
                <option value="">All</option>
                @foreach (['live' => 'Live', 'unverified' => 'OTP pending', 'hidden' => 'Hidden by manager', 'suspended' => 'Suspended'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('state') === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-dark">Filter</button>
        </form>
        <span class="small text-muted">{{ $branches->total() }} branches</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 small">
            <thead class="table-light"><tr><th>Branch</th><th>URL</th><th>Manager</th><th>Plan</th><th>Products</th><th>Orders</th><th>Sales</th><th>State</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($branches as $b)
                <tr>
                    <td><a href="{{ route('admin.branches.show', $b) }}" class="fw-semibold">{{ $b->display_name }}</a><br><span class="text-muted">{{ $b->created_at->format('d M Y') }}</span></td>
                    <td><a href="{{ $b->url }}" target="_blank">/{{ $b->slug }}</a></td>
                    <td>{{ $b->name }}<br><span class="text-muted">{{ $b->mobile }} · {{ $b->email }}</span></td>
                    <td>{{ $b->subscription?->plan?->name ?? '—' }}@if($b->subscription)<br><span class="badge bg-{{ $b->subscription->status_color }}">{{ $b->subscription->status_label }}</span>@endif</td>
                    <td>{{ $b->products_count }}</td>
                    <td>{{ $b->orders_count }}</td>
                    <td>@money($b->sales ?? 0)</td>
                    <td>@include('admin.branches._state')</td>
                    <td class="text-end">@include('admin.branches._actions')</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-5">No branches found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $branches->links() }}</div>
</div>
@endsection
