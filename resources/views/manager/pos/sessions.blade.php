@extends('layouts.manager')
@section('title', 'POS Sessions')

@section('content')
@include('manager.pos._nav')
<div class="panel">
    <div class="table-responsive"><table class="table table-hover mb-0 small align-middle">
        <thead class="table-light"><tr><th>#</th><th>Counter</th><th>Opened</th><th>Closed</th><th>Invoices</th><th class="text-end">Expected</th><th class="text-end">Counted</th><th class="text-end">Diff</th></tr></thead>
        <tbody>
        @forelse ($sessions as $s)
            <tr onclick="location='{{ route('manager.pos.session', $s) }}'" style="cursor:pointer">
                <td>{{ $s->id }}</td><td>{{ $s->warehouse?->name }}</td>
                <td>{{ $s->opened_at->format('d M h:i A') }}<br><span class="text-muted">{{ $s->opened_by }}</span></td>
                <td>@if ($s->closed_at){{ $s->closed_at->format('d M h:i A') }}<br><span class="text-muted">{{ $s->closed_by }}</span>@else<span class="badge bg-success">Open</span>@endif</td>
                <td>{{ $s->orders_count }}</td>
                <td class="text-end">{{ $s->closed_at ? number_format($s->expected_cash, 2) : '—' }}</td>
                <td class="text-end">{{ $s->closed_at ? number_format($s->counted_cash, 2) : '—' }}</td>
                <td class="text-end {{ $s->closed_at && (float) $s->difference != 0 ? 'text-danger' : '' }}">{{ $s->closed_at ? number_format($s->difference, 2) : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-5">No sessions yet.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="panel-body">{{ $sessions->links() }}</div>
</div>
@endsection
