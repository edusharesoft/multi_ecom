<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/** A custom domain (shop.example.com) pointing to a branch store. */
class Domain extends Model
{
    public const STATUS = [
        'pending' => ['DNS verification pending', 'warning'],
        'verified' => ['Verified — waiting for activation', 'info'],
        'active' => ['Active', 'success'],
        'rejected' => ['Rejected', 'danger'],
    ];

    protected $fillable = ['branch_id', 'host', 'status', 'verify_token', 'is_primary', 'redirect_to_domain', 'verified_at', 'last_checked_at', 'check_message'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'redirect_to_domain' => 'boolean', 'verified_at' => 'datetime', 'last_checked_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (Domain $d) => $d->verify_token ??= 'mb-verify-'.Str::lower(Str::random(24)));
        $flush = function (Domain $d) {
            Cache::forget('domain_host:'.$d->host);
            Cache::forget('domain_host:www.'.$d->host);
        };
        static::saved($flush);
        static::deleted($flush);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function normalize(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('#^https?://#', '', $host);

        return rtrim(explode('/', $host)[0], '.');
    }

    /** TXT record name the merchant has to create. */
    public function txtName(): string
    {
        return '_mbverify.'.$this->host;
    }
}
