@extends('layouts.manager')
@section('title', 'Purchase '.$purchase->number)

@section('content')
<div class="d-flex justify-content-between"><a href="{{ route('manager.purchases.index') }}" class="small text-muted"><i class="fa-solid fa-arrow-left"></i> Purchases</a>
    @perm('purchases.print')<button onclick="window.print()" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-print"></i> Print</button>@endperm</div>
<div class="row g-4 mt-1">
    <div class="col-xl-8">
        <div class="panel mb-3">
            <div class="panel-head">{{ $purchase->number }} <span class="badge bg-{{ ['paid' => 'success', 'partial' => 'warning', 'due' => 'danger'][$purchase->payment_status] }}">{{ ucfirst($purchase->payment_status) }}</span></div>
            <div class="panel-body small">
                <div class="row mb-2"><div class="col">Supplier: <a href="{{ route('manager.suppliers.show', $purchase->supplier) }}"><strong>{{ $purchase->supplier->name }}</strong></a><br>{{ $purchase->supplier->phone }}</div>
                    <div class="col text-end">Date: {{ $purchase->purchase_date->format('d M Y') }}<br>Warehouse: {{ $purchase->warehouse->name }}<br>Ref: {{ $purchase->reference ?: '—' }}</div></div>
                <table class="table table-sm">
                    <thead><tr><th>Item</th><th class="text-end">Qty</th><th class="text-end">Returned</th><th class="text-end">Unit cost</th><th class="text-end">Total</th></tr></thead>
                    @foreach ($purchase->items as $i)<tr><td>{{ $i->name }}</td><td class="text-end">{{ $i->quantity }}</td><td class="text-end">{{ $i->returned_qty ?: '' }}</td><td class="text-end">{{ number_format($i->unit_cost, 2) }}</td><td class="text-end">{{ number_format($i->total, 2) }}</td></tr>@endforeach
                    <tfoot>
                        <tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end">{{ number_format($purchase->subtotal, 2) }}</td></tr>
                        @if($purchase->discount > 0)<tr><td colspan="4" class="text-end">Discount</td><td class="text-end">− {{ number_format($purchase->discount, 2) }}</td></tr>@endif
                        @if($purchase->shipping > 0)<tr><td colspan="4" class="text-end">Shipping</td><td class="text-end">{{ number_format($purchase->shipping, 2) }}</td></tr>@endif
                        @if($purchase->tax > 0)<tr><td colspan="4" class="text-end">Tax</td><td class="text-end">{{ number_format($purchase->tax, 2) }}</td></tr>@endif
                        <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="text-end">@money($purchase->total)</td></tr>
                        @if($purchase->returned > 0)<tr><td colspan="4" class="text-end">Returned</td><td class="text-end">− @money($purchase->returned)</td></tr>@endif
                        <tr><td colspan="4" class="text-end">Paid</td><td class="text-end">@money($purchase->paid)</td></tr>
                        <tr class="fw-bold text-danger"><td colspan="4" class="text-end">Due</td><td class="text-end">@money($purchase->due)</td></tr>
                    </tfoot>
                </table>
                @if ($purchase->note)<p>{{ $purchase->note }}</p>@endif
                <div class="text-muted">Entered by {{ $purchase->actor_name }}</div>
            </div>
        </div>
        @if ($purchase->returns->isNotEmpty())
            <div class="panel"><div class="panel-head">Purchase Returns</div>
                <ul class="list-group list-group-flush small">@foreach ($purchase->returns as $r)<li class="list-group-item d-flex justify-content-between"><span>{{ $r->number }} · {{ $r->return_date->format('d M Y') }} · {{ $r->items->sum('quantity') }} pcs {{ $r->note }}</span><span>@money($r->total)</span></li>@endforeach</ul></div>
        @endif
    </div>
    <div class="col-xl-4">
        @perm('purchases.edit')
        @if ($purchase->due > 0)
            <form method="POST" action="{{ route('manager.purchases.pay', $purchase) }}" class="panel mb-3">@csrf
                <div class="panel-head">Add Payment</div>
                <div class="panel-body small">
                    <div class="d-flex gap-2 mb-2"><input type="number" step="0.01" min="0.01" max="{{ $purchase->due }}" name="amount" value="{{ $purchase->due }}" class="form-control form-control-sm"><select name="method" class="form-select form-select-sm">@foreach (\App\Models\SupplierPayment::METHODS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                    <input name="reference" class="form-control form-control-sm mb-2" placeholder="Reference">
                    <button class="btn btn-sm btn-brand">Pay</button>
                </div>
            </form>
        @endif
        @if ($purchase->items->contains(fn ($i) => $i->quantity > $i->returned_qty))
            <form method="POST" action="{{ route('manager.purchases.return', $purchase) }}" class="panel mb-3" onsubmit="return confirm('Return these items to the supplier?')">@csrf
                <div class="panel-head">Purchase Return</div>
                <div class="panel-body small">
                    @foreach ($purchase->items as $i)
                        @if ($i->quantity > $i->returned_qty)
                            <div class="d-flex align-items-center gap-2 mb-1"><span class="flex-fill">{{ $i->name }} <span class="text-muted">(max {{ $i->quantity - $i->returned_qty }})</span></span><input type="number" min="0" max="{{ $i->quantity - $i->returned_qty }}" name="qty[{{ $i->id }}]" value="0" class="form-control form-control-sm" style="width:70px"></div>
                        @endif
                    @endforeach
                    <input name="note" class="form-control form-control-sm my-2" placeholder="Reason">
                    <button class="btn btn-sm btn-outline-danger">Return & Stock Out</button>
                </div>
            </form>
        @endif
        @endperm
        <div class="panel"><div class="panel-head">Payments</div>
            <ul class="list-group list-group-flush small">
                @forelse ($purchase->payments as $pay)<li class="list-group-item d-flex justify-content-between"><span>{{ $pay->payment_date->format('d M Y') }} · {{ \App\Models\SupplierPayment::METHODS[$pay->method] ?? $pay->method }} {{ $pay->reference }}</span><span>@money($pay->amount)</span></li>
                @empty<li class="list-group-item text-muted">No payments.</li>@endforelse
            </ul></div>
    </div>
</div>
@endsection
