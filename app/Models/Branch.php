<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Branch extends Authenticatable
{
    use Notifiable;

    /** Sub-folder names that can never be used as a branch URL. */
    public const RESERVED_SLUGS = [
        'register', 'login', 'logout', 'otp', 'manager', 'admin', 'payment', 'storage',
        'api', 'css', 'js', 'images', 'img', 'assets', 'vendor', 'build', 'subscribe',
        'branches', 'about', 'contact', 'blog', 'help', 'terms', 'privacy', 'up', 'favicon.ico', 'webhooks', 'gateway',
    ];

    protected $fillable = [
        'name', 'mobile', 'email', 'slug', 'address', 'password',
        'store_name', 'status', 'logo', 'banner', 'hero_title', 'hero_subtitle', 'about',
        'store_phone', 'opening_hours', 'map_url', 'facebook_url',
        'bkash_number', 'nagad_number', 'cod_enabled', 'shatajpay_enabled',
        'shipping_charge', 'tax_percent', 'favicon', 'options',
    ];

    /** Branch store options with defaults */
    public const OPTION_DEFAULTS = [
        'review_auto_approve' => true,
        'points_per_100' => 1,          // loyalty points earned per ৳100
        'point_value' => 1,             // ৳ value of 1 point (for display / future redemption)
        'return_days' => 7,
        'high_value_amount' => 20000,   // CRM: total spend for "High Value"
        'invoice_note' => 'ধন্যবাদ! আবার আসবেন।',
        'allow_guest_checkout' => true,
    ];

    protected $hidden = ['password', 'remember_token', 'otp_code', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'mobile_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'otp_sent_at' => 'datetime',
            'suspended_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'trial_used_at' => 'datetime',
            'cod_enabled' => 'boolean',
            'shatajpay_enabled' => 'boolean',
            'shipping_charge' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'options' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** This branch's own ShatajPay merchant account (optional). */
    public function paymentGateway(): HasOne
    {
        return $this->hasOne(PaymentGateway::class)->where('gateway', 'shatajpay');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function option(string $key, $default = null)
    {
        return ($this->options ?? [])[$key] ?? $default ?? (self::OPTION_DEFAULTS[$key] ?? null);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function sliders(): HasMany
    {
        return $this->hasMany(Slider::class)->orderBy('sort');
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /** The default (main) warehouse — created on first use. */
    public function defaultWarehouse(): Warehouse
    {
        return $this->warehouses()->where('is_default', true)->first()
            ?? $this->warehouses()->orderBy('id')->first()
            ?? $this->warehouses()->create(['name' => 'Main Warehouse', 'code' => 'MAIN', 'type' => 'main', 'is_default' => true]);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function courierAccounts(): HasMany
    {
        return $this->hasMany(CourierAccount::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function posSessions(): HasMany
    {
        return $this->hasMany(PosSession::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest();
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /* ---------------- Plan / limits ---------------- */

    /** The plan whose limits apply right now (Free plan when not subscribed / unpaid / expired). */
    public function effectivePlan(): Plan
    {
        return once(function () {
            $sub = $this->subscription;
            if ($sub && $sub->isUsable() && $sub->plan) {
                return $sub->plan;
            }

            return Plan::free();
        });
    }

    public function planLimit(string $key): int
    {
        return $this->effectivePlan()->limit($key);
    }

    public function hasFeature(string $key): bool
    {
        return $this->effectivePlan()->hasFeature($key);
    }

    /** Current usage for a limit key. */
    public function usage(string $key): int|float
    {
        return match ($key) {
            'max_products' => $this->products()->count(),
            'max_staff' => $this->staff()->count(),
            'max_orders_month' => $this->orders()->where('created_at', '>=', now()->startOfMonth())->count(),
            'monthly_sales_limit' => (float) $this->orders()->where('created_at', '>=', now()->startOfMonth())->where('status', '!=', 'cancelled')->sum('total'),
            'storage_mb' => round($this->storage_used / 1048576, 1),
            'max_customers' => $this->customers()->count(),
            'max_warehouses' => $this->warehouses()->count(),
            'sms_month' => app(\App\Services\Messaging\MessageService::class)->smsUsedThisMonth($this),
            'ai_requests_month' => app(\App\Services\Ai\AiService::class)->usedThisMonth($this),
            default => 0,
        };
    }

    /** true when one more item may be added under $key (e.g. max_products). */
    public function withinLimit(string $key, int|float $adding = 1): bool
    {
        $limit = $this->planLimit($key);

        return $limit < 0 || ($this->usage($key) + $adding) <= $limit;
    }

    public function isReadOnly(): bool
    {
        return (bool) $this->subscription?->isReadOnly();
    }

    public function isOnboarded(): bool
    {
        return $this->onboarded_at !== null;
    }

    public function addStorage(int $bytes): void
    {
        $this->forceFill(['storage_used' => max(0, (int) $this->storage_used + $bytes)])->saveQuietly();
    }

    /** Store an upload on the public disk and count it against the plan storage limit. */
    public function storeUpload(\Illuminate\Http\UploadedFile $file, string $dir): string
    {
        $path = $file->store($dir, 'public');
        $this->addStorage((int) $file->getSize());

        return $path;
    }

    /** Delete a stored file and release its storage usage. */
    public function deleteUpload(?string $path): void
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if ($path && $disk->exists($path)) {
            $this->addStorage(-(int) $disk->size($path));
            $disk->delete($path);
        }
    }

    public function hasStorageFor(\Illuminate\Http\UploadedFile ...$files): bool
    {
        $mb = array_sum(array_map(fn ($f) => (int) $f->getSize(), $files)) / 1048576;

        return $this->withinLimit('storage_mb', $mb);
    }

    public function isVerified(): bool
    {
        return $this->mobile_verified_at !== null;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function isOpen(): bool
    {
        return $this->status === 'active' && $this->isVerified() && $this->isOnboarded() && ! $this->isSuspended();
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'active')->whereNotNull('mobile_verified_at')->whereNotNull('onboarded_at')->whereNull('suspended_at');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->store_name ?: $this->name;
    }

    public function getUrlAttribute(): string
    {
        return url($this->slug);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon ? asset('storage/'.$this->favicon) : null;
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/'.$this->logo) : null;
    }

    public function getBannerUrlAttribute(): string
    {
        return $this->banner ? asset('storage/'.$this->banner) : asset('images/banner.svg');
    }

    /** Payment methods this branch accepts, in display order. */
    public function paymentMethods(): array
    {
        $methods = [];
        if ($this->shatajpay_enabled && app(\App\Services\Payment\ShatajPayService::class)->availableFor($this)) {
            $methods['shatajpay'] = 'ShatajPay (Card / Mobile Banking)';
        }
        if ($this->bkash_number) {
            $methods['bkash'] = 'bKash (Send Money)';
        }
        if ($this->nagad_number) {
            $methods['nagad'] = 'Nagad (Send Money)';
        }
        if ($this->cod_enabled) {
            $methods['cod'] = 'Cash on Delivery';
        }

        return $methods;
    }
}
