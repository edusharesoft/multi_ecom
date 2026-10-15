<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One cash-drawer shift at the POS. */
class PosSession extends Model
{
    protected $fillable = ['branch_id', 'warehouse_id', 'opened_by', 'closed_by', 'opening_cash', 'expected_cash', 'counted_cash', 'difference', 'summary', 'note', 'opened_at', 'closed_at'];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2', 'expected_cash' => 'decimal:2', 'counted_cash' => 'decimal:2', 'difference' => 'decimal:2',
            'summary' => 'array', 'opened_at' => 'datetime', 'closed_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class);
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /** Live totals for the drawer. */
    public function totals(): array
    {
        $payments = Payment::whereIn('order_id', $this->orders()->pluck('id'))->whereIn('status', ['success', 'refunded'])->get(['gateway', 'amount']);
        // sales per method (cash refunds are recorded as drawer "out" movements)
        $byMethod = $payments->where('amount', '>', 0)->groupBy('gateway')->map(fn ($g) => round((float) $g->sum('amount'), 2))->all();
        $in = (float) $this->cashMovements()->where('type', 'in')->sum('amount');
        $out = (float) $this->cashMovements()->where('type', 'out')->sum('amount');
        $cash = (float) ($byMethod['cash'] ?? 0);

        return [
            'sales_count' => $this->orders()->count(),
            'sales_total' => round((float) $this->orders()->sum('total'), 2),
            'refunds' => round(abs((float) $payments->where('amount', '<', 0)->sum('amount')), 2),
            'by_method' => $byMethod,
            'cash_in' => $in,
            'cash_out' => $out,
            'expected_cash' => round((float) $this->opening_cash + $cash + $in - $out, 2),
        ];
    }
}
