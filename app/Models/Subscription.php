<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    public const STATUSES = ['trial', 'active', 'pending', 'grace', 'expired', 'cancelled'];

    protected $fillable = [
        'branch_id', 'plan_id', 'billing_cycle', 'status', 'amount', 'starts_at', 'ends_at', 'trial_ends_at',
        'grace_ends_at', 'cancelled_at', 'auto_renew', 'next_plan_id', 'next_cycle', 'reminders_sent',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
            'reminders_sent' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function nextPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'next_plan_id');
    }

    /** Subscription gives access to its plan's features right now. */
    public function isUsable(): bool
    {
        return in_array($this->status, ['trial', 'active', 'grace'], true);
    }

    public function isReadOnly(): bool
    {
        return $this->status === 'expired' && Setting::get('expiry_action') === 'readonly';
    }

    public function daysLeft(): ?int
    {
        $end = $this->status === 'grace' ? $this->grace_ends_at : $this->ends_at;

        return $end ? (int) max(0, ceil(now()->diffInDays($end, false))) : null;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'trial' => 'info',
            'pending' => 'secondary',
            'grace' => 'warning',
            'expired', 'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'trial' => 'Free Trial',
            'grace' => 'Grace Period',
            'pending' => 'Awaiting Payment',
            default => ucfirst($this->status),
        };
    }
}
