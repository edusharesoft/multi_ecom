<?php

namespace App\Services\Payment;

use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * ShatajPay (pay.shataj.com) — our own payment gateway.
 *
 * Official flow (github.com/mspmedisoft/shataj-payment-gateway-integration):
 *   1. POST {base}/api/v1/user/payment-system
 *        headers: username, password, appkey, appsecret, sandbox, gatewayid
 *        body:    amount, currency, name, email, mobile, Address, opt_value_a, opt_value_b, transactionId, orderId
 *   2. Response {status: true, status_code: 200, redirect_url} -> send the customer to redirect_url
 *   3. ShatajPay redirects the customer back to the callback URL set in the merchant panel:
 *        ?status=Complete|Failed&orderId=...
 *
 * Multi-tenant: a store order uses the branch's OWN ShatajPay account when the branch has
 * saved & activated one; otherwise the platform account. SaaS invoices always use the platform account.
 */
class ShatajPayService
{
    /** Is the platform in built-in test mode (no API calls)? */
    public function isFake(): bool
    {
        return $this->platformConfig()['mode'] === 'fake';
    }

    /** Platform credentials: DB row (Super Admin panel) first, then .env. */
    public function platformConfig(): array
    {
        $base = config('shataj.pay');
        $row = PaymentGateway::platform();

        if ($row) {
            return $this->fromRow($row, $base, $row->mode ?: 'fake') + ['source' => 'platform', 'active' => $row->isUsable() || $row->mode === 'fake'];
        }

        return $base + ['source' => 'env', 'active' => $base['mode'] === 'fake' || filled($base['app_key'])];
    }

    /**
     * Credentials to use for a payable.
     *
     * @return array|null null = ShatajPay not configured
     */
    public function configFor(Order|Invoice $payable): ?array
    {
        if ($payable instanceof Order && ($own = $this->branchGateway($payable->branch_id))) {
            return $own;
        }

        $cfg = $this->platformConfig();

        return $cfg['active'] ? $cfg : null;
    }

    /** The branch's own active ShatajPay account, or null. */
    public function branchGateway(int $branchId): ?array
    {
        $row = PaymentGateway::forBranch($branchId);
        if (! $row || ! $row->isUsable()) {
            return null;
        }

        return $this->fromRow($row, config('shataj.pay'), $row->mode === 'sandbox' ? 'sandbox' : 'live') + ['source' => 'branch', 'active' => true];
    }

    /** Can this branch take ShatajPay payments right now? */
    public function availableFor(Branch $branch): bool
    {
        return $this->branchGateway($branch->id) !== null || $this->platformConfig()['active'];
    }

