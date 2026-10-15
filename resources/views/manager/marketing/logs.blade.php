@extends('layouts.manager')
@section('title', 'Message Log')

@section('content')
@include('manager.marketing._nav')
@include('manager.marketing._quota')
<div class="panel">
    <form class="panel-head gap-2 justify-content-start flex-wrap" method="GET">
        <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:200px" placeholder="Number / email / text">
        <select name="channel" class="form-select form-select-sm" style="width:110px"><option value="">All</option><option value="sms" @selected(request('channel') === 'sms')>SMS</option><option value="email" @selected(request('channel') === 'email')>Email</option></select>
        <select name="status" class="form-select form-select-sm" style="width:120px"><option value="">Any status</option>@foreach (['sent', 'failed', 'skipped'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
        <button class="btn btn-sm btn-dark">Filter</button>
    </form>
    <div class="table-responsive"><table class="table table-sm table-hover mb-0 small">
        <thead class="table-light"><tr><th>Time</th><th>Channel</th><th>To</th><th>Message</th><th>Event</th><th>Status</th></tr></thead>
        @forelse ($logs as $l)
            <tr><td class="text-nowrap">{{ $l->created_at?->format('d M h:i A') }}</td><td>{{ strtoupper($l->channel) }}@if($l->channel === 'sms' && $l->segments > 1) ×{{ $l->segments }}@endif</td><td>{{ $l->to }}</td>
                <td>@if($l->subject)<strong>{{ $l->subject }}</strong><br>@endif{{ \Illuminate\Support\Str::limit($l->body, 120) }}</td>
                <td><code>{{ $l->context }}</code></td>
                <td><span class="badge bg-{{ ['sent' => 'success', 'failed' => 'danger', 'skipped' => 'secondary'][$l->status] ?? 'light' }}">{{ $l->status }}</span>@if($l->error)<br><span class="text-muted">{{ $l->error }}</span>@endif</td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">No messages yet.</td></tr>
        @endforelse
    </table></div>
    <div class="panel-body">{{ $logs->links() }}</div>
</div>
@endsection
