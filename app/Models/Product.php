<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    public const TYPES = ['physical' => 'Physical Product', 'digital' => 'Digital Product', 'service' => 'Service'];

    public const UNITS = ['pcs' => 'Piece', 'set' => 'Set', 'pair' => 'Pair', 'kg' => 'Kg', 'g' => 'Gram', 'ltr' => 'Litre', 'm' => 'Meter', 'yard' => 'Yard', 'box' => 'Box', 'dozen' => 'Dozen'];

    protected $fillable = [
        'branch_id', 'category_id', 'brand_id', 'subcategory_id', 'name', 'slug', 'product_type', 'sku', 'product_code', 'barcode', 'unit',
        'description', 'specifications', 'price', 'purchase_price', 'old_price', 'sale_price', 'sale_starts_at', 'sale_ends_at', 'tax_percent',
        'stock', 'min_stock', 'image', 'video_url', 'is_featured', 'is_active', 'meta_title', 'meta_description', 'meta_keywords',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'specifications' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    /* ---------- Pricing (flash sale / discount) ---------- */

    public function isOnSale(): bool
    {
        return $this->sale_price !== null && (float) $this->sale_price < (float) $this->price
            && (! $this->sale_starts_at || $this->sale_starts_at->isPast())
            && (! $this->sale_ends_at || $this->sale_ends_at->isFuture());
    }

    public function isFlashSale(): bool
    {
        return $this->isOnSale() && $this->sale_ends_at !== null;
    }

    /** Price the customer pays now. */
    public function currentPrice(): float
    {
        return $this->isOnSale() ? (float) $this->sale_price : (float) $this->price;
    }

    /** Price shown struck-through (regular price during a sale, else old_price). */
    public function comparePrice(): ?float
    {
        if ($this->isOnSale()) {
            return (float) $this->price;
        }

        return $this->old_price && $this->old_price > $this->price ? (float) $this->old_price : null;
    }

    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price')
            ->where(fn ($q) => $q->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>', now()));
    }

    public function scopeFlashSale($query)
    {
        return $query->onSale()->whereNotNull('sale_ends_at');
    }

    public function scopeDiscounted($query)
    {
        return $query->where(fn ($q) => $q->whereColumn('old_price', '>', 'price')->orWhere(fn ($s) => $s->onSale()));
    }

    public function getYoutubeIdAttribute(): ?string
    {
        if ($this->video_url && preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([\w-]{11})~', $this->video_url, $m)) {
            return $m[1];
        }

        return null;
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->latest();
    }

    public function allReviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): string
    {
        // Use the configured application URL so images also work when the app
        // is deployed behind a subdirectory or a reverse proxy.
        return $this->image
            ? asset('storage/'.ltrim($this->image, '/')).'?v='.($this->updated_at?->timestamp ?? 1)
            : asset('images/placeholder.svg');
    }

    public function getUrlAttribute(): string
    {
        return route('store.product', [$this->branch->slug, $this->slug]);
    }

    public function getDiscountPercentAttribute(): ?int
    {
        $compare = $this->comparePrice();

        return $compare ? (int) round((1 - $this->currentPrice() / $compare) * 100) : null;
    }

    public function getIsNewAttribute(): bool
    {
        return $this->created_at && $this->created_at->gt(now()->subDays(30));
    }

    public function totalStock(): int
    {
        return $this->variants->count() ? (int) $this->variants->sum('stock') : (int) $this->stock;
    }
}
