@extends('layouts.store')
@section('title', ($post->meta_title ?: $post->title).' — '.$store->display_name)
@section('meta_description', $post->meta_description ?: $post->summary(155))
@if ($post->cover_url)@section('og_image', $post->cover_url)@endif

@push('head')
<meta property="og:type" content="article">
<script type="application/ld+json">{!! json_encode([
    '@@context' => 'https://schema.org', '@type' => 'BlogPosting', 'headline' => $post->title, 'description' => $post->summary(155),
    'image' => $post->cover_url ? [$post->cover_url] : [], 'datePublished' => $post->published_at?->toAtomString(), 'dateModified' => $post->updated_at?->toAtomString(),
    'author' => ['@type' => 'Person', 'name' => $post->author_name ?: $store->display_name],
    'publisher' => ['@type' => 'Organization', 'name' => $store->display_name, 'logo' => $store->logo_url ? ['@type' => 'ImageObject', 'url' => $store->logo_url] : null],
    'mainEntityOfPage' => $post->url(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<section class="section pt-2">
    <div class="container" style="max-width:860px">
        <nav class="small mb-2"><a href="{{ route('store.home', $store) }}">Home</a> / <a href="{{ route('store.blog', $store) }}">Blog</a>@if($post->category) / <a href="{{ route('store.blog', [$store, 'category' => $post->category->slug]) }}">{{ $post->category->name }}</a>@endif</nav>
        @unless ($post->isLive())<div class="alert alert-warning small">Preview — এই পোস্ট এখনো প্রকাশিত হয়নি।</div>@endunless
        <article class="bg-white rounded p-4">
            <h1 class="h3 fw-bold">{{ $post->title }}</h1>
            <div class="small text-muted mb-3">{{ $post->author_name ?: $store->display_name }} · {{ $post->published_at?->format('d M Y') }} · {{ $post->readMinutes() }} min read · {{ number_format($post->views) }} views</div>
            @if ($post->cover_url)<img src="{{ $post->cover_url }}" alt="{{ $post->title }}" class="w-100 rounded mb-3" style="max-height:440px;object-fit:cover">@endif
            <div class="page-content">{!! $post->content !!}</div>
            @if ($post->tagList())
                <div class="mt-3 small">@foreach ($post->tagList() as $t)<a href="{{ route('store.blog', [$store, 'tag' => $t]) }}" class="badge bg-light text-dark border me-1">#{{ $t }}</a>@endforeach</div>
            @endif
            <div class="mt-3 small d-flex gap-2">
                <span class="text-muted">Share:</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($post->url()) }}" target="_blank" rel="noopener"><i class="fa-brands fa-facebook"></i></a>
                <a href="https://wa.me/?text={{ urlencode($post->title.' '.$post->url()) }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i></a>
            </div>
        </article>
        @if ($related->isNotEmpty())
            <h2 class="section-title h5 mt-4 mb-3">Related posts</h2>
            <div class="row g-3">@foreach ($related as $post)<div class="col-md-4">@include('store.blog._card')</div>@endforeach</div>
        @endif
    </div>
</section>
@endsection
