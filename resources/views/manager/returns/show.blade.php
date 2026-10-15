@extends('layouts.manager')
@section('title', 'Return '.$return->number)

@section('content')
<a href="{{ route('manager.returns.index') }}" class="small text-muted"><i class="fa-solid fa-arrow-left"></i> Returns</a>
@php($items = $return->order->items->keyBy('id'))
<div class="row g-4 mt-1">
    <div class="col-xl-7">
        <div class="panel mb-3">
            <div class="panel-head">{{ $return->number }} <span class="badge bg-{{ $return->status_color }}">{{ $return->status_label }}</span></div>
            <div class="panel-body small">
                <div class="mb-2"><strong>{{ \App\Models\ReturnRequest::TYPES[$return->type] ?? $return->type }}</strong> — {{ \App\Models\ReturnRequest::REASONS[$return->reason] ?? $return->reason }}</div>
                @if ($return->details)<p class="bg-light p-2 rounded">{{ $return->details }}</p>@endif
                <table class="table table-sm">
                    <thead><tr><th>Item</th><th>Qty</th><th class="text-end">Value</th></tr></thead>
                    @foreach ($return->items as $line)
                        @php($oi = $items[$line['order_item_id']] ?? null)
                        <tr><td>{{ $oi?->product_name }} @if($oi?->variant_name)<span class="text-muted">({{ $oi->variant_name }})</span>@endif</td><td>{{ $line['quantity'] }}</td><td class="text-end">@money(($oi?->price ?? 0) * $line['quantity'])</td></tr>
                    @endforeach
                </table>
                <div>Order: <a href="{{ route('manager.orders.show', $return->order) }}">{{ $return->order->order_number }}</a> · {{ $return->order->customer_name }} ({{ $return->order->customer_phone }}) · Paid via {{ $return->order->method_label }}</div>
                @if ($return->admin_note)<div class="mt-2"><strong>Note:</strong> {{ $return->admin_note }}</div>@endif
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        @if ($return->isOpen())
            @perm('returns.edit')
            <form method="POST" action="{{ route('manager.returns.update', $return) }}" class="panel">@csrf @method('PATCH')
                <div class="panel-head">Process</div>
                <div class="panel-body small">
                    <label class="form-label mb-0">Next status</label>
                    <select name="status" class="form-select form-select-sm mb-2" id="retStatus">
                        @foreach (\App\Models\ReturnRequest::STATUSES as $k => [$label])
                            @continue($k === 'requested')
                            <option value="{{ $k }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <div id="refundBox" class="d-none">
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label mb-0">Refund ৳</label><input type="number" step="0.01" min="0" name="refund_amount" value="{{ $return->refund_amount }}" class="form-control form-control-sm"></div>
                            <div class="col-6"><label class="form-label mb-0">Method</label><select name="refund_method" class="form-select form-select-sm">
                                @if ($return->customer_id)<option value="wallet">Customer wallet</option>@endif
                                <option value="original">Original payment</option><option value="cash">Cash</option><option value="bkash">bKash</option><option value="nagad">Nagad</option></select></div>
                        </div>
                    </div>
                    <div class="form-check mb-2"><input type="hidden" name="restock" value="0"><input class="form-check-input" type="checkbox" name="restock" value="1" @checked($return->restock)><label class="form-check-label">পণ্য ফেরত এলে (Received) স্টকে যোগ করুন</label></div>
                    <textarea name="admin_note" rows="2" class="form-control form-control-sm mb-2" placeholder="Note">{{ $return->admin_note }}</textarea>
                    <button class="btn btn-brand btn-sm">Update</button>
                    <p class="text-muted mt-2 mb-0">Flow: Approved → Pickup → Received (restock) → Inspected → Refunded / Replaced / Exchanged</p>
                </div>
            </form>
            @endperm
        @else
            <div class="alert alert-light border small">Resolved {{ $return->resolved_at?->format('d M Y') }}@if($return->status === 'refunded') — @money($return->refund_amount) via {{ $return->refund_method }}@endif</div>
        @endif
    </div>
</div>
@push('scripts')
<script>
const rs = document.getElementById('retStatus');
if (rs) { const t = () => document.getElementById('refundBox').classList.toggle('d-none', rs.value !== 'refunded'); rs.addEventListener('change', t); t(); }
</script>
@endpush
@endsection
