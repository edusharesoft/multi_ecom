<?php

namespace App\Models;

use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Throwable;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'branch_id', 'actor_type', 'actor_id', 'actor_name', 'action', 'description',
        'subject_type', 'subject_id', 'properties', 'ip', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['properties' => 'array', 'created_at' => 'datetime'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Record an action. Actor is detected automatically (admin / owner / staff / system).
     */
    public static function record(string $action, string $description, ?Model $subject = null, array $properties = [], ?int $branchId = null): void
    {
        try {
            [$type, $id, $name] = static::detectActor();
            $request = app()->runningInConsole() ? null : request();

            static::create([
                'branch_id' => $branchId ?? Tenancy::branch()?->id ?? ($subject instanceof Branch ? $subject->id : ($subject?->branch_id ?? null)),
                'actor_type' => $type,
                'actor_id' => $id,
                'actor_name' => $name,
                'action' => $action,
                'description' => mb_substr($description, 0, 255),
                'subject_type' => $subject ? $subject->getMorphClass() : null,
                'subject_id' => $subject?->getKey(),
                'properties' => $properties ?: null,
                'ip' => $request?->ip(),
                'user_agent' => $request ? mb_substr((string) $request->userAgent(), 0, 255) : null,
            ]);
        } catch (Throwable $e) {
            report($e);   // logging must never break the request
        }
    }

    protected static function detectActor(): array
    {
        if (app()->runningInConsole()) {
            return ['system', null, 'System'];
        }
        if ($actor = Tenancy::actor()) {
            return [$actor instanceof Staff ? 'staff' : 'owner', $actor->id, $actor->name];
        }
        if ($admin = auth('web')->user()) {
            return ['admin', $admin->id, $admin->name];
        }
        if ($owner = auth('branch')->user()) {
            return ['owner', $owner->id, $owner->name];
        }
        if ($staff = auth('staff')->user()) {
            return ['staff', $staff->id, $staff->name];
        }

        return ['customer', null, 'Visitor'];
    }
}
