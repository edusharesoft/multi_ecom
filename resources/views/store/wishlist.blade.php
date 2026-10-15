@extends('layouts.store')
@section('title', 'Wishlist — '.$store->display_name)

@section('content')
<section class="section pt-2">
    <div class="container">
        <h2 class="section-title mb-3">My Wishlist</h2>
        <div class="row g-3">
            @forelse ($products as $product)
                <div class="col-6 col-md-3">
                    @include('partials.product-card')
                    <form method="POST" action="{{ route('store.wishlist.toggle', [$store, $product]) }}" class="mt-1">@csrf
                        <button class="btn btn-sm btn-link text-danger w-100">Remove from wishlist</button>
                    </form>
                </div>
            @empty
                <div class="col-12 text-center text-muted py-5">Wishlist খালি।</div>
            @endforelse
        </div>
    </div>
</section>
@endsection
