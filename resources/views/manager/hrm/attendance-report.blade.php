@extends('layouts.manager')
@section('title', __('Monthly attendance'))

@section('content')
@include('manager.hrm._nav')
<form class="d-flex gap-2 mb-3 small d-print-none" method="GET">
    <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm" style="width:170px">
    <button class="btn btn-sm btn-dark">Show</button>
    <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-print"></i> Print</button>
</form>
<div class="panel">
    <div class="panel-head">{{ $start->format('F Y') }} <span class="small fw-normal">@foreach (\App\Models\Attendance::STATUSES as [$l, $c, $s])<span class="badge bg-{{ $c }} me-1">{{ $s }} = {{ __($l) }}</span>@endforeach</span></div>
    <div class="table-responsive"><table class="table table-sm table-bordered mb-0 text-center" style="font-size:11px">
        <thead class="table-light"><tr><th class="text-start">Employee</th>@for ($d = 1; $d <= $days; $d++)<th class="{{ $start->copy()->day($d)->isFriday() ? 'bg-warning-subtle' : '' }}">{{ $d }}</th>@endfor<th>P</th><th>A</th><th>L</th></tr></thead>
        @foreach ($employees as $e)
            @php $row = $rows[$e->id] ?? collect(); @endphp
            <tr>
                <td class="text-start text-nowrap">{{ $e->name }}</td>
                @for ($d = 1; $d <= $days; $d++)
                    @php $a = $row[$d] ?? null; @endphp
                    <td>@if($a)<span class="text-{{ \App\Models\Attendance::STATUSES[$a->status][1] }} fw-bold">{{ \App\Models\Attendance::STATUSES[$a->status][2] }}</span>@endif</td>
                @endfor
                <td class="fw-bold">{{ $row->whereIn('status', ['present', 'late'])->count() + $row->where('status', 'half_day')->count() * 0.5 }}</td>
                <td class="fw-bold text-danger">{{ $row->where('status', 'absent')->count() }}</td>
                <td class="fw-bold text-primary">{{ $row->where('status', 'leave')->count() }}</td>
            </tr>
        @endforeach
    </table></div>
</div>
@endsection
