@extends('layouts.admin')
@section('title', 'Global Coupons / Promotions')

@section('content')
<p class="small text-muted">এই কুপনগুলো প্ল্যাটফর্মের সব ব্রাঞ্চ স্টোরে কাজ করবে (প্ল্যানে coupon ফিচার না থাকলেও)। ছাড়ের খরচ ব্রাঞ্চের অর্ডারে ডিসকাউন্ট হিসেবে কাটা হয়।</p>
<form method="POST" action="{{ route('admin.coupons.store') }}" class="panel mb-4 coupon-form">
    @csrf
    <div class="panel-head">New Global Coupon</div>
    <div class="panel-body">@include('manager._coupon-fields', ['global' => true])<button class="btn btn-primary btn-sm mt-3">Create</button></div>
</form>
<div class="panel">
    <div class="table-responsive">
        <table class="table mb-0 small align-middle">
            <thead class="table-light"><tr><th>Code</th><th>Discount</th><th>Rules</th><th>Validity</th><th>Used</th><th>Total discount</th><th></th></tr></thead>
            <tbody>
            @forelse ($coupons as $c)
                <tr>
                    <td><strong>{{ $c->code }}</strong><br><span class="text-muted">{{ $c->description }}</span></td>
                    <td>{{ $c->label() }}</td>
                    <td>{{ ucfirst($c->scope) }}@if($c->min_order > 0) · min ৳{{ number_format($c->min_order) }}@endif @if($c->first_order_only) · first order @endif</td>
                    <td>{{ $c->starts_at?->format('d M') ?? '—' }} → {{ $c->expires_at?->format('d M Y') ?? '∞' }} @unless($c->is_active)<span class="badge bg-secondary">off</span>@endunless</td>
                    <td>{{ $c->usages_count }}{{ $c->usage_limit ? ' / '.$c->usage_limit : '' }}</td>
                    <td>৳ {{ number_format($c->usages_sum_discount ?? 0, 2) }}</td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#gc-{{ $c->id }}"><i class="fa-solid fa-pen"></i></button>
                        <form method="POST" action="{{ route('admin.coupons.destroy', $c) }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>
                    </td>
                </tr>
                <tr class="collapse" id="gc-{{ $c->id }}"><td colspan="7" class="bg-light">
                    <form method="POST" action="{{ route('admin.coupons.update', $c) }}" class="coupon-form">@csrf @method('PUT')@include('manager._coupon-fields', ['c' => $c, 'global' => true])<button class="btn btn-dark btn-sm mt-2">Update</button></form>
                </td></tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No global coupons.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.coupon-form').forEach(f => {
    const sel = f.querySelector('.scope-sel');
    sel.addEventListener('change', () => f.querySelector('.scope-categories')?.classList.toggle('d-none', sel.value !== 'categories'));
});
</script>
@endpush
