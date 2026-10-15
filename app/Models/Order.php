<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    /** status => [label, color] in workflow order */
    public const STATUS_META = [
        'new' => ['New', 'warning'],
        'pending' => ['Pending', 'warning'],
        'confirmed' => ['Confirmed', 'info'],
        'processing' => ['Processing', 'info'],
        'packed' => ['Packed', 'info'],
        'ready_to_ship' => ['Ready to Ship', 'primary'],
        'shipped' => ['Shipped', 'primary'],
        'on_the_way' => ['On The Way', 'primary'],
        'delivered' => ['Delivered', 'success'],
        'completed' => ['Completed', 'success'],
        'cancelled' => ['Cancelled', 'danger'],
        'returned' => ['Returned', 'dark'],
        'refunded' => ['Refunded', 'dark'],
        'failed' => ['Failed', 'danger'],
    ];

    public const STATUSES = ['new', 'pending', 'confirmed', 'processing', 'packed', 'ready_to_ship', 'shipped', 'on_the_way', 'delivered', 'completed', 'cancelled', 'returned', 'refunded', 'failed'];

    /** Statuses where the stock has gone back to the shelf */
    public const STOCK_RELEASED = ['cancelled', 'failed'];

    /** Customer-facing progress timeline */
    public const TIMELINE = ['new', 'confirmed', 'processing', 'packed', 'shipped', 'on_the_way', 'delivered'];

    public const PAYMENT_STATUSES = ['unpaid', 'pending_verification', 'paid', 'partially_refunded', 'refunded', 'failed'];

    public const METHOD_LABELS = [
        'cod' => 'Cash on Delivery',
        'shatajpay' => 'ShatajPay',
        'bkash' => 'bKash',
        'nagad' => 'Nagad',
        'wallet' => 'Wallet',
        'cash' => 'Cash',
        'card' => 'Card',
        'rocket' => 'Rocket',
    ];

    protected $fillable = [
        'branch_id', 'order_number', 'customer_name', 'customer_phone', 'customer_email',
        'shipping_address', 'note', 'subtotal', 'shipping', 'tax', 'total', 'commission_percent', 'commission_amount',
        'payment_method', 'payment_status', 'status', 'transaction_id', 'sender_number', 'paid_at',
        'customer_id', 'billing_address', 'discount', 'coupon_code', 'wallet_used', 'refunded_amount', 'points_earned', 'points_credited', 'source',
        'warehouse_id', 'pos_session_id', 'tendered', 'change_due', 'staff_name', 'client_ref',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'discount' => 'decimal:2',
            'wallet_used' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'points_credited' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('ymd').'-'.strtoupper(Str::random(6));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function needsOnlinePayment(): bool
    {
        return ! in_array($this->payment_method, ['cod', 'wallet', 'cash', 'card'], true)
            && in_array($this->payment_status, ['unpaid', 'failed'], true)
            && ! in_array($this->status, ['cancelled', 'failed'], true);
    }

    public function markPaid(?string $transactionId = null): void
    {
        $wasPaid = $this->payment_status === 'paid';
        $this->forceFill([
            'payment_status' => 'paid',
            'transaction_id' => $transactionId ?? $this->transaction_id,
            'paid_at' => now(),
            'status' => in_array($this->status, ['new', 'pending'], true) ? 'confirmed' : $this->status,
        ])->save();
        if (! $wasPaid && $this->source !== 'pos') {
            \Illuminate\Support\Facades\DB::afterCommit(fn () => app(\App\Services\Messaging\OrderNotifier::class)->fire($this, 'payment_received'));
        }
    }

    public function shipments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Shipment::class)->latest('id');
    }

    /** Current (latest) shipment. */
    public function shipment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Shipment::class)->latestOfMany();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ReturnRequest::class)->latest();
    }

    public function addHistory(string $status, ?string $note = null, ?string $actor = null): void
    {
        $this->histories()->create(['status' => $status, 'note' => $note, 'actor_name' => $actor ?? \App\Support\Tenancy::actorName()]);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_META[$this->status][0] ?? ucfirst($this->status);
    }

    /** Amount still to be collected (after wallet). */
    public function getPayableAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->wallet_used);
    }

    public function canRequestReturn(): bool
    {
        $days = (int) ($this->branch->option('return_days', 7));

        return in_array($this->status, ['delivered', 'completed'], true)
            && $this->updated_at && $this->updated_at->gt(now()->subDays($days))
            && ! $this->returns()->whereNotIn('status', ['rejected'])->exists();
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHOD_LABELS[$this->payment_method] ?? $this->payment_method;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_META[$this->status][1] ?? 'secondary';
    }

    public function getPaymentColorAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'success',
            'pending_verification' => 'warning',
            'partially_refunded', 'refunded' => 'dark',
            'failed' => 'danger',
            default => 'secondary',
        };
    }
}
