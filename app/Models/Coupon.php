<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'branch_id', 'code', 'description', 'type', 'value', 'min_order', 'max_discount', 'scope', 'product_ids', 'category_ids',
        'first_order_only', 'customer_id', 'usage_limit', 'per_customer_limit', 'starts_at', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'product_ids' => 'array',
            'category_ids' => 'array',
            'first_order_only' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isGlobal(): bool
    {
        return $this->branch_id === null;
    }

    public function label(): string
    {
        return $this->type === 'percent' ? rtrim(rtrim((string) $this->value, '0'), '.').'% OFF' : '৳'.number_format((float) $this->value).' OFF';
    }
}
