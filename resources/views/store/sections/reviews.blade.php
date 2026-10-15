@if ($reviews->isNotEmpty())
<section class="section pt-0">
    <div class="container">
        <h2 class="section-title mb-3">{{ $sec['title'] ?: __('Customer Reviews') }}</h2>
        <div class="row g-3">
            @foreach ($reviews->take($sec['limit'] ?: 3) as $r)
                <div class="col-md-4">
                    <div class="review-card">
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <div class="avatar">{{ mb_substr($r->name, 0, 1) }}</div>
                            <div><div class="fw-semibold">{{ $r->name }}</div>@if($r->is_verified)<small class="text-success"><i class="fa-solid fa-circle-check"></i> Verified Purchase</small>@endif</div>
                        </div>
                        <p>"{{ \Illuminate\Support\Str::limit($r->comment, 160) }}"</p>
                        <div class="stars text-end">@for ($i = 1; $i <= 5; $i++)<i class="fa-{{ $i <= $r->rating ? 'solid' : 'regular' }} fa-star"></i>@endfor</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif
