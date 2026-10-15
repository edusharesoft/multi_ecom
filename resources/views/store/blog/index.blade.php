@extends('layouts.store')
@section('title', ($category ? $category->name.' — ' : '').'Blog — '.$store->display_name)
@section('meta_description', 'Tips, news and guides from '.$store->display_name)

@section('content')
<section class="section pt-2">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
            <h1 class="section-title h3">{{ $category ? $category->name : 'Blog' }}</h1>
            <form class="d-flex gap-2" method="GET"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search posts"><button class="btn btn-sm btn-brand">Search</button></form>
        </div>
        @if ($categories->where('posts_count', '>', 0)->isNotEmpty())
            <div class="d-flex flex-wrap gap-2 mb-3 small">
                <a href="{{ route('store.blog', $store) }}" class="badge {{ $category ? 'bg-light text-dark border' : 'bg-brand' }} text-decoration-none">All</a>
                @foreach ($categories->where('posts_count', '>', 0) as $c)
                    <a href="{{ route('store.blog', [$store, 'category' => $c->slug]) }}" class="badge {{ $category?->id === $c->id ? 'bg-brand' : 'bg-light text-dark border' }} text-decoration-none">{{ $c->name }} ({{ $c->posts_count }})</a>
                @endforeach
            </div>
        @endif
        <div class="row g-3">
            @forelse ($posts as $post)
                <div class="col-md-6 col-lg-4">@include('store.blog._card')</div>
            @empty
                <p class="text-muted">কোনো পোস্ট পাওয়া যায়নি।</p>
            @endforelse
        </div>
        <div class="mt-3">{{ $posts->links() }}</div>
    </div>
</section>
@endsection
