<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ReturnRequest extends Model
{
    public const TYPES = ['return' => 'Return & Refund', 'replacement' => 'Replacement', 'exchange' => 'Exchange'];

    public const REASONS = [
        'damaged' => 'পণ্য ক্ষতিগ্রস্ত / ভাঙা',
        'wrong_item' => 'ভুল পণ্য পাঠানো হয়েছে',
        'size_issue' => 'সাইজ মেলেনি',
        'quality' => 'মান আশানুরূপ নয়',
        'not_as_described' => 'বর্ণনার সাথে মেলেনি',
        'other' => 'অন্যান্য',
    ];

    /** status => [label, color] — workflow order */
    public const STATUSES = [
        'requested' => ['Requested', 'warning'],
        'approved' => ['Approved', 'info'],
        'pickup' => ['Pickup Scheduled', 'primary'],
        'received' => ['Received', 'primary'],
        'inspected' => ['Inspected', 'dark'],
        'refunded' => ['Refunded', 'success'],
        'replaced' => ['Replaced', 'success'],
        'exchanged' => ['Exchanged', 'success'],
        'rejected' => ['Rejected', 'danger'],
    ];

    protected $fillable = [
        'branch_id', 'order_id', 'customer_id', 'number', 'type', 'reason', 'details', 'items', 'status',
        'refund_amount', 'refund_method', 'restock', 'admin_note', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['items' => 'array', 'refund_amount' => 'decimal:2', 'restock' => 'boolean', 'resolved_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public static function generateNumber(): string
    {
        do {
            $n = 'RET-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while (static::where('number', $n)->exists());

        return $n;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['refunded', 'replaced', 'exchanged', 'rejected'], true);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status][0] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status][1] ?? 'secondary';
    }

    /** Value of the returned items. */
    public function itemsValue(): float
    {
        $orderItems = $this->order->items->keyBy('id');

        return (float) collect($this->items)->sum(fn ($i) => ($orderItems[$i['order_item_id']]->price ?? 0) * ($i['quantity'] ?? 0));
    }
}
