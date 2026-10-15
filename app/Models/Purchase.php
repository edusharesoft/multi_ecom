<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = ['branch_id', 'supplier_id', 'warehouse_id', 'number', 'reference', 'purchase_date', 'subtotal', 'discount', 'shipping', 'tax', 'total', 'paid', 'returned', 'note', 'actor_name'];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'shipping' => 'decimal:2', 'tax' => 'decimal:2',
            'total' => 'decimal:2', 'paid' => 'decimal:2', 'returned' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function getDueAttribute(): float
    {
        return max(0, round((float) $this->total - (float) $this->returned - (float) $this->paid, 2));
    }

    public function getPaymentStatusAttribute(): string
    {
        return $this->due <= 0 ? 'paid' : ((float) $this->paid > 0 ? 'partial' : 'due');
    }
}