    /** Create a payment for a customer Order or a SaaS subscription Invoice and return the gateway URL. */
    public function initiate(Order|Invoice $payable): string
    {
        $cfg = $this->configFor($payable);
        if (! $cfg) {
            throw new RuntimeException('ShatajPay is not configured.');
        }

        $isInvoice = $payable instanceof Invoice;
        $branch = $payable->branch;
        $orderId = (string) Str::uuid();          // sent as orderId, comes back on the callback
        $transactionId = (string) Str::uuid();    // sent as transactionId

        $payment = $payable->payments()->create([
            'gateway' => 'shatajpay',
            'reference' => $orderId,
            'transaction_id' => $transactionId,
            'amount' => $isInvoice ? $payable->total : $payable->payable,
            'currency' => $cfg['currency'] ?: 'BDT',
            'status' => 'initiated',
            'payload' => ['account' => $cfg['source'], 'mode' => $cfg['mode'], 'ref' => $isInvoice ? $payable->number : $payable->order_number],
        ]);

        if ($cfg['mode'] === 'fake') {
            return URL::temporarySignedRoute('shatajpay.fake', now()->addMinutes(30), ['reference' => $payment->reference]);
        }

        $mobile = $isInvoice ? $branch->mobile : $payable->customer_phone;
        $email = $isInvoice ? $branch->email : $payable->customer_email;

        $body = [
            'amount' => round((float) $payment->amount, 2),
            'currency' => $payment->currency,
            'name' => Str::limit($isInvoice ? ($branch->name ?: $branch->store_name) : $payable->customer_name, 100, ''),
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : ($branch->email ?: (Setting::get('site_email') ?: 'noreply@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'shataj.com'))),
            'mobile' => $this->mobile((string) $mobile),
            'Address' => Str::limit((string) ($isInvoice ? $branch->address : ($payable->shipping_address ?? $branch->address)), 250, ''),
            'opt_value_a' => $isInvoice ? 'invoice' : 'order',
            'opt_value_b' => $isInvoice ? $payable->number : $payable->order_number,
            'transactionId' => $transactionId,
            'orderId' => $orderId,
        ];

        try {
            $response = Http::timeout($cfg['timeout'] ?? 20)
                ->acceptJson()
                ->withHeaders([
                    'username' => (string) $cfg['username'],
                    'password' => (string) $cfg['password'],
                    'appkey' => (string) $cfg['app_key'],
                    'appsecret' => (string) $cfg['app_secret'],
                    // as in the official sample: sandbox => 0, production => 1
                    'sandbox' => $cfg['mode'] === 'sandbox' ? '0' : '1',
                    'gatewayid' => (string) $cfg['gateway_id'],
                ])
                ->post($cfg['base_url'].$cfg['endpoint'], $body);
        } catch (Throwable $e) {
            $payment->update(['status' => 'failed', 'payload' => $payment->payload + ['error' => $e->getMessage()]]);
            throw new RuntimeException('ShatajPay connection failed: '.$e->getMessage(), 0, $e);
        }

        $json = $response->json() ?? [];
        $payment->update(['payload' => $payment->payload + ['init' => $this->safe($json, $response->status())]]);

        $ok = $response->successful() && ($json['status'] ?? null) === true && (int) ($json['status_code'] ?? 0) === 200;
        $url = $json['redirect_url'] ?? null;

        if (! $ok || ! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            $payment->update(['status' => 'failed']);
            Log::warning('[ShatajPay] init rejected', ['http' => $response->status(), 'body' => Str::limit($response->body(), 500), 'ref' => $body['opt_value_b']]);
            throw new RuntimeException($json['message'] ?? 'ShatajPay did not return a redirect URL.');
        }

        $payment->update(['status' => 'pending']);

        return $url;
    }

    /**
     * Read the callback (?status=Complete|Failed&orderId=...).
     *
     * @return array{payment: ?Payment, result: string, transaction_id: ?string, message: string}
     *               result = success | failed | cancelled
     */
    public function readCallback(Request $request): array
    {
        $orderId = (string) ($request->input('orderId') ?? $request->input('order_id') ?? $request->input('orderid') ?? $request->input('reference', ''));
        $txn = (string) ($request->input('transactionId') ?? $request->input('transaction_id') ?? '');
        $status = strtolower(trim((string) $request->input('status', '')));

        $payment = null;
        if ($orderId !== '') {
            $payment = Payment::where('gateway', 'shatajpay')->where('reference', $orderId)->first();
        }
        if (! $payment && $txn !== '') {
            $payment = Payment::where('gateway', 'shatajpay')->where('transaction_id', $txn)->first();
        }
        if (! $payment) {
            return ['payment' => null, 'result' => 'failed', 'transaction_id' => null, 'message' => 'Payment not found.'];
        }

        $cfg = config('shataj.pay');
        $result = in_array($status, $cfg['success_values'], true) ? 'success'
            : (in_array($status, $cfg['cancel_values'], true) ? 'cancelled' : 'failed');

        // Test payments can only be completed from the signed test page.
        if (data_get($payment->payload, 'mode') === 'fake' && $result === 'success' && ! $request->hasValidSignature()) {
            $result = 'failed';
        }

        // Old pending payments must not be completed by a late / replayed callback.
        if ($result === 'success' && $payment->created_at && $payment->created_at->lt(now()->subHours($cfg['callback_valid_hours']))) {
            Log::warning('[ShatajPay] late success callback ignored', ['payment' => $payment->id]);

            return ['payment' => $payment, 'result' => 'failed', 'transaction_id' => $payment->transaction_id, 'message' => 'Payment session expired. Please contact the store.'];
        }

        $payment->update(['payload' => ($payment->payload ?? []) + ['callback' => $this->safe($request->except(['signature', 'expires']), 200)]]);

        return [
            'payment' => $payment,
            'result' => $result,
            'transaction_id' => $payment->transaction_id ?: $txn ?: null,
            'message' => match ($result) {
                'success' => 'Payment completed.',
                'cancelled' => 'Payment cancelled.',
                default => 'Payment failed ('.($status ?: 'unknown').').',
            },
        ];
    }

    /** Try the credentials without taking money: a 1 BDT init against the API. */
    public function testConnection(array $cfg): array
    {
        try {
            $response = Http::timeout(15)->acceptJson()->withHeaders([
                'username' => (string) $cfg['username'], 'password' => (string) $cfg['password'],
                'appkey' => (string) $cfg['app_key'], 'appsecret' => (string) $cfg['app_secret'],
                'sandbox' => $cfg['mode'] === 'sandbox' ? '0' : '1', 'gatewayid' => (string) $cfg['gateway_id'],
            ])->post($cfg['base_url'].$cfg['endpoint'], [
                'amount' => 1, 'currency' => 'BDT', 'name' => 'Connection Test', 'email' => 'test@shataj.com',
                'mobile' => '01700000000', 'Address' => 'Dhaka', 'opt_value_a' => 'test', 'opt_value_b' => 'test',
                'transactionId' => (string) Str::uuid(), 'orderId' => (string) Str::uuid(),
            ]);
        } catch (Throwable $e) {
            return [false, 'Connection failed: '.$e->getMessage()];
        }

        $json = $response->json() ?? [];
        $ok = $response->successful() && ($json['status'] ?? null) === true && ! empty($json['redirect_url']);

        return [$ok, $ok ? 'Connected ✓ ShatajPay accepted the credentials.' : 'ShatajPay replied (HTTP '.$response->status().'): '.($json['message'] ?? Str::limit($response->body(), 200))];
    }

    public function fromRow(PaymentGateway $row, array $base, string $mode): array
    {
        return array_merge($base, [
            'mode' => $mode,
            'base_url' => rtrim($row->base_url ?: $base['base_url'], '/'),
            'username' => $row->app_username,
            'password' => $row->app_password,
            'app_key' => $row->app_key,
            'app_secret' => $row->app_secret,
            'gateway_id' => $row->gateway_id,
        ]);
    }

    protected function mobile(string $mobile): string
    {
        $d = preg_replace('/\D+/', '', $mobile);

        return str_starts_with($d, '880') ? substr($d, 2) : ($d ?: '01700000000');
    }

    /** Keep gateway responses small and free of secrets before storing them. */
    protected function safe(array $data, int $http): array
    {
        unset($data['password'], $data['appsecret'], $data['appkey']);

        return ['http' => $http, 'data' => array_map(fn ($v) => is_scalar($v) ? Str::limit((string) $v, 300) : '[…]', $data), 'at' => now()->toDateTimeString()];
    }
}
