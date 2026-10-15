@extends('layouts.admin')
@section('title', 'SMS & Email Log')

@section('content')
<div class="row g-4">
    <div class="col-xl-4">
        <div class="panel">
            <div class="panel-head">SMS usage this month</div>
            <table class="table table-sm mb-0 small">
                <thead class="table-light"><tr><th>Branch</th><th class="text-end">Messages</th><th class="text-end">SMS parts</th></tr></thead>
                @forelse ($usage as $u)
                    <tr><td>{{ $u->branch?->name ?? 'Platform (OTP / admin)' }}</td><td class="text-end">{{ $u->msgs }}</td><td class="text-end fw-semibold">{{ $u->parts }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-muted text-center py-3">No SMS yet.</td></tr>
                @endforelse
                @if ($usage->isNotEmpty())<tfoot><tr class="fw-bold"><td>Total</td><td class="text-end">{{ $usage->sum('msgs') }}</td><td class="text-end">{{ $usage->sum('parts') }}</td></tr></tfoot>@endif
            </table>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="panel">
            <form class="panel-head gap-2 justify-content-start flex-wrap" method="GET">
                <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:200px" placeholder="Number / email / text">
                <select name="channel" class="form-select form-select-sm" style="width:110px"><option value="">All</option><option value="sms" @selected(request('channel') === 'sms')>SMS</option><option value="email" @selected(request('channel') === 'email')>Email</option></select>
                <select name="status" class="form-select form-select-sm" style="width:120px"><option value="">Any status</option>@foreach (['sent', 'failed', 'skipped'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
                <button class="btn btn-sm btn-dark">Filter</button>
            </form>
            <div class="table-responsive"><table class="table table-sm mb-0 small">
                <thead class="table-light"><tr><th>Time</th><th>Branch</th><th>Ch.</th><th>To</th><th>Message</th><th>Status</th></tr></thead>
                @forelse ($logs as $l)
                    <tr><td class="text-nowrap">{{ $l->created_at?->format('d M h:i A') }}</td><td>{{ $l->branch?->name ?? '—' }}</td><td>{{ strtoupper($l->channel) }}</td><td>{{ $l->to }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($l->body, 90) }} <code>{{ $l->context }}</code></td>
                        <td><span class="badge bg-{{ ['sent' => 'success', 'failed' => 'danger', 'skipped' => 'secondary'][$l->status] ?? 'light' }}">{{ $l->status }}</span> <span class="text-muted">{{ $l->error }}</span></td></tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No messages.</td></tr>
                @endforelse
            </table></div>
            <div class="panel-body">{{ $logs->links() }}</div>
        </div>
    </div>
</div>
@endsection
