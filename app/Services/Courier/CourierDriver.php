<?php

namespace App\Services\Courier;

use App\Models\CourierAccount;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Base courier driver. mode "demo" simulates the courier (no API call) so the whole flow can be tested
 * before the merchant has API keys.
 */
abstract class CourierDriver
{
    public function __construct(protected CourierAccount $account) {}

    /** @return array{consignment_id:?string, tracking_code:?string, status:string, delivery_charge:?float, payload:array} */
    abstract protected function createLive(Order $order, array $opt): array;

    /** @return array{status:string, payload:array} */
    abstract protected function trackLive(Shipment $s): array;

    /** Balance / connection check. Returns a human message; throws on failure. */
    abstract protected function testLive(): string;

    /** Map a raw courier status to our group. */
    public static function group(string $raw): string
    {
        $s = strtolower(str_replace([' ', '-'], '_', $raw));

        return match (true) {
            str_contains($s, 'partial') && ! str_contains($s, 'approval') => 'delivered',
            str_contains($s, 'cancel') && ! str_contains($s, 'pending') => 'cancelled',
            str_contains($s, 'return') || str_contains($s, 'paid_return') => 'returned',
            str_contains($s, 'delivered') && ! str_contains($s, 'pending') && ! str_contains($s, 'approval') => 'delivered',
            str_contains($s, 'transit') || str_contains($s, 'picked') || str_contains($s, 'on_the_way') || str_contains($s, 'in_review')
                || str_contains($s, 'hold') || str_contains($s, 'ready_for_delivery') || str_contains($s, 'delivery_in_progress')
                || str_contains($s, 'assigned_for_delivery') || str_contains($s, 'at_the_sorting') || str_contains($s, 'received_at') || str_contains($s, 'approval') => 'in_transit',
            default => 'pending',
        };
    }

    public function create(Order $order, array $opt): array
    {
        if ($this->account->mode === 'demo') {
            return ['consignment_id' => (string) random_int(10000000, 99999999), 'tracking_code' => strtoupper(Str::random(10)),
                'status' => 'pending', 'delivery_charge' => 60.0, 'payload' => ['demo' => true]];
        }

        return $this->createLive($order, $opt);
    }

    public function track(Shipment $s): array
    {
        if ($this->account->mode === 'demo') {
            // demo: advance one step each sync
            $next = ['pending' => 'in_transit', 'in_transit' => 'delivered'][$s->status_group] ?? $s->status;

            return ['status' => $next, 'payload' => ['demo' => true]];
        }

        return $this->trackLive($s);
    }

    public function test(): string
    {
        return $this->account->mode === 'demo' ? 'Demo mode — কোনো API কল হবে না।' : $this->testLive();
    }

    protected function need(string ...$keys): void
    {
        foreach ($keys as $k) {
            if (! $this->account->cred($k)) {
                throw new RuntimeException($this->account->label().": '{$k}' সেট করা নেই (Settings → Courier)।");
            }
        }
    }

    protected function http()
    {
        return Http::timeout(25)->acceptJson()->asJson();
    }

    protected function fail(string $what, $response): never
    {
        $body = $response->json();
        $msg = is_array($body) ? ($body['message'] ?? $body['error'] ?? json_encode($body['errors'] ?? $body, JSON_UNESCAPED_UNICODE)) : $response->body();
        throw new RuntimeException($this->account->label()." {$what} failed (HTTP {$response->status()}): ".Str::limit((string) (is_array($msg) ? json_encode($msg, JSON_UNESCAPED_UNICODE) : $msg), 250));
    }

    protected static function phone(string $p): string
    {
        $d = preg_replace('/\D+/', '', $p);

        return str_starts_with($d, '880') ? substr($d, 2) : $d;
    }
}
