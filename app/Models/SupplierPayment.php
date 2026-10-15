<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    public const METHODS = ['cash' => 'Cash', 'bank' => 'Bank', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'cheque' => 'Cheque'];

    protected $fillable = ['branch_id', 'supplier_id', 'purchase_id', 'amount', 'method', 'reference', 'payment_date', 'note', 'actor_name'];

    protected static function booted(): void
    {
        static::created(fn (SupplierPayment $p) => app(\App\Services\AccountingService::class)->postSupplierPayment($p));
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'date'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
