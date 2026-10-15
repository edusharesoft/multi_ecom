<div class="d-flex justify-content-center gap-2 gap-md-4 mb-4 flex-wrap small">
    @foreach ([1 => 'Select Subscription', 2 => 'Payment', 3 => 'Create Store'] as $n => $label)
        <div class="d-flex align-items-center gap-2 {{ $step >= $n ? 'fw-semibold' : 'text-muted' }}">
            <span class="rounded-circle d-inline-flex align-items-center justify-content-center {{ $step > $n ? 'bg-success text-white' : ($step === $n ? 'bg-brand text-white' : 'bg-light border') }}" style="width:28px;height:28px">{!! $step > $n ? '<i class="fa-solid fa-check"></i>' : $n !!}</span>
            {{ $label }}
        </div>
        @if ($n < 3)<span class="text-muted d-none d-md-inline">——</span>@endif
    @endforeach
</div>
