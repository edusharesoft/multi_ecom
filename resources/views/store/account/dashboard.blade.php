@extends('layouts.store')
@section('title', 'My Account — '.$store->display_name)

@section('content')
<section class="section pt-2">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">@include('store.account._nav')</div>
            <div class="col-lg-9">
                <div class="row g-3 mb-3">
                    <div class="col-4"><div class="pd-box text-center"><div class="fs-4 fw-bold">{{ $stats['orders'] }}</div><small class="text-muted">Orders</small></div></div>
                    <div class="col-4"><div class="pd-box text-center"><div class="fs-4 fw-bold">@money($customer->wallet_balance)</div><small class="text-muted">Wallet</small></div></div>
                    <div class="col-4"><div class="pd-box text-center"><div class="fs-4 fw-bold">{{ $customer->loyalty_points }}</div><small class="text-muted">Loyalty Points</small></div></div>
                </div>
                <div class="pd-box">
                    <h5 class="fw-bold mb-3">Order History</h5>
                    <div class="table-responsive">
                        <table class="table small align-middle mb-0">
                            <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            @forelse ($orders as $o)
                                <tr>
                                    <td class="fw-semibold">{{ $o->order_number }}</td>
                                    <td>{{ $o->created_at->format('d M Y') }}</td>
                                    <td>{{ $o->items_count }}</td>
                                    <td>@money($o->total)</td>
                                    <td><span class="badge bg-{{ $o->status_color }}">{{ $o->status_label }}</span></td>
                                    <td class="text-end"><a href="{{ route('store.account.order', [$store, $o]) }}" class="btn btn-sm btn-light">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">এখনও কোনো অর্ডার নেই। <a href="{{ route('store.products', $store) }}">কেনাকাটা শুরু করুন</a></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
