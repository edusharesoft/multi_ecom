@extends('layouts.manager')
@section('title', $supplier->name)

@section('content')
<a href="{{ route('manager.suppliers.index') }}" class="small text-muted"><i class="fa-solid fa-arrow-left"></i> Suppliers</a>
<div class="row g-4 mt-1">
    <div class="col-xl-4">
        <div class="panel mb-3"><div class="panel-body">
            <h5 class="fw-bold mb-0">{{ $supplier->name }}</h5>
            <div class="small text-muted">{{ $supplier->company }}<br>{{ $supplier->phone }} {{ $supplier->email }}<br>{{ $supplier->address }}</div>
            <div class="mt-3 fs-5">Due: <strong class="{{ $due > 0 ? 'text-danger' : 'text-success' }}">@money($due)</strong></div>
        </div></div>
        @perm('purchases.edit')
        <form method="POST" action="{{ route('manager.suppliers.pay', $supplier) }}" class="panel mb-3">@csrf
            <div class="panel-head">Payment to Supplier</div>
            <div class="panel-body small">
                <div class="d-flex gap-2 mb-2"><input type="number" step="0.01" min="0.01" name="amount" value="{{ max(0, $due) }}" class="form-control form-control-sm" required><select name="method" class="form-select form-select-sm">@foreach (\App\Models\SupplierPayment::METHODS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <div class="d-flex gap-2 mb-2"><input type="date" name="payment_date" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required><input name="reference" class="form-control form-control-sm" placeholder="Ref / TrxID / Cheque no"></div>
                <select name="purchase_id" class="form-select form-select-sm mb-2"><option value="">General payment (not tied to a purchase)</option>@foreach ($purchases->filter(fn ($p) => $p->due > 0) as $p)<option value="{{ $p->id }}">{{ $p->number }} — due ৳{{ number_format($p->due, 2) }}</option>@endforeach</select>
                <input name="note" class="form-control form-control-sm mb-2" placeholder="Note">
                <button class="btn btn-sm btn-brand">Record Payment</button>
            </div>
        </form>
        @endperm
        @perm('suppliers.edit')
        <form method="POST" action="{{ route('manager.suppliers.update', $supplier) }}" class="panel">@csrf @method('PUT')
            <div class="panel-head">Profile</div>
            <div class="panel-body small">
                <input name="name" value="{{ $supplier->name }}" class="form-control form-control-sm mb-1" required>
                <input name="company" value="{{ $supplier->company }}" class="form-control form-control-sm mb-1" placeholder="Company">
                <input name="phone" value="{{ $supplier->phone }}" class="form-control form-control-sm mb-1" placeholder="Phone">
                <input type="email" name="email" value="{{ $supplier->email }}" class="form-control form-control-sm mb-1" placeholder="Email">
                <textarea name="address" rows="2" class="form-control form-control-sm mb-1">{{ $supplier->address }}</textarea>
                <label class="form-label mb-0">Opening balance</label><input type="number" step="0.01" name="opening_balance" value="{{ $supplier->opening_balance }}" class="form-control form-control-sm mb-1">
                <textarea name="notes" rows="2" class="form-control form-control-sm mb-2" placeholder="Notes">{{ $supplier->notes }}</textarea>
                <div class="form-check mb-2"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($supplier->is_active)><label class="form-check-label">Active</label></div>
                <button class="btn btn-sm btn-dark">Save</button>
            </div>
        </form>
        @endperm
    </div>
    <div class="col-xl-8">
        <div class="panel">
            <form class="panel-head gap-2 justify-content-start" method="GET">
                <span>Supplier Ledger / Statement</span>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" style="width:140px">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" style="width:140px">
                <button class="btn btn-sm btn-dark">Show</button>
                <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-dark ms-auto"><i class="fa-solid fa-print"></i> Statement</button>
            </form>
            <div class="table-responsive"><table class="table table-sm mb-0 small">
                <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Ref</th><th class="text-end">Debit (paid/returned)</th><th class="text-end">Credit (purchased)</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                @foreach ($ledger as $row)
                    <tr>
                        <td>{{ $row['date']?->format('d M Y') }}</td>
                        <td>{{ $row['type'] }}</td>
                        <td>@if($row['url'])<a href="{{ $row['url'] }}">{{ $row['ref'] }}</a>@else{{ $row['ref'] }}@endif</td>
                        <td class="text-end">{{ $row['debit'] ? number_format($row['debit'], 2) : '' }}</td>
                        <td class="text-end">{{ $row['credit'] ? number_format($row['credit'], 2) : '' }}</td>
                        <td class="text-end fw-semibold">{{ number_format($row['balance'], 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    </div>
</div>
@endsection
