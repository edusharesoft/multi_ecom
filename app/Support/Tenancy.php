<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\Staff;

/**
 * Holds the current tenant (branch) and the acting user for the running request.
 * Set by the ResolveTenant middleware for the branch backend.
 */
class Tenancy
{
    protected static ?Branch $branch = null;

    protected static Branch|Staff|null $actor = null;

    public static function set(Branch $branch, Branch|Staff $actor): void
    {
        static::$branch = $branch;
        static::$actor = $actor;
    }

    public static function branch(): ?Branch
    {
        return static::$branch;
    }

    public static function actor(): Branch|Staff|null
    {
        return static::$actor;
    }

    public static function isOwner(): bool
    {
        return static::$actor instanceof Branch;
    }

    public static function can(string $permission): bool
    {
        if (! static::$actor) {
            return false;
        }

        return static::isOwner() || static::$actor->hasPermission($permission);
    }

    /** Staff may only grant / manage permissions they hold themselves (owner: anything). */
    public static function canGrant(array $permissions): bool
    {
        if (static::isOwner()) {
            return true;
        }

        return static::$actor !== null && collect($permissions)->every(fn ($p) => static::$actor->hasPermission($p));
    }

    public static function actorName(): string
    {
        return static::$actor?->name ?? 'System';
    }
}
