@extends('layouts.store')
@section('title', 'Order '.$order->order_number)

@section('content')
<section class="section pt-2">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">@include('store.account._nav')</div>
            <div class="col-lg-9">
                @include('store._order-detail')
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    @if ($order->needsOnlinePayment())<a href="{{ route('store.payment', [$store, $order]) }}" class="btn btn-brand btn-sm">Complete Payment</a>@endif
                    @if ($order->canRequestReturn())<button class="btn btn-outline-dark btn-sm" data-bs-toggle="collapse" data-bs-target="#returnForm"><i class="fa-solid fa-rotate-left"></i> Return / Exchange</button>@endif
                </div>

                @if ($order->canRequestReturn())
                    <div class="collapse mt-3" id="returnForm">
                        <form method="POST" action="{{ route('store.account.return', [$store, $order]) }}" class="pd-box">
                            @csrf
                            <h6 class="fw-bold">Return Request</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6"><select name="type" class="form-select form-select-sm">@foreach (\App\Models\ReturnRequest::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                                <div class="col-md-6"><select name="reason" class="form-select form-select-sm">@foreach (\App\Models\ReturnRequest::REASONS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                            </div>
                            @foreach ($order->items as $item)
                                <div class="d-flex align-items-center gap-2 small mb-1">
                                    <span class="flex-fill">{{ $item->product_name }} @if($item->variant_name)({{ $item->variant_name }})@endif — কেনা: {{ $item->quantity }}</span>
                                    <input type="number" name="qty[{{ $item->id }}]" value="0" min="0" max="{{ $item->quantity }}" class="form-control form-control-sm" style="width:80px">
                                </div>
                            @endforeach
                            <textarea name="details" rows="2" class="form-control form-control-sm mt-2" placeholder="বিস্তারিত লিখুন (ঐচ্ছিক)"></textarea>
                            <button class="btn btn-brand btn-sm mt-2">Submit Request</button>
                        </form>
                    </div>
                @endif

                @if ($order->histories->isNotEmpty())
                    <div class="pd-box mt-3">
                        <h6 class="fw-bold">Order Updates</h6>
                        <ul class="list-unstyled small mb-0">
                            @foreach ($order->histories->reverse() as $h)
                                <li class="py-1 border-bottom"><span class="badge bg-{{ \App\Models\Order::STATUS_META[$h->status][1] ?? 'secondary' }}">{{ \App\Models\Order::STATUS_META[$h->status][0] ?? $h->status }}</span> {{ $h->note }} <span class="text-muted float-end">{{ $h->created_at->format('d M, h:i A') }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @foreach ($order->returns as $ret)
                    <div class="alert alert-light border small mt-3 mb-0">Return {{ $ret->number }}: <span class="badge bg-{{ $ret->status_color }}">{{ $ret->status_label }}</span> @if($ret->refund_amount > 0 && $ret->status === 'refunded') — @money($ret->refund_amount) refunded to {{ $ret->refund_method }}@endif</div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endsection
