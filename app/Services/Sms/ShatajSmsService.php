<?php

namespace App\Services\Sms;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Shataj SMS Gateway (sms.shataj.com) — our own SMS gateway.
 *
 * API (form POST, see "PHP Integration" on sms.shataj.com):
 *   POST {server}/services/send.php            key, number, message, devices, type=sms, prioritize, schedule
 *   POST {server}/services/send.php            key                         -> data.credits (balance)
 *   POST {server}/services/get-devices.php     key                         -> data.devices
 *   POST {server}/services/read-messages.php   key, id | groupId           -> data.messages
 *   Response: {"success": true, "data": {...}} or {"success": false, "error": {"message": "..."}}
 *
 * Credentials: Super Admin panel (Setting table, key encrypted) first, then .env.
 */
class ShatajSmsService
{
    protected ?array $cfg = null;

    public function __construct(protected array $override = []) {}

    public function config(): array
    {
        if ($this->cfg !== null) {
            return $this->cfg;
        }
        $env = config('shataj.sms');
        $key = Setting::get('sms_api_key');
        if ($key) {
            try {
                $key = Crypt::decryptString($key);
            } catch (Throwable) {
                $key = null;   // APP_KEY changed -> key must be saved again
            }
        }

        return $this->cfg = array_merge($env, array_filter([
            'driver' => Setting::get('sms_driver'),
            'server' => ($s = Setting::get('sms_server')) ? rtrim($s, '/') : null,
            'api_key' => $key,
            'device' => Setting::get('sms_device'),
        ], fn ($v) => $v !== null && $v !== ''), $this->override);
    }

    public function isLive(): bool
    {
        return $this->config()['driver'] === 'shataj' && filled($this->config()['api_key']);
    }

    /** Encrypt an API key before saving it in the settings table. */
    public static function storeKey(string $key): string
    {
        return Crypt::encryptString(trim($key));
    }

    /** 017XXXXXXXX / 88017... / +88017... -> +88017XXXXXXXX */
    public function normalize(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile);
        $cc = (string) $this->config()['country_code'];

        if ($cc !== '' && str_starts_with($digits, '0')) {
            $digits = $cc.$digits;
        }

        return '+'.$digits;
    }

    /**
     * Send one SMS. $priority = true for OTP / password reset (sent before queued bulk messages).
     */
    public function send(string $to, string $message, bool $priority = false): bool
    {
        $to = $this->normalize($to);
        $cfg = $this->config();

        if ($cfg['driver'] !== 'shataj') {
            Log::info("[ShatajSMS:log] to {$to}: {$message}");

            return true;
        }
        if (! filled($cfg['api_key'])) {
            Log::warning('[ShatajSMS] API key not set — SMS not sent', ['to' => $to]);

            return false;
        }

        try {
            $data = $this->request('/services/send.php', [
                'number' => $to,
                'message' => $message,
                'devices' => (string) ($cfg['device'] ?? '0') ?: '0',
                'type' => 'sms',
                'prioritize' => $priority ? 1 : 0,
            ]);
        } catch (Throwable $e) {
            Log::warning('[ShatajSMS] send failed: '.$e->getMessage(), ['to' => $to]);

            return false;
        }

        $msg = $data['messages'][0] ?? null;
        if (! $msg || strtolower((string) ($msg['status'] ?? '')) === 'failed') {
            Log::warning('[ShatajSMS] message not accepted', ['to' => $to, 'reply' => $msg]);

            return false;
        }

        return true;
    }

    /** Remaining credits ("Unlimited" when the account has no limit). */
    public function balance(): string
    {
        $credits = $this->request('/services/send.php', [])['credits'] ?? null;

        return $credits === null ? 'Unlimited' : (string) $credits;
    }

    /** Enabled sender devices: [['ID' => 1, 'model' => ..., 'sims' => ...], ...] */
    public function devices(): array
    {
        return $this->request('/services/get-devices.php', [])['devices'] ?? [];
    }

    /** Delivery status of a sent message. */
    public function message(int $id): ?array
    {
        return $this->request('/services/read-messages.php', ['id' => $id])['messages'][0] ?? null;
    }

    /**
     * @throws RuntimeException
     */
    protected function request(string $path, array $post): array
    {
        $cfg = $this->config();
        if (! filled($cfg['api_key'])) {
            throw new RuntimeException('Shataj SMS API key is not set.');
        }

        $response = Http::timeout($cfg['timeout'] ?? 20)
            ->asForm()
            ->acceptJson()
            ->post($cfg['server'].$path, $post + ['key' => $cfg['api_key']]);

        if ($response->status() !== 200) {
            throw new RuntimeException('HTTP Error Code : '.$response->status());
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new RuntimeException(trim($response->body()) ?: 'Missing data in request.');
        }
        if (empty($json['success'])) {
            throw new RuntimeException($json['error']['message'] ?? 'Shataj SMS rejected the request.');
        }

        return $json['data'] ?? [];
    }
}
