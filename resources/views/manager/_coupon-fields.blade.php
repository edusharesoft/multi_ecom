{{-- Coupon form fields. expects optional $c (coupon), $categories, optional $products, $customers, $global --}}
@php($c = $c ?? null)
<div class="row g-2 small">
    <div class="col-md-3"><label class="form-label mb-0">Code *</label><input name="code" value="{{ $c?->code }}" class="form-control form-control-sm text-uppercase" required></div>
    <div class="col-md-3"><label class="form-label mb-0">Type</label><select name="type" class="form-select form-select-sm"><option value="percent" @selected($c?->type === 'percent')>Percentage %</option><option value="fixed" @selected($c?->type === 'fixed')>Fixed ৳</option></select></div>
    <div class="col-md-2"><label class="form-label mb-0">Value *</label><input type="number" step="0.01" min="0" name="value" value="{{ $c?->value }}" class="form-control form-control-sm" required></div>
    <div class="col-md-2"><label class="form-label mb-0">Min order ৳</label><input type="number" step="0.01" min="0" name="min_order" value="{{ $c?->min_order }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label mb-0">Max discount ৳</label><input type="number" step="0.01" min="0" name="max_discount" value="{{ $c?->max_discount }}" class="form-control form-control-sm"></div>
    <div class="col-md-4"><label class="form-label mb-0">Applies to</label>
        <select name="scope" class="form-select form-select-sm scope-sel">
            <option value="all" @selected(($c?->scope ?? 'all') === 'all')>Whole order</option>
            @unless ($global ?? false)<option value="products" @selected($c?->scope === 'products')>Selected products</option>@endunless
            <option value="categories" @selected($c?->scope === 'categories')>Selected categories</option>
        </select></div>
    @unless ($global ?? false)
        <div class="col-md-8 scope-products {{ $c?->scope === 'products' ? '' : 'd-none' }}"><label class="form-label mb-0">Products</label>
            <select name="product_ids[]" multiple class="form-select form-select-sm" size="4">@foreach ($products as $p)<option value="{{ $p->id }}" @selected(in_array($p->id, $c?->product_ids ?? []))>{{ $p->name }}</option>@endforeach</select></div>
    @endunless
    <div class="col-md-8 scope-categories {{ $c?->scope === 'categories' ? '' : 'd-none' }}"><label class="form-label mb-0">Categories</label>
        <select name="category_ids[]" multiple class="form-select form-select-sm" size="4">@foreach ($categories as $cat)<option value="{{ $cat->id }}" @selected(in_array($cat->id, $c?->category_ids ?? []))>{{ $cat->parent_id ? '— ' : '' }}{{ $cat->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label mb-0">Starts</label><input type="datetime-local" name="starts_at" value="{{ $c?->starts_at?->format('Y-m-d\TH:i') }}" class="form-control form-control-sm"></div>
    <div class="col-md-3"><label class="form-label mb-0">Expires</label><input type="datetime-local" name="expires_at" value="{{ $c?->expires_at?->format('Y-m-d\TH:i') }}" class="form-control form-control-sm"></div>
    <div class="col-md-2"><label class="form-label mb-0">Total uses</label><input type="number" min="1" name="usage_limit" value="{{ $c?->usage_limit }}" class="form-control form-control-sm" placeholder="∞"></div>
    <div class="col-md-2"><label class="form-label mb-0">Per customer</label><input type="number" min="1" name="per_customer_limit" value="{{ $c?->per_customer_limit }}" class="form-control form-control-sm" placeholder="∞"></div>
    @unless ($global ?? false)
        <div class="col-md-2"><label class="form-label mb-0">Only for</label><select name="customer_id" class="form-select form-select-sm"><option value="">Anyone</option>@foreach ($customers as $cu)<option value="{{ $cu->id }}" @selected($c?->customer_id === $cu->id)>{{ $cu->name }} ({{ $cu->phone }})</option>@endforeach</select></div>
    @endunless
    <div class="col-md-6"><label class="form-label mb-0">Description</label><input name="description" value="{{ $c?->description }}" class="form-control form-control-sm"></div>
    <div class="col-md-6 d-flex align-items-end gap-3">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="first_order_only" value="1" @checked($c?->first_order_only)><label class="form-check-label">First order only</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($c?->is_active ?? true)><label class="form-check-label">Active</label></div>
    </div>
</div>
