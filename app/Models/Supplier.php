<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = ['branch_id', 'name', 'company', 'phone', 'email', 'address', 'opening_balance', 'notes', 'is_active'];

    protected function casts(): array
    {
        return ['opening_balance' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /** Amount we still owe the supplier. */
    public function due(): float
    {
        return round((float) $this->opening_balance
            + (float) $this->purchases()->sum('total')
            - (float) $this->returns()->sum('total')
            - (float) $this->payments()->sum('amount'), 2);
    }

    /**
     * Supplier ledger: opening, purchases (credit), returns & payments (debit) with running balance.
     *
     * @return \Illuminate\Support\Collection<int, array{date: mixed, type: string, ref: string, debit: float, credit: float, balance: float, url: ?string}>
     */
    public function ledger(?string $from = null, ?string $to = null)
    {
        $rows = collect();
        foreach ($this->purchases()->get() as $p) {
            $rows->push(['date' => $p->purchase_date, 'type' => 'Purchase', 'ref' => $p->number, 'debit' => 0.0, 'credit' => (float) $p->total, 'url' => route('manager.purchases.show', $p), 'sort' => $p->created_at]);
        }
        foreach ($this->returns()->get() as $r) {
            $rows->push(['date' => $r->return_date, 'type' => 'Purchase Return', 'ref' => $r->number, 'debit' => (float) $r->total, 'credit' => 0.0, 'url' => null, 'sort' => $r->created_at]);
        }
        foreach ($this->payments()->get() as $pay) {
            $rows->push(['date' => $pay->payment_date, 'type' => 'Payment ('.$pay->method.')', 'ref' => $pay->reference ?: '#'.$pay->id, 'debit' => (float) $pay->amount, 'credit' => 0.0, 'url' => null, 'sort' => $pay->created_at]);
        }
        $rows = $rows->sortBy([['date', 'asc'], ['sort', 'asc']])->values();

        $balance = (float) $this->opening_balance;
        $out = collect([['date' => null, 'type' => 'Opening Balance', 'ref' => '', 'debit' => 0.0, 'credit' => $balance, 'balance' => $balance, 'url' => null]]);
        foreach ($rows as $row) {
            $balance += $row['credit'] - $row['debit'];
            $row['balance'] = round($balance, 2);
            unset($row['sort']);
            $out->push($row);
        }

        if ($from || $to) {
            $out = $out->filter(fn ($r) => $r['date'] === null || ((! $from || $r['date']->toDateString() >= $from) && (! $to || $r['date']->toDateString() <= $to)))->values();
        }

        return $out;
    }
}
