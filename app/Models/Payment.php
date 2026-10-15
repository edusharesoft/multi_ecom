<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id', 'invoice_id', 'gateway', 'reference', 'amount', 'currency', 'transaction_id', 'status', 'payload',
    ];

    /** Auto-post order receipts / refunds to the branch cash book. */
    protected static function booted(): void
    {
        static::saved(function (Payment $p) {
            if (! $p->order_id || ! ($p->wasRecentlyCreated || $p->wasChanged('status'))) {
                return;
            }
            $acc = app(\App\Services\AccountingService::class);
            in_array($p->status, ['success', 'refunded'], true) ? $acc->postPayment($p) : $acc->unpostPayment($p);
        });
    }

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payload' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
