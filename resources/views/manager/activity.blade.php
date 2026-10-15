@extends('layouts.manager')
@section('title', 'Activity Log')

@section('content')
<div class="panel">
    <form class="panel-head gap-2 flex-wrap justify-content-start" method="GET">
        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:240px" placeholder="Search description / user">
        <select name="action" class="form-select form-select-sm" style="width:170px">
            <option value="">All actions</option>
            @foreach (['auth' => 'Login', 'product' => 'Products', 'order' => 'Orders', 'staff' => 'Staff', 'role' => 'Roles', 'settings' => 'Settings', 'subscription' => 'Subscription', 'invoice' => 'Invoices'] as $k => $v)
                <option value="{{ $k }}" @selected(request('action') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-dark">Filter</button>
    </form>
    <div class="table-responsive">
        <table class="table mb-0 small">
            <thead class="table-light"><tr><th>Time</th><th>User</th><th>Action</th><th>Description</th><th>IP</th></tr></thead>
            <tbody>
            @forelse ($logs as $l)
                <tr>
                    <td class="text-nowrap">{{ $l->created_at->format('d M Y, h:i A') }}</td>
                    <td>{{ $l->actor_name }} <span class="badge bg-light text-dark">{{ $l->actor_type }}</span></td>
                    <td><code>{{ $l->action }}</code></td>
                    <td>{{ $l->description }}</td>
                    <td class="text-muted">{{ $l->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No activity yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $logs->links() }}</div>
</div>
@endsection
