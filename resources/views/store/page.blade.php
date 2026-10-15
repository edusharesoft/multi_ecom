@extends('layouts.store')
@section('title', ($page->meta_title ?: $page->title).' — '.$store->display_name)
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags((string) $page->content)), 155))

@section('content')
<section class="section pt-2">
    <div class="container" style="max-width:900px">
        <nav class="small mb-2"><a href="{{ route('store.home', $store) }}">Home</a> / {{ $page->title }}</nav>
        <div class="bg-white rounded p-4">
            <h1 class="h3 fw-bold mb-3">{{ $page->title }}</h1>
            <div class="page-content">{!! $page->content !!}</div>
        </div>
    </div>
</section>
@endsection
