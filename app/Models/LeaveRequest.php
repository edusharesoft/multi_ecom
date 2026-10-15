<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    public const TYPES = ['casual' => 'Casual', 'sick' => 'Sick', 'annual' => 'Annual', 'unpaid' => 'Unpaid'];

    protected $fillable = ['branch_id', 'employee_id', 'type', 'from_date', 'to_date', 'days', 'reason', 'status', 'decided_by'];

    protected function casts(): array
    {
        return ['from_date' => 'date', 'to_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
