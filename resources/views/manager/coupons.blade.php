@extends('layouts.manager')
@section('title', 'Coupons & Promotions')

@section('content')
@perm('coupons.add')
<form method="POST" action="{{ route('manager.coupons.store') }}" class="panel mb-4 coupon-form">
    @csrf
    <div class="panel-head">New Coupon <small class="text-muted fw-normal">Percentage, Fixed, Product/Category, Minimum order, First order, Customer-specific, Expiry, Usage limit</small></div>
    <div class="panel-body">@include('manager._coupon-fields')<button class="btn btn-brand btn-sm mt-3">Create Coupon</button></div>
</form>
@endperm

<div class="panel mb-4">
    <div class="table-responsive">
        <table class="table mb-0 small align-middle">
            <thead class="table-light"><tr><th>Code</th><th>Discount</th><th>Rules</th><th>Validity</th><th>Used</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($coupons as $c)
                <tr>
                    <td><strong>{{ $c->code }}</strong><br><span class="text-muted">{{ $c->description }}</span></td>
                    <td>{{ $c->label() }}@if($c->max_discount)<br><span class="text-muted">max ৳{{ number_format($c->max_discount) }}</span>@endif</td>
                    <td>{{ ucfirst($c->scope) }}@if($c->min_order > 0) · min ৳{{ number_format($c->min_order) }}@endif @if($c->first_order_only)<br>First order @endif @if($c->customer)<br>Only: {{ $c->customer->name }}@endif</td>
                    <td>{{ $c->starts_at?->format('d M') ?? '—' }} → {{ $c->expires_at?->format('d M Y') ?? '∞' }}</td>
                    <td>{{ $c->used_count }}{{ $c->usage_limit ? ' / '.$c->usage_limit : '' }}</td>
                    <td><span class="badge bg-{{ $c->is_active && (! $c->expires_at || $c->expires_at->isFuture()) ? 'success' : 'secondary' }}">{{ $c->is_active ? ($c->expires_at?->isPast() ? 'Expired' : 'Active') : 'Off' }}</span></td>
                    <td class="text-end text-nowrap">
                        @perm('coupons.edit')<button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#cp-{{ $c->id }}"><i class="fa-solid fa-pen"></i></button>@endperm
                        @perm('coupons.delete')<form method="POST" action="{{ route('manager.coupons.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Delete coupon?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>@endperm
                    </td>
                </tr>
                @perm('coupons.edit')
                <tr class="collapse" id="cp-{{ $c->id }}"><td colspan="7" class="bg-light">
                    <form method="POST" action="{{ route('manager.coupons.update', $c) }}" class="coupon-form">@csrf @method('PUT')@include('manager._coupon-fields', ['c' => $c])<button class="btn btn-dark btn-sm mt-2">Update</button></form>
                </td></tr>
                @endperm
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No coupons yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($globalCoupons->isNotEmpty())
    <div class="panel"><div class="panel-head">Platform-wide coupons <small class="text-muted fw-normal">Super Admin — আপনার স্টোরেও প্রযোজ্য</small></div>
        <div class="panel-body small">@foreach ($globalCoupons as $g)<span class="badge bg-dark me-1">{{ $g->code }} · {{ $g->label() }}</span>@endforeach</div></div>
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('.coupon-form').forEach(f => {
    const sel = f.querySelector('.scope-sel');
    const apply = () => {
        f.querySelector('.scope-products')?.classList.toggle('d-none', sel.value !== 'products');
        f.querySelector('.scope-categories')?.classList.toggle('d-none', sel.value !== 'categories');
    };
    sel.addEventListener('change', apply);
});
</script>
@endpush
