<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A money account of the branch: cash drawer, bank account, bKash/Nagad wallet… */
class Account extends Model
{
    public const TYPES = ['cash' => 'Cash', 'bank' => 'Bank', 'mobile' => 'Mobile Banking', 'other' => 'Other'];

    /** Payment methods that can be routed to an account automatically. */
    public const METHODS = [
        'cash' => 'Cash (POS)', 'cod' => 'Cash on Delivery', 'card' => 'Card', 'bank' => 'Bank transfer', 'cheque' => 'Cheque',
        'shatajpay' => 'ShatajPay', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket',
    ];

    protected $fillable = ['branch_id', 'name', 'type', 'account_number', 'bank_name', 'methods', 'opening_balance', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['methods' => 'array', 'opening_balance' => 'decimal:2', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AccountTransaction::class);
    }

    /** Balance as of a date (inclusive), or now. */
    public function balance(?string $asOf = null): float
    {
        $q = $this->transactions()->when($asOf, fn ($q) => $q->where('txn_date', '<=', $asOf));
        $in = (float) (clone $q)->where('direction', 'in')->sum('amount');
        $out = (float) (clone $q)->where('direction', 'out')->sum('amount');

        return round((float) $this->opening_balance + $in - $out, 2);
    }
}
