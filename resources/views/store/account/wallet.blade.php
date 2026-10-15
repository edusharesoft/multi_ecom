@extends('layouts.store')
@section('title', 'Wallet — '.$store->display_name)

@section('content')
<section class="section pt-2">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">@include('store.account._nav')</div>
            <div class="col-lg-9">
                <div class="row g-3 mb-3">
                    <div class="col-md-6"><div class="pd-box"><small class="text-muted">Wallet balance</small><div class="fs-3 fw-bold text-brand">@money($customer->wallet_balance)</div><small class="text-muted">চেকআউটে ব্যবহার করা যায়। রিফান্ড এখানে জমা হয়।</small></div></div>
                    <div class="col-md-6"><div class="pd-box"><small class="text-muted">Loyalty points</small><div class="fs-3 fw-bold text-success">{{ $customer->loyalty_points }}</div><small class="text-muted">প্রতি ৳100 কেনাকাটায় {{ $store->option('points_per_100') }} পয়েন্ট</small></div></div>
                </div>
                <div class="pd-box">
                    <h6 class="fw-bold">Transactions</h6>
                    <table class="table small mb-0">
                        @forelse ($transactions as $t)
                            <tr><td>{{ $t->created_at->format('d M Y') }}</td><td>{{ $t->description }}</td><td class="text-end {{ $t->amount >= 0 ? 'text-success' : 'text-danger' }}">{{ $t->amount >= 0 ? '+' : '' }}{{ number_format($t->amount, 2) }}</td><td class="text-end text-muted">{{ number_format($t->balance_after, 2) }}</td></tr>
                        @empty
                            <tr><td class="text-muted text-center py-3">No transactions.</td></tr>
                        @endforelse
                    </table>
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
