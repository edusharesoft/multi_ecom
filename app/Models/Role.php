<?php

namespace App\Models;

use App\Support\Permissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['branch_id', 'name', 'description', 'permissions', 'is_system'];

    protected function casts(): array
    {
        return ['permissions' => 'array', 'is_system' => 'boolean'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function allows(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    public static function createDefaultsFor(Branch $branch): void
    {
        foreach (Permissions::defaultRoles() as $name => [$description, $permissions]) {
            static::firstOrCreate(
                ['branch_id' => $branch->id, 'name' => $name],
                ['description' => $description, 'permissions' => $permissions, 'is_system' => true]
            );
        }
    }
}
