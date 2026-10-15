<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'opening' => ['Opening Stock', 'secondary'],
        'purchase' => ['Purchase', 'success'],
        'purchase_return' => ['Purchase Return', 'warning'],
        'sale' => ['Online Sale', 'primary'],
        'sale_return' => ['Sales Return', 'info'],
        'pos_sale' => ['POS Sale', 'primary'],
        'pos_return' => ['POS Return', 'info'],
        'cancel_restock' => ['Cancelled Order', 'info'],
        'adjustment_in' => ['Stock In (Adjust)', 'success'],
        'adjustment_out' => ['Stock Out (Adjust)', 'warning'],
        'damage' => ['Damaged', 'danger'],
        'transfer_in' => ['Transfer In', 'dark'],
        'transfer_out' => ['Transfer Out', 'dark'],
    ];

    protected $fillable = ['branch_id', 'warehouse_id', 'product_id', 'variant_id', 'type', 'quantity', 'balance_after', 'unit_cost', 'reference_type', 'reference_id', 'note', 'actor_name'];

    protected function casts(): array
    {
        return ['unit_cost' => 'decimal:2', 'created_at' => 'datetime'];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type][0] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPES[$this->type][1] ?? 'secondary';
    }
}
