<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Personal access token for the branch REST API (only the sha256 hash is stored). */
class ApiToken extends Model
{
    public const ABILITIES = [
        'store.read' => 'Store info',
        'products.read' => 'Products — read', 'products.write' => 'Products — create / update / stock',
        'orders.read' => 'Orders — read', 'orders.write' => 'Orders — update status',
        'customers.read' => 'Customers — read', 'customers.write' => 'Customers — create',
    ];

    protected $fillable = ['branch_id', 'name', 'token_hash', 'prefix', 'abilities', 'last_used_at', 'last_used_ip', 'expires_at', 'created_by'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['abilities' => 'array', 'last_used_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** @return array{0: ApiToken, 1: string} token model + plain text (shown once) */
    public static function issue(Branch $branch, string $name, array $abilities, ?\DateTimeInterface $expires, string $by): array
    {
        $plain = 'mb_'.$branch->id.'_'.Str::random(40);
        $t = static::create([
            'branch_id' => $branch->id, 'name' => $name, 'token_hash' => hash('sha256', $plain), 'prefix' => substr($plain, 0, 10),
            'abilities' => array_values(array_intersect($abilities, array_keys(self::ABILITIES))), 'expires_at' => $expires, 'created_by' => $by,
        ]);

        return [$t, $plain];
    }

    public function can(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? [], true);
    }

    public function expired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
