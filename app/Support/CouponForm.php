<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Shared validation for branch & global coupons. */
class CouponForm
{
    public static function validate(Request $request, ?int $branchId, ?int $ignoreId = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->code))]);

        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:40', Rule::unique('coupons', 'code')->where(fn ($q) => $branchId ? $q->where('branch_id', $branchId) : $q->whereNull('branch_id'))->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:0.01', $request->type === 'percent' ? 'max:100' : 'max:10000000'],
            'min_order' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'scope' => ['required', Rule::in(['all', 'products', 'categories'])],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer'],
            'customer_id' => ['nullable', 'integer'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data['min_order'] = $data['min_order'] ?? 0;
        $data['first_order_only'] = $request->boolean('first_order_only');
        $data['is_active'] = $request->boolean('is_active');
        $data['product_ids'] = $data['scope'] === 'products' ? array_values(array_map('intval', $data['product_ids'] ?? [])) : null;
        $data['category_ids'] = $data['scope'] === 'categories' ? array_values(array_map('intval', $data['category_ids'] ?? [])) : null;

        return $data;
    }
}
