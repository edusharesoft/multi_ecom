<a href="{{ $post->url() }}" class="d-block bg-white rounded overflow-hidden h-100 text-dark shadow-sm">
    <div style="aspect-ratio:16/9;background:#eee url('{{ $post->cover_url ?: $store->banner_url }}') center/cover"></div>
    <div class="p-3">
        <div class="small text-muted mb-1">@if($post->category){{ $post->category->name }} · @endif{{ $post->published_at?->format('d M Y') }} · {{ $post->readMinutes() }} min read</div>
        <h3 class="h6 fw-bold mb-1">{{ $post->title }}</h3>
        <p class="small text-muted mb-0">{{ $post->summary(120) }}</p>
    </div>
</a>
