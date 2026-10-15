<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['payroll_id', 'employee_id', 'present_days', 'absent_days', 'unpaid_leave_days', 'overtime_hours', 'basic', 'allowance',
        'overtime_amount', 'bonus', 'deduction', 'other_deduction', 'net', 'note', 'paid_at', 'voucher_number'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'basic' => 'decimal:2', 'allowance' => 'decimal:2', 'overtime_amount' => 'decimal:2', 'bonus' => 'decimal:2',
            'deduction' => 'decimal:2', 'other_deduction' => 'decimal:2', 'net' => 'decimal:2', 'overtime_hours' => 'decimal:2'];
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recalc(): void
    {
        $this->net = round(max(0, (float) $this->basic + (float) $this->allowance + (float) $this->overtime_amount + (float) $this->bonus
            - (float) $this->deduction - (float) $this->other_deduction), 2);
    }
}
