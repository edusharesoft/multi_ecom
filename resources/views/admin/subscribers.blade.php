@extends('layouts.admin')
@section('title', 'Subscribers')

@section('content')
<div class="panel" style="max-width:640px">
    <div class="panel-head">Newsletter Subscribers <span class="small text-muted">{{ $subscribers->total() }}</span></div>
    <table class="table mb-0 small">
        <tbody>
        @forelse ($subscribers as $s)
            <tr><td>{{ $s->email }}</td><td class="text-muted text-end">{{ $s->created_at->format('d M Y') }}</td></tr>
        @empty
            <tr><td class="text-center text-muted py-4">No subscribers yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="panel-body">{{ $subscribers->links() }}</div>
</div>
@endsection
