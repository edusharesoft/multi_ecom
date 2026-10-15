@extends('layouts.admin')
@section('title', 'Activity Logs')

@section('content')
<div class="panel">
    <form class="panel-head gap-2 flex-wrap justify-content-start" method="GET">
        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:200px" placeholder="Description / IP">
        <select name="branch" class="form-select form-select-sm" style="width:170px"><option value="">All branches</option>@foreach ($branches as $b)<option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->store_name ?: $b->name }}</option>@endforeach</select>
        <select name="actor" class="form-select form-select-sm" style="width:130px"><option value="">Any user</option>@foreach (['admin', 'owner', 'staff', 'system', 'customer'] as $a)<option @selected(request('actor') === $a)>{{ $a }}</option>@endforeach</select>
        <input name="action" value="{{ request('action') }}" class="form-control form-control-sm" style="width:150px" placeholder="action prefix e.g. invoice">
        <button class="btn btn-sm btn-dark">Filter</button>
    </form>
    <div class="table-responsive">
        <table class="table mb-0 small">
            <thead class="table-light"><tr><th>Time</th><th>Branch</th><th>User</th><th>Action</th><th>Description</th><th>IP / Device</th></tr></thead>
            <tbody>
            @forelse ($logs as $l)
                <tr>
                    <td class="text-nowrap">{{ $l->created_at->format('d M y, h:i A') }}</td>
                    <td>{{ $l->branch?->display_name ?? 'Platform' }}</td>
                    <td>{{ $l->actor_name }} <span class="badge bg-light text-dark">{{ $l->actor_type }}</span></td>
                    <td><code>{{ $l->action }}</code></td>
                    <td>{{ $l->description }}</td>
                    <td class="text-muted" title="{{ $l->user_agent }}">{{ $l->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No logs.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $logs->links() }}</div>
</div>
@endsection
