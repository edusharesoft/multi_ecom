<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    protected $fillable = ['branch_id', 'staff_id', 'code', 'name', 'phone', 'email', 'designation', 'department', 'join_date', 'salary_type',
        'basic_salary', 'allowance', 'address', 'emergency_contact', 'photo', 'is_active', 'notes'];

    protected function casts(): array
    {
        return ['join_date' => 'date', 'basic_salary' => 'decimal:2', 'allowance' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }
}
