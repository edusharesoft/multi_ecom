<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A branch's API credentials for one courier. */
class CourierAccount extends Model
{
    public const COURIERS = [
        'steadfast' => ['Steadfast Courier', ['api_key' => 'API Key', 'secret_key' => 'Secret Key']],
        'pathao' => ['Pathao Courier', ['client_id' => 'Client ID', 'client_secret' => 'Client Secret', 'username' => 'Username (email)', 'password' => 'Password', 'store_id' => 'Store ID']],
        'redx' => ['RedX', ['access_token' => 'API Access Token']],
        'manual' => ['Manual / Other courier', []],
    ];

    protected $fillable = ['branch_id', 'courier', 'credentials', 'mode', 'webhook_token', 'is_active', 'is_default'];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array', 'is_active' => 'boolean', 'is_default' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (CourierAccount $a) => $a->webhook_token ??= Str::random(32));
    }

    public function label(): string
    {
        return self::COURIERS[$this->courier][0] ?? ucfirst($this->courier);
    }

    public function cred(string $key): ?string
    {
        return ($this->credentials ?? [])[$key] ?? null;
    }
}
