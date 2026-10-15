<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTransfer extends Model
{
    protected $fillable = ['branch_id', 'from_account_id', 'to_account_id', 'amount', 'transfer_date', 'note', 'actor_name'];

    protected function casts(): array
    {
        return ['transfer_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function from(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function to(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }
}
