<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    public const GROUPS = [
        'pending' => ['Booked / Pickup pending', 'secondary'],
        'in_transit' => ['In transit', 'primary'],
        'delivered' => ['Delivered', 'success'],
        'returned' => ['Returned', 'warning'],
        'cancelled' => ['Cancelled', 'danger'],
    ];

    protected $fillable = ['branch_id', 'order_id', 'courier', 'courier_name', 'consignment_id', 'tracking_code', 'tracking_url', 'status', 'status_group',
        'cod_amount', 'delivery_charge', 'weight', 'note', 'payload', 'last_synced_at', 'delivered_at', 'actor_name'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'cod_amount' => 'decimal:2', 'delivery_charge' => 'decimal:2', 'weight' => 'decimal:2',
            'last_synced_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status_group, ['pending', 'in_transit'], true);
    }

    public function courierLabel(): string
    {
        return $this->courier === 'manual' ? ($this->courier_name ?: 'Manual') : (CourierAccount::COURIERS[$this->courier][0] ?? $this->courier);
    }

    /** Public tracking link for the customer. */
    public function publicTrackingUrl(): ?string
    {
        if ($this->tracking_url) {
            return $this->tracking_url;
        }

        return match ($this->courier) {
            'steadfast' => $this->tracking_code ? 'https://steadfast.com.bd/t/'.$this->tracking_code : null,
            'pathao' => $this->consignment_id ? 'https://merchant.pathao.com/tracking?consignment_id='.$this->consignment_id.'&phone='.($this->order?->customer_phone) : null,
            'redx' => $this->tracking_code ? 'https://redx.com.bd/track-global-parcel/?trackingId='.$this->tracking_code : null,
            default => null,
        };
    }
}
