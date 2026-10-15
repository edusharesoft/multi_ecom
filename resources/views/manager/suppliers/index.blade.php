@extends('layouts.manager')
@section('title', 'Suppliers')

@section('content')
<div class="row g-4">
    @perm('suppliers.add')
    <div class="col-lg-4">
        <form method="POST" action="{{ route('manager.suppliers.store') }}" class="panel">@csrf
            <div class="panel-head">Supplier Registration</div>
            <div class="panel-body small">
                <input name="name" class="form-control form-control-sm mb-2" placeholder="Contact name *" required>
                <input name="company" class="form-control form-control-sm mb-2" placeholder="Company">
                <div class="d-flex gap-2 mb-2"><input name="phone" class="form-control form-control-sm" placeholder="Phone"><input type="email" name="email" class="form-control form-control-sm" placeholder="Email"></div>
                <textarea name="address" rows="2" class="form-control form-control-sm mb-2" placeholder="Address"></textarea>
                <label class="form-label mb-0">Opening balance (আমরা পাওনা) ৳</label>
                <input type="number" step="0.01" name="opening_balance" value="0" class="form-control form-control-sm mb-3">
                <button class="btn btn-brand btn-sm w-100">Add Supplier</button>
            </div>
        </form>
    </div>
    @endperm
    <div class="col-lg-8">
        <div class="panel">
            <form class="panel-head" method="GET"><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" style="width:240px" placeholder="Search"><span class="small">Total supplier due: <strong class="text-danger">@money($totalDue)</strong></span></form>
            <div class="table-responsive"><table class="table table-hover mb-0 small align-middle">
                <thead class="table-light"><tr><th>Supplier</th><th>Purchases</th><th class="text-end">Purchased</th><th class="text-end">Paid</th><th class="text-end">Due</th></tr></thead>
                <tbody>
                @forelse ($suppliers as $s)
                    <tr onclick="location='{{ route('manager.suppliers.show', $s) }}'" style="cursor:pointer">
                        <td><strong>{{ $s->name }}</strong> @unless($s->is_active)<span class="badge bg-secondary">inactive</span>@endunless<br><span class="text-muted">{{ $s->company }} {{ $s->phone }}</span></td>
                        <td>{{ $s->purchases_count }}</td>
                        <td class="text-end">@money($s->purchased ?? 0)</td>
                        <td class="text-end">@money($s->paid ?? 0)</td>
                        <td class="text-end fw-semibold {{ $s->due_amount > 0 ? 'text-danger' : 'text-success' }}">@money($s->due_amount)</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">No suppliers yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
</div>
@endsection
