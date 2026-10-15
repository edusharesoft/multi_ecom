@extends('layouts.store')
@section('title', 'Returns — '.$store->display_name)

@section('content')
<section class="section pt-2">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">@include('store.account._nav')</div>
            <div class="col-lg-9">
                <div class="pd-box">
                    <h6 class="fw-bold">Return / Exchange Requests</h6>
                    <p class="small text-muted">ডেলিভারির {{ $store->option('return_days') }} দিনের মধ্যে অর্ডার পেজ থেকে রিটার্ন রিকোয়েস্ট করা যায়।</p>
                    <table class="table small mb-0">
                        <thead><tr><th>Request</th><th>Order</th><th>Type</th><th>Reason</th><th>Status</th><th>Refund</th></tr></thead>
                        @forelse ($returns as $r)
                            <tr><td class="fw-semibold">{{ $r->number }}<br><span class="text-muted">{{ $r->created_at->format('d M Y') }}</span></td>
                                <td><a href="{{ route('store.account.order', [$store, $r->order]) }}">{{ $r->order->order_number }}</a></td>
                                <td>{{ \App\Models\ReturnRequest::TYPES[$r->type] ?? $r->type }}</td>
                                <td>{{ \App\Models\ReturnRequest::REASONS[$r->reason] ?? $r->reason }}</td>
                                <td><span class="badge bg-{{ $r->status_color }}">{{ $r->status_label }}</span></td>
                                <td>@if($r->status === 'refunded')@money($r->refund_amount) <span class="text-muted">({{ $r->refund_method }})</span>@else — @endif</td></tr>
                        @empty
                            <tr><td colspan="6" class="text-muted text-center py-3">No return requests.</td></tr>
                        @endforelse
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
