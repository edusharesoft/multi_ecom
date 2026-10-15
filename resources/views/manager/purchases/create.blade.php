@extends('layouts.manager')
@section('title', 'New Purchase')

@section('content')
@if ($suppliers->isEmpty())
    <div class="alert alert-warning small">আগে একজন সাপ্লায়ার যোগ করুন — <a href="{{ route('manager.suppliers.index') }}">Suppliers</a></div>
@else
<form method="POST" action="{{ route('manager.purchases.store') }}" class="panel" id="purchaseForm">@csrf
    <div class="panel-body">
        <div class="row g-2 mb-3 small">
            <div class="col-md-3"><label class="form-label mb-0">Supplier *</label><select name="supplier_id" class="form-select form-select-sm" required>@foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }} {{ $s->company ? '('.$s->company.')' : '' }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label mb-0">Receive into *</label><select name="warehouse_id" id="purWh" class="form-select form-select-sm">@foreach ($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label mb-0">Date *</label><input type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" class="form-control form-control-sm" required></div>
            <div class="col-md-3"><label class="form-label mb-0">Supplier invoice / Ref</label><input name="reference" value="{{ old('reference') }}" class="form-control form-control-sm"></div>
        </div>
        @include('manager.inventory._item-picker', ['withCost' => true, 'warehouseSelect' => '#purWh'])
        <div class="row g-2 small justify-content-end">
            <div class="col-md-2"><label class="form-label mb-0">Discount ৳</label><input type="number" step="0.01" min="0" name="discount" value="0" class="form-control form-control-sm calc"></div>
            <div class="col-md-2"><label class="form-label mb-0">Shipping ৳</label><input type="number" step="0.01" min="0" name="shipping" value="0" class="form-control form-control-sm calc"></div>
            <div class="col-md-2"><label class="form-label mb-0">Tax / VAT ৳</label><input type="number" step="0.01" min="0" name="tax" value="0" class="form-control form-control-sm calc"></div>
            <div class="col-md-2"><label class="form-label mb-0">Grand total</label><div class="form-control form-control-sm fw-bold" id="grand">0.00</div></div>
        </div>
        <div class="row g-2 small justify-content-end mt-1">
            <div class="col-md-2"><label class="form-label mb-0">Paid now ৳</label><input type="number" step="0.01" min="0" name="paid" value="0" class="form-control form-control-sm"></div>
            <div class="col-md-2"><label class="form-label mb-0">Method</label><select name="payment_method" class="form-select form-select-sm">@foreach (\App\Models\SupplierPayment::METHODS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label mb-0">Note</label><input name="note" class="form-control form-control-sm"></div>
        </div>
        <div class="text-end mt-3"><button class="btn btn-brand px-4"><i class="fa-solid fa-check"></i> Save Purchase & Stock In</button></div>
    </div>
</form>
@endif
@endsection

@push('scripts')
<script>
(() => {
    const f = document.getElementById('purchaseForm'); if (!f) return;
    let sub = 0;
    const g = () => { const v = n => +f.querySelector(`[name=${n}]`).value || 0; document.getElementById('grand').textContent = Math.max(0, sub - v('discount') + v('shipping') + v('tax')).toFixed(2); };
    f.addEventListener('subtotal', e => { sub = e.detail; g(); });
    f.querySelectorAll('.calc').forEach(i => i.addEventListener('input', g));
})();
</script>
@endpush
