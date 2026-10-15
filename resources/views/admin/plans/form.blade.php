@extends('layouts.admin')
@section('title', $plan->exists ? 'Edit Plan: '.$plan->name : 'New Plan')

@section('content')
<form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}">
    @csrf
    @if ($plan->exists) @method('PUT') @endif
    <div class="row g-4">
        <div class="col-xl-6">
            <div class="panel mb-4">
                <div class="panel-head">Plan & Pricing</div>
                <div class="panel-body row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Name *</label><input name="name" value="{{ old('name', $plan->name) }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label small fw-semibold">Sort</label><input type="number" name="sort" value="{{ old('sort', $plan->sort) }}" class="form-control"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Description</label><input name="description" value="{{ old('description', $plan->description) }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Monthly (৳)</label><input type="number" step="0.01" min="0" name="price_monthly" value="{{ old('price_monthly', $plan->price_monthly ?? 0) }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Yearly (৳)</label><input type="number" step="0.01" min="0" name="price_yearly" value="{{ old('price_yearly', $plan->price_yearly ?? 0) }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Lifetime (৳)</label><input type="number" step="0.01" min="0" name="price_lifetime" value="{{ old('price_lifetime', $plan->price_lifetime) }}" class="form-control" placeholder="empty = not offered"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Trial days</label><input type="number" min="0" name="trial_days" value="{{ old('trial_days', $plan->trial_days ?? 0) }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Commission %</label><input type="number" step="0.01" min="0" name="commission_percent" value="{{ old('commission_percent', $plan->commission_percent ?? 0) }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Custom plan for</label>
                        <select name="branch_id" class="form-select"><option value="">— Public —</option>@foreach ($branches as $b)<option value="{{ $b->id }}" @selected(old('branch_id', $plan->branch_id) == $b->id)>{{ $b->store_name ?: $b->name }}</option>@endforeach</select></div>
                    <div class="col-12 d-flex gap-4">
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="pa" @checked(old('is_active', $plan->is_active))><label class="form-check-label small" for="pa">Active</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="pf" @checked(old('is_featured', $plan->is_featured))><label class="form-check-label small" for="pf">Most popular</label></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_free" value="1" id="pfr" @checked(old('is_free', $plan->is_free))><label class="form-check-label small" for="pfr">Free plan</label></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="panel mb-4">
                <div class="panel-head">Usage Limits <small class="text-muted fw-normal">-1 = unlimited</small></div>
                <div class="panel-body row g-2">
                    @foreach (\App\Models\Plan::LIMITS as $key => $label)
                        <div class="col-md-6"><label class="form-label small mb-0">{{ $label }}</label><input type="number" min="-1" name="limits[{{ $key }}]" value="{{ old('limits.'.$key, $plan->limit($key)) }}" class="form-control form-control-sm" required></div>
                    @endforeach
                </div>
            </div>
            <div class="panel">
                <div class="panel-head">Features (Lock / Unlock)</div>
                <div class="panel-body row g-2">
                    @foreach (\App\Models\Plan::FEATURES as $key => $label)
                        <div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="features[]" value="{{ $key }}" id="f-{{ $key }}" @checked(in_array($key, old('features', collect($plan->features ?? [])->filter()->keys()->all()), true))><label class="form-check-label small" for="f-{{ $key }}">{{ $label }}</label></div></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3 d-flex justify-content-between"><a href="{{ route('admin.plans.index') }}" class="btn btn-link">Cancel</a><button class="btn btn-primary px-5">Save Plan</button></div>
</form>
@endsection
