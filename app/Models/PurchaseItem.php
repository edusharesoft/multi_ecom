<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['purchase_id', 'product_id', 'variant_id', 'name', 'quantity', 'returned_qty', 'unit_cost', 'total'];

    protected function casts(): array
    {
        return ['unit_cost' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
