@extends('layouts.store')
@section('title', 'Checkout — '.$store->display_name)

@section('content')
<section class="section pt-2">
    <div class="container" style="max-width:1000px">
        <h1 class="h4 fw-bold mb-3"><i class="fa-solid fa-truck-fast text-brand"></i> Checkout</h1>
        <form method="POST" action="{{ route('store.checkout.place', $store) }}">
            @csrf
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="pd-box">
                        @if (! $account)
                            <div class="info-box blue small mb-3"><i class="fa-regular fa-user"></i> আগে কেনাকাটা করেছেন? <a href="{{ route('store.account.login', $store) }}">লগইন করুন</a> — অর্ডার হিস্টোরি, সেভড ঠিকানা ও ওয়ালেট পাবেন। অথবা নিচে গেস্ট হিসেবে অর্ডার করুন।</div>
                        @endif
                        <h6 class="fw-bold mb-3">Shipping Information</h6>
                        @if ($addresses->isNotEmpty())
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Saved addresses</label>
                                <select class="form-select form-select-sm" id="savedAddress">
                                    <option value="">— নতুন ঠিকানা —</option>
                                    @foreach ($addresses as $a)
                                        <option value="{{ $a->id }}" data-name="{{ $a->name }}" data-phone="{{ $a->phone }}" data-address="{{ $a->fullAddress() }}" @selected($a->is_default)>{{ $a->label }} — {{ \Illuminate\Support\Str::limit($a->fullAddress(), 50) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Full Name *</label>
                                <input name="customer_name" value="{{ old('customer_name', $customer['customer_name'] ?? '') }}" class="form-control @error('customer_name') is-invalid @enderror" required>
                                @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Mobile *</label>
                                <input name="customer_phone" value="{{ old('customer_phone', $customer['customer_phone'] ?? '') }}" class="form-control @error('customer_phone') is-invalid @enderror" placeholder="01XXXXXXXXX" required>
                                @error('customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Email (optional)</label>
                                <input type="email" name="customer_email" value="{{ old('customer_email', $customer['customer_email'] ?? '') }}" class="form-control @error('customer_email') is-invalid @enderror">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Delivery Address *</label>
                                <textarea name="shipping_address" rows="3" class="form-control @error('shipping_address') is-invalid @enderror" required>{{ old('shipping_address', $customer['shipping_address'] ?? '') }}</textarea>
                                @error('shipping_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check small"><input class="form-check-input" type="checkbox" id="diffBilling" onchange="document.getElementById('billingBox').classList.toggle('d-none', !this.checked)"><label class="form-check-label" for="diffBilling">Billing address আলাদা</label></div>
                                <div id="billingBox" class="d-none mt-2"><textarea name="billing_address" rows="2" class="form-control" placeholder="Billing address">{{ old('billing_address') }}</textarea></div>
                                @if ($account)<div class="form-check small mt-1"><input class="form-check-input" type="checkbox" name="save_address" value="1" id="saveAddr" @checked($addresses->isEmpty())><label class="form-check-label" for="saveAddr">এই ঠিকানা সেভ করুন</label></div>@endif
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Order Note (optional)</label>
                                <input name="note" value="{{ old('note') }}" class="form-control">
                            </div>
                        </div>

                        <h6 class="fw-bold mt-4 mb-2">Payment Method</h6>
                        @include('store._methods', ['methods' => $methods, 'selected' => old('payment_method', array_key_first($methods))])
                        @error('payment_method')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="pd-box mb-3">
                        <strong>Your Items</strong>
                        @foreach ($items as $item)
                            <div class="d-flex justify-content-between small py-2 border-bottom">
                                <span>{{ $item->product->name }} @if($item->variant)<span class="text-muted">({{ $item->variant->name }})</span>@endif × {{ $item->quantity }}</span>
                                <span>@money($item->total)</span>
                            </div>
                        @endforeach
                    </div>
                    @include('store._summary', ['totals' => $totals])
                    @if ($account && $account->wallet_balance > 0)
                        <div class="pd-box mt-3 small">
                            <div class="form-check"><input class="form-check-input" type="checkbox" name="use_wallet" value="1" id="useWallet"><label class="form-check-label" for="useWallet">ওয়ালেট ব্যালেন্স ব্যবহার করুন (<strong>@money($account->wallet_balance)</strong>)</label></div>
                        </div>
                    @endif
                    <button class="btn btn-brand w-100 mt-3 py-2 fw-semibold"><i class="fa-solid fa-lock"></i> Place Order</button>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const sel = document.getElementById('savedAddress');
    if (!sel) return;
    const fill = () => {
        const o = sel.selectedOptions[0];
        if (!o || !o.value) return;
        document.querySelector('[name=customer_name]').value = o.dataset.name;
        document.querySelector('[name=customer_phone]').value = o.dataset.phone;
        document.querySelector('[name=shipping_address]').value = o.dataset.address;
    };
    sel.addEventListener('change', fill);
    if (!document.querySelector('[name=shipping_address]').value) fill();
})();
</script>
@endpush
