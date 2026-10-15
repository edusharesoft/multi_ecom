<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const STATUSES = [
        'present' => ['Present', 'success', 'P'], 'late' => ['Late', 'warning', 'L'], 'half_day' => ['Half day', 'info', 'H'],
        'absent' => ['Absent', 'danger', 'A'], 'leave' => ['Leave', 'primary', 'LV'], 'holiday' => ['Holiday', 'secondary', 'HD'],
    ];

    protected $fillable = ['branch_id', 'employee_id', 'date', 'status', 'check_in', 'check_out', 'overtime_hours', 'note', 'actor_name'];

    protected function casts(): array
    {
        return ['date' => 'date', 'overtime_hours' => 'decimal:2'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
