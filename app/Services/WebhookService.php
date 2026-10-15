<?php

namespace App\Services;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\Branch;
use App\Models\Order;
use App\Models\WebhookDelivery;
use App\Support\Features;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Outgoing webhooks for API users. Branch options: webhook_url, webhook_secret, webhook_events[].
 * Each POST carries JSON {event, sent_at, data} and header X-MB-Signature: sha256=HMAC(body, secret).
 * Never throws; 5 s timeout; every attempt is logged in webhook_deliveries.
 */
class WebhookService
{
    public const EVENTS = [
        'order.created' => 'New order (online & POS)',
        'order.status_changed' => 'Order status changed',
        'order.paid' => 'Order payment received',
    ];

    public function orderEvent(Order $order, string $event): void
    {
        $order->loadMissing('branch');
        $b = $order->branch;
        if (! $b || ! $b->hasFeature('api') || ! in_array($event, (array) $b->option('webhook_events', []), true)) {
            return;
        }
        $this->send($b, $event, ApiController::order($order->loadMissing(['items', 'shipment']), true));
    }

    public function send(Branch $b, string $event, array $data): ?WebhookDelivery
    {
        $url = (string) $b->option('webhook_url');
        if ($url === '' || ! Features::ready('webhook_deliveries')) {
            return null;
        }
        $body = json_encode(['event' => $event, 'sent_at' => now()->toIso8601String(), 'store' => $b->slug, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $sig = 'sha256='.hash_hmac('sha256', $body, (string) $b->option('webhook_secret'));
        $start = microtime(true);
        try {
            if (! $this->publicHost($url)) {
                throw new \RuntimeException('Blocked: webhook URL must point to a public internet address.');
            }
            $r = Http::timeout(5)->withoutRedirecting()->withHeaders(['X-MB-Event' => $event, 'X-MB-Signature' => $sig, 'User-Agent' => 'MultiBranch-Webhook/1.0'])
                ->withBody($body, 'application/json')->post($url);
            $code = $r->status();
            $ok = $r->successful();
            $resp = Str::limit($r->body(), 480);
        } catch (Throwable $e) {
            $code = null;
            $ok = false;
            $resp = Str::limit($e->getMessage(), 480);
        }

        try {
            return WebhookDelivery::create(['branch_id' => $b->id, 'event' => $event, 'url' => Str::limit($url, 250, ''), 'status_code' => $code,
                'success' => $ok, 'response' => $resp, 'duration_ms' => (int) ((microtime(true) - $start) * 1000)]);
        } catch (Throwable) {
            return null;
        }
    }

    /** SSRF guard: the URL host must resolve only to public (non-private, non-reserved) IPs. */
    protected function publicHost(string $url): bool
    {
        $host = trim((string) parse_url($url, PHP_URL_HOST), '[]');
        if ($host === '') {
            return false;
        }
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return $ips !== [];
    }
}
