<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** One line of the cash book. amount is always positive; direction says in/out. */
class AccountTransaction extends Model
{
    public const CATEGORIES = [
        'sale' => 'Sale receipt', 'refund' => 'Customer refund', 'supplier_payment' => 'Supplier payment',
        'expense' => 'Expense', 'income' => 'Other income', 'transfer_in' => 'Transfer in', 'transfer_out' => 'Transfer out',
        'drawer_in' => 'POS cash in', 'drawer_out' => 'POS cash out', 'adjustment' => 'Adjustment',
    ];

    protected $fillable = ['branch_id', 'account_id', 'txn_date', 'direction', 'amount', 'category', 'reference_type', 'reference_id', 'description', 'actor_name'];

    protected function casts(): array
    {
        return ['txn_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function signed(): float
    {
        return $this->direction === 'in' ? (float) $this->amount : -(float) $this->amount;
    }
}
