@extends('layouts.manager')
@section('title', 'Reviews')

@section('content')
<div class="d-flex gap-2 mb-3 flex-wrap">
    @foreach (['' => 'All', 'pending' => 'Pending approval', 'reported' => 'Reported', 'unreplied' => 'No reply'] as $k => $v)
        <a href="{{ route('manager.reviews.index', array_filter(['filter' => $k])) }}" class="btn btn-sm {{ request('filter', '') === $k ? 'btn-dark' : 'btn-light' }}">{{ $v }}</a>
    @endforeach
    <span class="small text-muted ms-auto align-self-center">Auto-approve: {{ $tenant->option('review_auto_approve') ? 'On' : 'Off' }} (Settings)</span>
</div>
<div class="panel">
    <ul class="list-group list-group-flush small">
        @forelse ($reviews as $r)
            <li class="list-group-item">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <div>
                        <span class="stars">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5 - $r->rating) }}</span>
                        <strong>{{ $r->name }}</strong> on <a href="{{ $r->product?->url }}" target="_blank">{{ $r->product?->name }}</a>
                        @if ($r->is_verified)<span class="badge bg-success">Verified purchase</span>@endif
                        @unless ($r->is_approved)<span class="badge bg-warning text-dark">Pending</span>@endunless
                        @if ($r->reports)<span class="badge bg-danger">{{ $r->reports }} reports</span>@endif
                        <span class="text-muted">· {{ $r->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="d-flex gap-1">
                        @perm('reviews.approve')
                        <form method="POST" action="{{ route('manager.reviews.update', $r) }}">@csrf @method('PATCH')<input type="hidden" name="is_approved" value="{{ $r->is_approved ? 0 : 1 }}"><button class="btn btn-sm {{ $r->is_approved ? 'btn-outline-secondary' : 'btn-success' }}">{{ $r->is_approved ? 'Hide' : 'Approve' }}</button></form>
                        @if ($r->reports)<form method="POST" action="{{ route('manager.reviews.update', $r) }}">@csrf @method('PATCH')<input type="hidden" name="clear_reports" value="1"><button class="btn btn-sm btn-outline-dark">Clear reports</button></form>@endif
                        @endperm
                        @perm('reviews.delete')<form method="POST" action="{{ route('manager.reviews.destroy', $r) }}" onsubmit="return confirm('Delete review?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>@endperm
                    </div>
                </div>
                <div class="mt-1">{{ $r->comment }}</div>
                @if ($r->image_url)<a href="{{ $r->image_url }}" target="_blank"><img src="{{ $r->image_url }}" class="rounded mt-1" style="height:60px"></a>@endif
                @perm('reviews.approve')
                <form method="POST" action="{{ route('manager.reviews.update', $r) }}" class="d-flex gap-2 mt-2">@csrf @method('PATCH')
                    <input name="reply" value="{{ $r->reply }}" class="form-control form-control-sm" placeholder="Reply publicly as {{ $tenant->display_name }}...">
                    @if ($tenant->hasFeature('ai'))<button type="button" class="btn btn-sm btn-light border text-nowrap ai-reply" data-url="{{ route('manager.ai.review-reply', $r) }}" title="AI reply suggestion">✨</button>@endif
                    <button class="btn btn-sm btn-dark text-nowrap">{{ $r->reply ? 'Update reply' : 'Reply' }}</button>
                </form>
                @endperm
            </li>
        @empty
            <li class="list-group-item text-center text-muted py-5">No reviews.</li>
        @endforelse
    </ul>
    <div class="panel-body">{{ $reviews->links() }}</div>
</div>
@endsection

@include('manager.ai._js')
@push('scripts')
<script>
document.querySelectorAll('.ai-reply').forEach(b => b.addEventListener('click', async () => {
    const d = await mbAi(b.dataset.url, {}, b);
    if (d?.reply) b.closest('form').querySelector('[name=reply]').value = d.reply;
}));
</script>
@endpush
