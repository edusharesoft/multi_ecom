<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    public const TYPES = ['in' => 'Stock In', 'out' => 'Stock Out', 'damage' => 'Damaged Stock'];

    protected $fillable = ['branch_id', 'warehouse_id', 'number', 'type', 'reason', 'note', 'actor_name'];

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }
}
