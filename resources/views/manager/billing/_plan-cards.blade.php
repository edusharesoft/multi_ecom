{{-- Plan cards with Monthly/Yearly toggle. expects $plans, $action (route url), optional $sub, $branch --}}
@php
    $currentId = isset($sub) && $sub?->isUsable() ? $sub->plan_id : null;
    $canTrial = isset($branch) && ! $branch->trial_used_at;
@endphp
<div class="text-center mb-4">
    <div class="btn-group" role="group" id="cycleToggle">
        <input type="radio" class="btn-check" name="cycleView" id="cyM" value="monthly" checked>
        <label class="btn btn-outline-dark btn-sm px-4" for="cyM">Monthly</label>
        <input type="radio" class="btn-check" name="cycleView" id="cyY" value="yearly">
        <label class="btn btn-outline-dark btn-sm px-4" for="cyY">Yearly <span class="badge bg-success">Save</span></label>
        <input type="radio" class="btn-check" name="cycleView" id="cyL" value="lifetime">
        <label class="btn btn-outline-dark btn-sm px-4" for="cyL">Lifetime</label>
    </div>
</div>
<div class="row g-3 justify-content-center">
    @foreach ($plans as $p)
        <div class="col-md-6 col-xl-3">
            <div class="panel h-100 d-flex flex-column position-relative {{ $p->is_featured ? 'border border-2 border-danger' : '' }}">
                @if ($p->is_featured)<span class="badge bg-brand position-absolute top-0 start-50 translate-middle">Most Popular</span>@endif
                @if ($p->branch_id)<span class="badge bg-dark position-absolute top-0 end-0 m-2">Custom</span>@endif
                <div class="panel-body text-center pb-2">
                    <h5 class="fw-bold mb-0">{{ $p->name }}</h5>
                    <div class="small text-muted mb-2">{{ $p->description }}</div>
                    <div class="price-view" data-cycle="monthly"><span class="display-6 fw-bold">৳{{ number_format($p->price_monthly) }}</span><span class="text-muted">/মাস</span></div>
                    <div class="price-view d-none" data-cycle="yearly"><span class="display-6 fw-bold">৳{{ number_format($p->price_yearly) }}</span><span class="text-muted">/বছর</span>
                        @if ($p->yearlySavingPercent())<div class="small text-success">{{ $p->yearlySavingPercent() }}% সাশ্রয়</div>@endif</div>
                    <div class="price-view d-none" data-cycle="lifetime">
                        @if ($p->is_free)<span class="display-6 fw-bold">৳0</span>
                        @elseif ($p->price_lifetime !== null)<span class="display-6 fw-bold">৳{{ number_format($p->price_lifetime) }}</span><span class="text-muted"> একবার</span>
                        @else<span class="text-muted small d-block py-3">Lifetime নেই</span>@endif
                    </div>
                    @if ($p->trial_days && $canTrial)<div class="badge bg-info-subtle text-info-emphasis mt-1">{{ $p->trial_days }} দিন ফ্রি ট্রায়াল</div>@endif
                </div>
                <ul class="list-unstyled small px-3 mb-3 flex-fill">
                    @foreach (\App\Models\Plan::LIMITS as $key => $label)
                        <li class="py-1 border-bottom"><i class="fa-solid fa-check text-success me-1"></i>{{ \App\Models\Plan::limitLabel($p->limit($key), $key) }} {{ $label }}</li>
                    @endforeach
                    @foreach (\App\Models\Plan::FEATURES as $key => $label)
                        <li class="py-1 {{ $p->hasFeature($key) ? '' : 'text-muted text-decoration-line-through opacity-50' }}"><i class="fa-solid {{ $p->hasFeature($key) ? 'fa-check text-success' : 'fa-lock' }} me-1"></i>{{ $label }}</li>
                    @endforeach
                    @if ($p->commission_percent > 0)<li class="py-1 text-muted"><i class="fa-solid fa-percent me-1"></i>{{ rtrim(rtrim($p->commission_percent, '0'), '.') }}% প্ল্যাটফর্ম কমিশন</li>@endif
                </ul>
                <div class="px-3 pb-3">
                    @if ($currentId === $p->id)
                        <button class="btn btn-outline-secondary w-100" disabled>বর্তমান প্ল্যান</button>
                    @else
                        <form method="POST" action="{{ $action }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $p->id }}">
                            <input type="hidden" name="cycle" value="monthly" class="cycle-input">
                            @if ($p->is_free)
                                <button class="btn btn-outline-dark w-100">Free শুরু করুন</button>
                            @else
                                @if ($p->trial_days && $canTrial)
                                    <button name="trial" value="1" class="btn btn-brand w-100 mb-2 trial-btn">{{ $p->trial_days }} দিন ফ্রি ট্রায়াল</button>
                                @endif
                                <button class="btn {{ $p->trial_days && $canTrial ? 'btn-outline-brand' : 'btn-brand' }} w-100 pay-btn">এখনই কিনুন</button>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
@push('scripts')
<script>
document.querySelectorAll('#cycleToggle input').forEach(r => r.addEventListener('change', () => {
    const c = r.value;
    document.querySelectorAll('.price-view').forEach(v => v.classList.toggle('d-none', v.dataset.cycle !== c));
    document.querySelectorAll('.cycle-input').forEach(i => i.value = c);
    document.querySelectorAll('.trial-btn').forEach(b => b.classList.toggle('d-none', c === 'lifetime'));
}));
</script>
@endpush
