<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['product_id', 'customer_id', 'order_id', 'name', 'rating', 'comment', 'is_verified', 'image', 'is_approved', 'reply', 'replied_at', 'reports'];

    protected function casts(): array
    {
        return ['is_approved' => 'boolean', 'is_verified' => 'boolean', 'replied_at' => 'datetime'];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
