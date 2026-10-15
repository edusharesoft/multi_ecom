<div class="pd-box mb-3 mb-lg-0">
    <div class="d-flex align-items-center gap-2 mb-3">
        <div class="avatar rounded-circle bg-brand text-white d-flex align-items-center justify-content-center fw-bold" style="width:42px;height:42px">{{ mb_substr($storeCustomer->name, 0, 1) }}</div>
        <div><div class="fw-semibold">{{ $storeCustomer->name }}</div><small class="text-muted">{{ $storeCustomer->phone }}</small></div>
    </div>
    <div class="list-group list-group-flush small">
        @foreach ([['store.account', 'fa-receipt', 'My Orders'], ['store.account.profile', 'fa-user', 'Profile'], ['store.account.addresses', 'fa-location-dot', 'Addresses'], ['store.wishlist', 'fa-heart', 'Wishlist'], ['store.account.wallet', 'fa-wallet', 'Wallet & Points'], ['store.account.returns', 'fa-rotate-left', 'Returns']] as [$r, $i, $l])
            <a href="{{ route($r, $store) }}" class="list-group-item list-group-item-action px-0 {{ request()->routeIs($r) ? 'text-brand fw-semibold' : '' }}"><i class="fa-solid {{ $i }} me-2" style="width:16px"></i>{{ $l }}</a>
        @endforeach
        <form method="POST" action="{{ route('store.account.logout', $store) }}">@csrf<button class="list-group-item list-group-item-action px-0 text-danger border-0"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</button></form>
    </div>
</div>
