<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = ['branch_id', 'month', 'status', 'working_days', 'total_net', 'paid_at', 'actor_name'];

    protected function casts(): array
    {
        return ['total_net' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function label(): string
    {
        return \Illuminate\Support\Carbon::parse($this->month.'-01')->format('F Y');
    }
}
