<?php

namespace App\Services\Courier;

use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\Cache;

/**
 * Pathao Courier merchant API (aladdin). City/zone are optional — Pathao auto-detects them from a full address
 * (street, thana/area, district).
 */
class PathaoDriver extends CourierDriver
{
    protected function base(): string
    {
        return rtrim($this->account->cred('base_url') ?: ($this->account->mode === 'sandbox' ? 'https://courier-api-sandbox.pathao.com' : 'https://api-hermes.pathao.com'), '/');
    }

    protected function token(): string
    {
        $this->need('client_id', 'client_secret', 'username', 'password');
        $key = 'pathao_token:'.$this->account->id.':'.md5($this->account->cred('client_id').$this->account->cred('username').$this->account->mode);
        if ($t = Cache::get($key)) {
            return $t;
        }
        $r = $this->http()->post($this->base().'/aladdin/api/v1/issue-token', [
            'client_id' => $this->account->cred('client_id'), 'client_secret' => $this->account->cred('client_secret'),
            'username' => $this->account->cred('username'), 'password' => $this->account->cred('password'), 'grant_type' => 'password',
        ]);
        if (! $r->successful() || ! $r->json('access_token')) {
            $this->fail('login', $r);
        }
        Cache::put($key, $r->json('access_token'), now()->addSeconds(max(300, (int) $r->json('expires_in', 3600) - 300)));

        return $r->json('access_token');
    }

    protected function client()
    {
        return $this->http()->withToken($this->token());
    }

    protected function createLive(Order $order, array $opt): array
    {
        $this->need('store_id');
        $r = $this->client()->post($this->base().'/aladdin/api/v1/orders', array_filter([
            'store_id' => (int) $this->account->cred('store_id'),
            'merchant_order_id' => $order->order_number,
            'recipient_name' => mb_substr($order->customer_name, 0, 100),
            'recipient_phone' => self::phone($order->customer_phone),
            'recipient_address' => mb_substr($opt['address'] ?? $order->shipping_address, 0, 220),
            'delivery_type' => 48,
            'item_type' => 2,
            'special_instruction' => $opt['note'] ?? null,
            'item_quantity' => max(1, (int) $order->items()->sum('quantity')),
            'item_weight' => max(0.5, min(10, (float) ($opt['weight'] ?? 0.5))),
            'amount_to_collect' => (int) round((float) $opt['cod_amount']),
            'item_description' => $opt['description'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));
        $d = $r->json('data');
        if (! $r->successful() || empty($d['consignment_id'])) {
            $this->fail('booking', $r);
        }

        return ['consignment_id' => (string) $d['consignment_id'], 'tracking_code' => (string) $d['consignment_id'],
            'status' => (string) ($d['order_status'] ?? 'Pending'), 'delivery_charge' => isset($d['delivery_fee']) ? (float) $d['delivery_fee'] : null, 'payload' => $r->json()];
    }

    protected function trackLive(Shipment $s): array
    {
        $r = $this->client()->get($this->base().'/aladdin/api/v1/orders/'.$s->consignment_id.'/info');
        if (! $r->successful()) {
            $this->fail('tracking', $r);
        }

        return ['status' => (string) ($r->json('data.order_status') ?? $s->status), 'payload' => $r->json()];
    }

    protected function testLive(): string
    {
        $r = $this->client()->get($this->base().'/aladdin/api/v1/stores');
        if (! $r->successful()) {
            $this->fail('connection', $r);
        }
        $stores = collect($r->json('data.data', []))->map(fn ($s) => ($s['store_id'] ?? '?').': '.($s['store_name'] ?? ''))->implode(', ');

        return 'Connected ✓ — Stores: '.($stores ?: 'none');
    }
}
