<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * ShatajPay merchant credentials.
 * branch_id NULL = platform (Super Admin) account, otherwise the branch's own account.
 */
class PaymentGateway extends Model
{
    protected $fillable = [
        'branch_id', 'gateway', 'display_name', 'logo', 'gateway_id', 'app_key', 'app_secret',
        'app_password', 'app_username', 'mode', 'base_url', 'status',
    ];

    protected $hidden = ['app_key', 'app_secret', 'app_password'];

    protected static ?bool $ready = null;

    protected function casts(): array
    {
        return [
            'app_key' => 'encrypted',
            'app_secret' => 'encrypted',
            'app_password' => 'encrypted',
            'status' => 'boolean',
        ];
    }

    public static function ready(): bool
    {
        try {
            return static::$ready ??= Schema::hasTable('payment_gateways');
        } catch (Throwable) {
            return false;
        }
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /** Platform (Super Admin) ShatajPay row, or null. */
    public static function platform(string $gateway = 'shatajpay'): ?self
    {
        return static::ready() ? static::whereNull('branch_id')->where('gateway', $gateway)->first() : null;
    }

    /** A branch's own ShatajPay row, or null. */
    public static function forBranch(int $branchId, string $gateway = 'shatajpay'): ?self
    {
        return static::ready() ? static::where('branch_id', $branchId)->where('gateway', $gateway)->first() : null;
    }

    /** All four credentials + gateway id filled in. */
    public function isComplete(): bool
    {
        return filled($this->app_username) && filled($this->app_password) && filled($this->app_key)
            && filled($this->app_secret) && filled($this->gateway_id);
    }

    public function isUsable(): bool
    {
        return $this->status && $this->isComplete();
    }
}
