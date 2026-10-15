<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** SaaS subscription invoice (platform → branch). */
class Invoice extends Model
{
    protected $fillable = [
        'number', 'branch_id', 'plan_id', 'type', 'billing_cycle', 'amount', 'discount', 'total', 'status',
        'payment_method', 'transaction_id', 'sender_number', 'notes', 'due_date', 'paid_at', 'period_start', 'period_end',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public static function generateNumber(): string
    {
        $prefix = 'INV-'.now()->format('ym').'-';
        $last = static::where('number', 'like', $prefix.'%')->orderByDesc('id')->value('number');
        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        do {
            $number = $prefix.str_pad((string) $seq++, 4, '0', STR_PAD_LEFT);
        } while (static::where('number', $number)->exists());

        return $number;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function isPayable(): bool
    {
        return in_array($this->status, ['unpaid', 'failed'], true) && $this->total > 0;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'pending_verification' => 'warning',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'dark',
        };
    }
}
