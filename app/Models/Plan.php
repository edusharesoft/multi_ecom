<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    public const CYCLES = ['monthly' => 'Monthly', 'yearly' => 'Yearly', 'lifetime' => 'Lifetime'];

    /** limit key => label. -1 means unlimited */
    public const LIMITS = [
        'max_products' => 'Products',
        'max_orders_month' => 'Orders / month',
        'max_customers' => 'Customers',
        'max_staff' => 'Staff users',
        'max_warehouses' => 'Warehouses / Branches',
        'storage_mb' => 'Storage (MB)',
        'monthly_sales_limit' => 'Monthly sales (৳)',
        'sms_month' => 'SMS / month',
        'ai_requests_month' => 'AI requests / month',
    ];

    public const FEATURES = [
        'pos' => 'POS',
        'inventory' => 'Inventory',
        'accounting' => 'Accounting, Expenses & P&L',
        'courier' => 'Courier API (Steadfast, Pathao, RedX)',
        'crm' => 'CRM',
        'coupon' => 'Coupons & Promotions',
        'reports' => 'Basic Reports',
        'advanced_reports' => 'Advanced Reports',
        'api' => 'REST API',
        'custom_domain' => 'Custom Domain',
        'blog' => 'Blog',
        'hrm' => 'HRM & Payroll',
        'ai' => 'AI Assistant (copywriting, insights, chatbot)',
        'whatsapp' => 'WhatsApp',
        'sms' => 'SMS',
        'email_marketing' => 'Email Marketing',
        'advanced_marketing' => 'Advanced Marketing',
    ];

    protected $fillable = [
        'name', 'slug', 'description', 'price_monthly', 'price_yearly', 'price_lifetime', 'trial_days',
        'commission_percent', 'is_free', 'is_active', 'is_featured', 'branch_id', 'limits', 'features', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'price_lifetime' => 'decimal:2',
            'commission_percent' => 'decimal:2',
            'is_free' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'limits' => 'array',
            'features' => 'array',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public static function free(): self
    {
        // Fallback keeps the app working even before `db:seed --class=SaasSeeder` has been run
        return static::where('is_free', true)->whereNull('branch_id')->orderBy('sort')->first()
            ?? new static([
                'name' => 'Free', 'slug' => 'free', 'is_free' => true, 'price_monthly' => 0, 'price_yearly' => 0,
                'commission_percent' => 0,
                'limits' => ['max_products' => 10, 'max_orders_month' => 30, 'max_customers' => 100, 'max_staff' => 0, 'max_warehouses' => 1, 'storage_mb' => 100, 'monthly_sales_limit' => 50000],
                'features' => ['reports' => true],
            ]);
    }

    /** Plans a given branch can choose (public + its own custom plans). */
    public function scopeAvailableFor($query, Branch $branch)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('branch_id')->orWhere('branch_id', $branch->id))
            ->orderBy('sort');
    }

    public function limit(string $key): int
    {
        return (int) ($this->limits[$key] ?? 0);
    }

    public function hasFeature(string $key): bool
    {
        return (bool) ($this->features[$key] ?? false);
    }

    public function priceFor(string $cycle): float
    {
        if ($this->is_free) {
            return 0.0;
        }

        return (float) match ($cycle) {
            'yearly' => $this->price_yearly,
            'lifetime' => $this->price_lifetime ?? 0,
            default => $this->price_monthly,
        };
    }

    public function offersCycle(string $cycle): bool
    {
        return $this->is_free || $cycle !== 'lifetime' || $this->price_lifetime !== null;
    }

    public function yearlySavingPercent(): ?int
    {
        if ($this->price_monthly > 0 && $this->price_yearly > 0) {
            $save = 1 - $this->price_yearly / ($this->price_monthly * 12);

            return $save > 0 ? (int) round($save * 100) : null;
        }

        return null;
    }

    public static function limitLabel(int $value, string $key = ''): string
    {
        if ($value < 0) {
            return 'Unlimited';
        }
        if ($key === 'storage_mb' && $value >= 1024) {
            return round($value / 1024, 1).' GB';
        }

        return number_format($value).($key === 'storage_mb' ? ' MB' : '');
    }
}
