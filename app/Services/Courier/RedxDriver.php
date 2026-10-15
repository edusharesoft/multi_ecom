<?php

namespace App\Services\Courier;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\Cache;

/** RedX open API — header "API-ACCESS-TOKEN: Bearer <token>". Needs a delivery area (id + name). */
class RedxDriver extends CourierDriver
{
    protected function base(): string
    {
        return rtrim($this->account->cred('base_url') ?: ($this->account->mode === 'sandbox' ? 'https://sandbox.redx.com.bd/v1.0.0-beta' : 'https://openapi.redx.com.bd/v1.0.0-beta'), '/');
    }

    protected function client()
    {
        $this->need('access_token');

        return $this->http()->withHeaders(['API-ACCESS-TOKEN' => 'Bearer '.$this->account->cred('access_token')]);
    }

    /** [[id, name, district]] cached for a day */
    public function areas(): array
    {
        if ($this->account->mode === 'demo') {
            return [[1, 'Mirpur', 'Dhaka'], [2, 'Dhanmondi', 'Dhaka'], [3, 'Barishal Sadar', 'Barishal']];
        }

        return Cache::remember('redx_areas:'.$this->account->id, now()->addDay(), function () {
            $r = $this->client()->get($this->base().'/areas');
            if (! $r->successful()) {
                $this->fail('area list', $r);
            }

            return collect($r->json('areas', []))->map(fn ($a) => [(int) $a['id'], (string) $a['name'], (string) ($a['district_name'] ?? '')])->values()->all();
        });
    }

    protected function createLive(Order $order, array $opt): array
    {
        if (empty($opt['area_id'])) {
            throw new \RuntimeException('RedX: ডেলিভারি এরিয়া নির্বাচন করুন।');
        }
        $area = collect($this->areas())->firstWhere(0, (int) $opt['area_id']);
        $r = $this->client()->post($this->base().'/parcel', [
            'customer_name' => mb_substr($order->customer_name, 0, 100),
            'customer_phone' => self::phone($order->customer_phone),
            'delivery_area' => $area[1] ?? (string) ($opt['area_name'] ?? ''),
            'delivery_area_id' => (int) $opt['area_id'],
            'customer_address' => mb_substr($opt['address'] ?? $order->shipping_address, 0, 250),
            'merchant_invoice_id' => $order->order_number,
            'cash_collection_amount' => (string) round((float) $opt['cod_amount']),
            'parcel_weight' => (int) round(max(0.1, (float) ($opt['weight'] ?? 0.5)) * 1000),
            'instruction' => (string) ($opt['note'] ?? ''),
            'value' => (int) round((float) $order->total),
        ]);
        if (! $r->successful() || ! $r->json('tracking_id')) {
            $this->fail('booking', $r);
        }

        return ['consignment_id' => (string) $r->json('tracking_id'), 'tracking_code' => (string) $r->json('tracking_id'),
            'status' => 'pickup-pending', 'delivery_charge' => null, 'payload' => $r->json()];
    }

    protected function trackLive(Shipment $s): array
    {
        $r = $this->client()->get($this->base().'/parcel/info/'.$s->tracking_code);
        if (! $r->successful()) {
            $this->fail('tracking', $r);
        }

        return ['status' => (string) ($r->json('parcel.status') ?? $s->status), 'payload' => $r->json()];
    }

    protected function testLive(): string
    {
        return 'Connected ✓ — '.count($this->areas()).' delivery areas';
    }
}
