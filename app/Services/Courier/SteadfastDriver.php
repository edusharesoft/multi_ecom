<?php

namespace App\Services\Courier;

use App\Models\Order;
use App\Models\Shipment;

/** Steadfast Courier — https://portal.packzy.com/api/v1  (headers Api-Key / Secret-Key) */
class SteadfastDriver extends CourierDriver
{
    protected function base(): string
    {
        return rtrim($this->account->cred('base_url') ?: 'https://portal.packzy.com/api/v1', '/');
    }

    protected function client()
    {
        $this->need('api_key', 'secret_key');

        return $this->http()->withHeaders(['Api-Key' => $this->account->cred('api_key'), 'Secret-Key' => $this->account->cred('secret_key')]);
    }

    protected function createLive(Order $order, array $opt): array
    {
        $r = $this->client()->post($this->base().'/create_order', array_filter([
            'invoice' => $order->order_number,
            'recipient_name' => mb_substr($order->customer_name, 0, 100),
            'recipient_phone' => self::phone($order->customer_phone),
            'recipient_address' => mb_substr($opt['address'] ?? $order->shipping_address, 0, 250),
            'cod_amount' => round((float) $opt['cod_amount'], 0),
            'note' => $opt['note'] ?? null,
            'item_description' => $opt['description'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));
        $c = $r->json('consignment');
        if (! $r->successful() || ! $c) {
            $this->fail('booking', $r);
        }

        return ['consignment_id' => (string) ($c['consignment_id'] ?? ''), 'tracking_code' => $c['tracking_code'] ?? null,
            'status' => (string) ($c['status'] ?? 'in_review'), 'delivery_charge' => null, 'payload' => $r->json()];
    }

    protected function trackLive(Shipment $s): array
    {
        $r = $s->consignment_id
            ? $this->client()->get($this->base().'/status_by_cid/'.$s->consignment_id)
            : $this->client()->get($this->base().'/status_by_trackingcode/'.$s->tracking_code);
        if (! $r->successful()) {
            $this->fail('tracking', $r);
        }

        return ['status' => (string) ($r->json('delivery_status') ?? $s->status), 'payload' => $r->json()];
    }

    protected function testLive(): string
    {
        $r = $this->client()->get($this->base().'/get_balance');
        if (! $r->successful()) {
            $this->fail('connection', $r);
        }

        return 'Connected ✓ — Balance ৳'.number_format((float) $r->json('current_balance'), 2);
    }

    /** Customer delivery history across Steadfast merchants (fraud check). */
    public function fraudCheck(string $phone): ?array
    {
        if ($this->account->mode === 'demo') {
            return ['total_parcels' => 12, 'total_delivered' => 10, 'total_cancelled' => 2];
        }
        $r = $this->client()->get($this->base().'/fraud_check/'.self::phone($phone));

        return $r->successful() ? $r->json() : null;
    }
}
