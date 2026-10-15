@extends('layouts.manager')
@section('title', __('Attendance'))

@section('content')
@include('manager.hrm._nav')
<form class="d-flex gap-2 mb-3 small align-items-center" method="GET">
    <input type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}" class="form-control form-control-sm" style="width:170px" onchange="this.form.submit()">
    <a href="{{ route('manager.hrm.attendance', ['date' => \Illuminate\Support\Carbon::parse($date)->subDay()->toDateString()]) }}" class="btn btn-sm btn-light border">‹ Prev</a>
    @if ($date < now()->toDateString())<a href="{{ route('manager.hrm.attendance', ['date' => \Illuminate\Support\Carbon::parse($date)->addDay()->toDateString()]) }}" class="btn btn-sm btn-light border">Next ›</a>@endif
    <span class="text-muted ms-2">{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d F Y') }}</span>
</form>
<form method="POST" action="{{ route('manager.hrm.attendance.save') }}" class="panel">@csrf
    <input type="hidden" name="date" value="{{ $date }}">
    <div class="panel-head">Daily attendance
        @perm('hrm.edit')<div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-outline-success" onclick="document.querySelectorAll('.st').forEach(s => { if (!s.value) s.value = 'present'; })">Mark all present</button><button class="btn btn-sm btn-brand">{{ __('Save') }}</button></div>@endperm</div>
    <div class="table-responsive"><table class="table table-sm mb-0 small align-middle">
        <thead class="table-light"><tr><th>Employee</th><th style="width:150px">Status</th><th style="width:110px">In</th><th style="width:110px">Out</th><th style="width:90px">OT hrs</th><th>Note</th></tr></thead>
        @forelse ($employees as $e)
            @php $r = $records[$e->id] ?? null; @endphp
            <tr>
                <td><strong>{{ $e->name }}</strong> <span class="text-muted">{{ $e->designation }}</span> @if(isset($onLeave[$e->id]))<span class="badge bg-primary">approved leave</span>@endif</td>
                <td><select name="rows[{{ $e->id }}][status]" class="form-select form-select-sm st"><option value="">— not marked —</option>@foreach (\App\Models\Attendance::STATUSES as $k => [$l])<option value="{{ $k }}" @selected(($r?->status ?? (isset($onLeave[$e->id]) ? 'leave' : '')) === $k)>{{ __($l) }}</option>@endforeach</select></td>
                <td><input type="time" name="rows[{{ $e->id }}][check_in]" value="{{ $r?->check_in ? substr($r->check_in, 0, 5) : '' }}" class="form-control form-control-sm"></td>
                <td><input type="time" name="rows[{{ $e->id }}][check_out]" value="{{ $r?->check_out ? substr($r->check_out, 0, 5) : '' }}" class="form-control form-control-sm"></td>
                <td><input type="number" step="0.5" min="0" max="24" name="rows[{{ $e->id }}][overtime_hours]" value="{{ $r ? (float) $r->overtime_hours : '' }}" class="form-control form-control-sm"></td>
                <td><input name="rows[{{ $e->id }}][note]" value="{{ $r?->note }}" class="form-control form-control-sm"></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-5">আগে <a href="{{ route('manager.hrm.employees') }}">কর্মচারী যোগ করুন</a>।</td></tr>
        @endforelse
    </table></div>
    <div class="panel-body small text-muted">মাসিক বেতনে যে দিন হাজিরা দেওয়া নেই সেটি উপস্থিত ধরা হয়; শুধু Absent / Half day / Unpaid leave কাটা যায়।</div>
</form>
@endsection
