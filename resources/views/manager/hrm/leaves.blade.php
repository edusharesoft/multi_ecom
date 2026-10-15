@extends('layouts.manager')
@section('title', __('Leave'))

@section('content')
@include('manager.hrm._nav')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel">
            <div class="table-responsive"><table class="table table-sm mb-0 small align-middle">
                <thead class="table-light"><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Reason</th><th>Status</th><th></th></tr></thead>
                @forelse ($leaves as $l)
                    <tr>
                        <td>{{ $l->employee?->name }}</td><td>{{ \App\Models\LeaveRequest::TYPES[$l->type] ?? $l->type }}</td>
                        <td>{{ $l->from_date->format('d M') }} – {{ $l->to_date->format('d M Y') }}</td><td>{{ $l->days }}</td><td>{{ $l->reason }}</td>
                        <td><span class="badge bg-{{ ['pending' => 'warning text-dark', 'approved' => 'success', 'rejected' => 'secondary'][$l->status] }}">{{ ucfirst($l->status) }}</span>@if($l->decided_by)<br><span class="text-muted">{{ $l->decided_by }}</span>@endif</td>
                        <td class="text-nowrap">@if ($l->status === 'pending')@perm('hrm.approve')
                            <form method="POST" action="{{ route('manager.hrm.leaves.decide', $l) }}" class="d-inline">@csrf @method('PATCH')<button name="status" value="approved" class="btn btn-sm btn-success">Approve</button> <button name="status" value="rejected" class="btn btn-sm btn-outline-danger">Reject</button></form>
                        @endperm @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No leave requests.</td></tr>
                @endforelse
            </table></div>
            <div class="panel-body">{{ $leaves->links() }}</div>
        </div>
    </div>
    @perm('hrm.add')
    <div class="col-xl-4">
        <form method="POST" action="{{ route('manager.hrm.leaves.store') }}" class="panel">@csrf
            <div class="panel-head">New leave request</div>
            <div class="panel-body small">
                <select name="employee_id" class="form-select form-select-sm mb-2" required>@foreach ($employees as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select>
                <select name="type" class="form-select form-select-sm mb-2">@foreach (\App\Models\LeaveRequest::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
                <div class="d-flex gap-2 mb-2"><input type="date" name="from_date" class="form-control form-control-sm" required><input type="date" name="to_date" class="form-control form-control-sm" required></div>
                <input name="reason" class="form-control form-control-sm mb-2" placeholder="Reason">
                <button class="btn btn-sm btn-brand w-100">Submit</button>
                <p class="text-muted mt-2 mb-0">Approve করলে ঐ দিনগুলো হাজিরায় “Leave” হবে। শুধু <strong>Unpaid</strong> ছুটির জন্য বেতন কাটা হয়।</p>
            </div>
        </form>
    </div>
    @endperm
</div>
@endsection
