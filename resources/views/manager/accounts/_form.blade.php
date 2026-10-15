<form method="POST" action="{{ $action }}" class="small">@csrf @if($method !== 'POST') @method($method) @endif
    <div class="row g-2">
        <div class="col-md-6"><label class="form-label mb-0">Name *</label><input name="name" value="{{ $a?->name }}" class="form-control form-control-sm" required></div>
        <div class="col-md-6"><label class="form-label mb-0">Type</label><select name="type" class="form-select form-select-sm">@foreach (\App\Models\Account::TYPES as $k => $v)<option value="{{ $k }}" @selected(($a?->type ?? 'bank') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label mb-0">Bank / Provider</label><input name="bank_name" value="{{ $a?->bank_name }}" class="form-control form-control-sm"></div>
        <div class="col-md-6"><label class="form-label mb-0">Account no.</label><input name="account_number" value="{{ $a?->account_number }}" class="form-control form-control-sm"></div>
        <div class="col-md-6"><label class="form-label mb-0">Opening balance ৳</label><input type="number" step="0.01" name="opening_balance" value="{{ $a?->opening_balance ?? 0 }}" class="form-control form-control-sm"></div>
        <div class="col-md-6 d-flex align-items-end gap-3">
            <div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($a?->is_active ?? true)><label class="form-check-label">Active</label></div>
            <div class="form-check"><input type="hidden" name="is_default" value="0"><input class="form-check-input" type="checkbox" name="is_default" value="1" @checked($a?->is_default)><label class="form-check-label">Default</label></div>
        </div>
        <div class="col-12"><label class="form-label mb-0">এই পেমেন্ট মেথডের টাকা এখানে জমা হবে</label>
            <div class="d-flex flex-wrap gap-2">@foreach (\App\Models\Account::METHODS as $k => $v)<label class="form-check-label"><input type="checkbox" class="form-check-input" name="methods[]" value="{{ $k }}" @checked(in_array($k, $a?->methods ?? [], true))> {{ $v }}</label>@endforeach</div></div>
        <div class="col-12"><button class="btn btn-sm btn-brand">{{ $a ? 'Save' : 'Add Account' }}</button></div>
    </div>
</form>
