@extends('layouts.admin')
@section('title', 'Subscriptions')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-sm {{ request('status') ? 'btn-light' : 'btn-dark' }}">All</a>
    @foreach (\App\Models\Subscription::STATUSES as $st)
        <a href="{{ route('admin.subscriptions.index', ['status' => $st]) }}" class="btn btn-sm {{ request('status') === $st ? 'btn-dark' : 'btn-light' }}">{{ ucfirst($st) }} <span class="badge bg-secondary">{{ $counts[$st] ?? 0 }}</span></a>
    @endforeach
    <a href="{{ route('admin.subscriptions.index', ['expiring' => 7]) }}" class="btn btn-sm btn-outline-warning">Expiring ≤ 7 days</a>
    <form method="POST" action="{{ route('admin.subscriptions.run') }}" class="ms-auto">@csrf<button class="btn btn-sm btn-outline-primary" title="Reminders, renewal invoices, grace & expiry"><i class="fa-solid fa-play"></i> Run automation now</button></form>
</div>
<div class="panel">
    <form class="panel-head gap-2 justify-content-start" method="GET">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:220px" placeholder="Branch name / USER">
        <select name="plan" class="form-select form-select-sm" style="width:160px"><option value="">All plans</option>@foreach ($plans as $p)<option value="{{ $p->id }}" @selected(request('plan') == $p->id)>{{ $p->name }}</option>@endforeach</select>
        <button class="btn btn-sm btn-dark">Filter</button>
    </form>
    <div class="table-responsive">
        <table class="table mb-0 small align-middle">
            <thead class="table-light"><tr><th>Branch</th><th>Plan</th><th>Status</th><th>Start</th><th>Expiry</th><th>Auto-renew</th><th class="text-end">Manage</th></tr></thead>
            <tbody>
            @forelse ($subs as $s)
                <tr>
                    <td><a href="{{ route('admin.branches.show', $s->branch) }}" class="fw-semibold">{{ $s->branch->display_name }}</a><br><span class="text-muted">/{{ $s->branch->slug }}</span></td>
                    <td>{{ $s->plan->name }} <span class="text-muted">· {{ $s->billing_cycle }}</span>@if($s->amount > 0)<br>৳ {{ number_format($s->amount) }}@endif
                        @if($s->nextPlan)<br><span class="text-warning">→ {{ $s->nextPlan->name }}</span>@endif</td>
                    <td><span class="badge bg-{{ $s->status_color }}">{{ $s->status_label }}</span>@if($s->cancelled_at)<br><span class="text-danger">cancelled</span>@endif</td>
                    <td>{{ $s->starts_at?->format('d M Y') }}</td>
                    <td>{{ $s->ends_at?->format('d M Y') ?? '∞' }}@if($s->status === 'grace')<br><span class="text-danger">grace → {{ $s->grace_ends_at?->format('d M') }}</span>@endif</td>
                    <td>{{ $s->auto_renew ? 'Yes' : 'No' }}</td>
                    <td class="text-end"><button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#sub-{{ $s->id }}"><i class="fa-solid fa-sliders"></i></button></td>
                </tr>
                <tr class="collapse" id="sub-{{ $s->id }}"><td colspan="7" class="bg-light">
                    <div class="row g-3">
                        <form method="POST" action="{{ route('admin.subscriptions.update', $s) }}" class="col-lg-9 row g-2">@csrf @method('PUT')
                            <div class="col-md-3"><select name="plan_id" class="form-select form-select-sm">@foreach ($plans as $p)<option value="{{ $p->id }}" @selected($s->plan_id === $p->id)>{{ $p->name }}</option>@endforeach</select></div>
                            <div class="col-md-2"><select name="billing_cycle" class="form-select form-select-sm">@foreach (['monthly', 'yearly', 'lifetime', 'trial'] as $c)<option @selected($s->billing_cycle === $c)>{{ $c }}</option>@endforeach</select></div>
                            <div class="col-md-2"><select name="status" class="form-select form-select-sm">@foreach (\App\Models\Subscription::STATUSES as $st)<option @selected($s->status === $st)>{{ $st }}</option>@endforeach</select></div>
                            <div class="col-md-3"><input type="date" name="ends_at" value="{{ $s->ends_at?->toDateString() }}" class="form-control form-control-sm" title="Expiry (empty = never)"></div>
                            <div class="col-md-2 d-flex align-items-center gap-2"><input type="hidden" name="auto_renew" value="0"><input class="form-check-input" type="checkbox" name="auto_renew" value="1" @checked($s->auto_renew)> <small>renew</small> <button class="btn btn-sm btn-dark ms-auto">Save</button></div>
                        </form>
                        <form method="POST" action="{{ route('admin.subscriptions.extend', $s) }}" class="col-lg-3 d-flex gap-2">@csrf
                            <input type="number" name="days" min="1" value="30" class="form-control form-control-sm"><button class="btn btn-sm btn-outline-success text-nowrap">+ days</button>
                        </form>
                    </div>
                </td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-5">No subscriptions.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel-body">{{ $subs->links() }}</div>
</div>
@endsection
