<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Manual expense or income entry. */
class Voucher extends Model
{
    protected $fillable = ['branch_id', 'number', 'kind', 'account_category_id', 'account_id', 'amount', 'voucher_date', 'party', 'reference', 'note', 'attachment', 'actor_name'];

    protected function casts(): array
    {
        return ['voucher_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
