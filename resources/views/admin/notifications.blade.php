@extends('layouts.admin')
@section('title', 'Notifications')

@section('content')
<div class="panel" style="max-width:820px">
    <ul class="list-group list-group-flush small">
        @forelse ($items as $n)
            <li class="list-group-item">
                <div class="d-flex justify-content-between"><span><span class="badge bg-{{ $n->level }} me-1">&nbsp;</span><strong>{{ $n->title }}</strong></span><span class="text-muted">{{ $n->created_at->diffForHumans() }}</span></div>
                @if ($n->body)<div class="text-muted">{{ $n->body }}</div>@endif
                @if ($n->url)<a href="{{ $n->url }}">Open →</a>@endif
            </li>
        @empty
            <li class="list-group-item text-muted text-center py-4">No notifications.</li>
        @endforelse
    </ul>
    <div class="panel-body">{{ $items->links() }}</div>
</div>
@endsection
