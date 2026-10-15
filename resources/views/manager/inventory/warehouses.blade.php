@extends('layouts.manager')
@section('title', 'Warehouses')

@section('content')
@include('manager.inventory._nav')
<div class="row g-4">
    @perm('inventory.add')
    <div class="col-lg-4">
        <form method="POST" action="{{ route('manager.warehouses.store') }}" class="panel">@csrf
            <div class="panel-head">Add Warehouse <small class="text-muted fw-normal">{{ $warehouses->count() }} / {{ \App\Models\Plan::limitLabel($limit) }}</small></div>
            <div class="panel-body small">
                <input name="name" class="form-control form-control-sm mb-2" placeholder="Name (e.g. Mirpur Outlet)" required>
                <div class="d-flex gap-2 mb-2"><input name="code" class="form-control form-control-sm" placeholder="Code"><select name="type" class="form-select form-select-sm">@foreach (\App\Models\Warehouse::TYPES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                <input name="address" class="form-control form-control-sm mb-2" placeholder="Address">
                <input name="phone" class="form-control form-control-sm mb-2" placeholder="Phone">
                <select name="manager_id" class="form-select form-select-sm mb-3"><option value="">Warehouse manager (staff)</option>@foreach ($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
                <button class="btn btn-brand btn-sm w-100">Add</button>
            </div>
        </form>
    </div>
    @endperm
    <div class="col-lg-8">
        <div class="row g-3">
            @foreach ($warehouses as $w)
                <div class="col-md-6">
                    <form method="POST" action="{{ route('manager.warehouses.update', $w) }}" class="panel h-100">@csrf @method('PUT')
                        <div class="panel-head">{{ $w->name }} @if($w->is_default)<span class="badge bg-success">Default</span>@endif @unless($w->is_active)<span class="badge bg-secondary">Inactive</span>@endunless</div>
                        <div class="panel-body small">
                            <div class="d-flex justify-content-between mb-2"><span>Units: <strong>{{ number_format($stats[$w->id]->units ?? 0) }}</strong></span><span>Value: <strong>@money($stats[$w->id]->value ?? 0)</strong></span></div>
                            <input name="name" value="{{ $w->name }}" class="form-control form-control-sm mb-1" required>
                            <div class="d-flex gap-1 mb-1"><input name="code" value="{{ $w->code }}" class="form-control form-control-sm" placeholder="Code"><select name="type" class="form-select form-select-sm">@foreach (\App\Models\Warehouse::TYPES as $k => $v)<option value="{{ $k }}" @selected($w->type === $k)>{{ $v }}</option>@endforeach</select></div>
                            <input name="address" value="{{ $w->address }}" class="form-control form-control-sm mb-1" placeholder="Address">
                            <input name="phone" value="{{ $w->phone }}" class="form-control form-control-sm mb-1" placeholder="Phone">
                            <select name="manager_id" class="form-select form-select-sm mb-2"><option value="">No manager</option>@foreach ($staff as $s)<option value="{{ $s->id }}" @selected($w->manager_id === $s->id)>{{ $s->name }}</option>@endforeach</select>
                            <div class="d-flex gap-3 mb-2">
                                <div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($w->is_active)><label class="form-check-label">Active</label></div>
                                @unless($w->is_default)<div class="form-check"><input class="form-check-input" type="checkbox" name="is_default" value="1"><label class="form-check-label">Make default</label></div>@endunless
                            </div>
                            @perm('inventory.edit')<button class="btn btn-sm btn-dark">Save</button>
                            @unless($w->is_default)<button type="submit" form="del-w-{{ $w->id }}" class="btn btn-sm btn-link text-danger">Delete</button>@endunless @endperm
                            <a href="{{ route('manager.inventory.ledger', ['warehouse' => $w->id]) }}" class="btn btn-sm btn-link">Ledger</a>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('manager.warehouses.destroy', $w) }}" id="del-w-{{ $w->id }}" onsubmit="return confirm('Delete warehouse?')">@csrf @method('DELETE')</form>
                </div>
            @endforeach
        </div>
        <p class="small text-muted mt-3">অনলাইন অর্ডার ডিফল্ট ওয়্যারহাউস থেকে স্টক কমায়। POS যে ওয়্যারহাউস বেছে রেজিস্টার খোলা হয় সেখান থেকে কমায়।</p>
    </div>
</div>
@endsection
