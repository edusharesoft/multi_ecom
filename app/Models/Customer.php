<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Storefront customer — belongs to exactly one branch (tenant). Guard "customer". */
class Customer extends Authenticatable
{
    public const GROUPS = ['general' => 'General', 'wholesale' => 'Wholesale', 'vip' => 'VIP', 'staff' => 'Staff'];

    protected $fillable = ['branch_id', 'name', 'phone', 'email', 'password', 'group', 'notes', 'is_active', 'last_login_at', 'marketing_opt_out'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'wallet_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'marketing_opt_out' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->orderByDesc('is_default');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest();
    }

    public function wishlist(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest();
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRequest::class)->latest();
    }

    /** Credit (+) or debit (−) the wallet atomically. */
    public function adjustWallet(float $amount, string $type, string $description = '', ?int $orderId = null): WalletTransaction
    {
        return DB::transaction(function () use ($amount, $type, $description, $orderId) {
            $me = static::whereKey($this->id)->lockForUpdate()->first();
            $new = round((float) $me->wallet_balance + $amount, 2);
            if ($new < 0) {
                throw new InvalidArgumentException('Insufficient wallet balance.');
            }
            $me->forceFill(['wallet_balance' => $new])->save();
            $this->wallet_balance = $new;

            return $me->walletTransactions()->create([
                'amount' => $amount, 'balance_after' => $new, 'type' => $type, 'description' => $description, 'order_id' => $orderId,
            ]);
        });
    }

    /**
     * CRM segment derived from order history.
     * new (≤30 days, ≤1 order) · vip (group) · high_value · regular · inactive (no order in 90 days)
     */
    public function segment(?float $totalSpent = null, ?int $orderCount = null, $lastOrderAt = null): string
    {
        $totalSpent ??= (float) $this->orders()->where('status', '!=', 'cancelled')->sum('total');
        $orderCount ??= $this->orders()->where('status', '!=', 'cancelled')->count();
        $lastOrderAt ??= $this->orders()->max('created_at');

        if ($this->group === 'vip') {
            return 'vip';
        }
        if ($totalSpent >= (float) ($this->branch->option('high_value_amount', 20000))) {
            return 'high_value';
        }
        if ($orderCount <= 1 && $this->created_at && $this->created_at->gt(now()->subDays(30))) {
            return 'new';
        }
        if (! $lastOrderAt || \Illuminate\Support\Carbon::parse($lastOrderAt)->lt(now()->subDays(90))) {
            return 'inactive';
        }

        return $orderCount >= 3 ? 'regular' : 'new';
    }

    public const SEGMENTS = [
        'new' => ['New Customer', 'info'],
        'regular' => ['Regular Customer', 'primary'],
        'vip' => ['VIP Customer', 'warning'],
        'high_value' => ['High Value', 'success'],
        'inactive' => ['Inactive', 'secondary'],
    ];
}
