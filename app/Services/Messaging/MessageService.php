<?php

namespace App\Services\Messaging;

use App\Models\Branch;
use App\Models\MessageLog;
use App\Services\Sms\ShatajSmsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Outgoing SMS / email to customers & managers with plan quota + delivery log.
 * SMS goes through the platform's Shataj SMS gateway; usage is counted per branch per month.
 */
class MessageService
{
    protected static ?bool $ready = null;

    public function __construct(protected ShatajSmsService $sms) {}

    public static function ready(): bool
    {
        return static::$ready ??= Schema::hasTable('message_logs');
    }

    /** SMS parts: 160 chars (GSM) or 70 chars (Unicode / Bangla) per part. */
    public static function segments(string $text): int
    {
        $unicode = (bool) preg_match('/[^\x00-\x7F]/', $text);
        $len = mb_strlen($text);
        if ($unicode) {
            return $len <= 70 ? 1 : (int) ceil($len / 67);
        }

        return $len <= 160 ? 1 : (int) ceil($len / 153);
    }

    public function smsUsedThisMonth(Branch $branch): int
    {
        if (! static::ready()) {
            return 0;
        }

        return (int) MessageLog::where('branch_id', $branch->id)->where('channel', 'sms')->where('status', 'sent')
            ->where('created_at', '>=', now()->startOfMonth())->sum('segments');
    }

    /** Remaining SMS parts this month; null = unlimited. */
    public function smsRemaining(Branch $branch): ?int
    {
        $limit = $branch->planLimit('sms_month');
        if ($limit < 0) {
            return null;
        }

        return max(0, $limit - $this->smsUsedThisMonth($branch));
    }

    public function sms(?Branch $branch, string $to, string $text, string $context = 'general', array $meta = []): bool
    {
        $text = trim($text);
        $to = preg_replace('/[^\d+]/', '', $to);
        if ($to === '' || $text === '') {
            return false;
        }
        $segments = static::segments($text);

        if ($branch) {
            if (! $branch->hasFeature('sms')) {
                return $this->log($branch, 'sms', $to, null, $text, 'skipped', $context, $meta, $segments, 'Plan has no SMS feature');
            }
            $left = $this->smsRemaining($branch);
            if ($left !== null && $left < $segments) {
                return $this->log($branch, 'sms', $to, null, $text, 'skipped', $context, $meta, $segments, 'Monthly SMS quota exhausted');
            }
        }

        try {
            $ok = $this->sms->send($to, $text);
        } catch (Throwable $e) {
            return $this->log($branch, 'sms', $to, null, $text, 'failed', $context, $meta, $segments, mb_substr($e->getMessage(), 0, 250));
        }

        return $this->log($branch, 'sms', $to, null, $text, $ok ? 'sent' : 'failed', $context, $meta, $segments, $ok ? null : 'Gateway rejected');
    }

    public function email(?Branch $branch, string $to, string $subject, string $text, string $context = 'general', array $meta = []): bool
    {
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        try {
            $from = $branch ? ($branch->store_name ?: $branch->name) : null;
            Mail::raw($text, function ($m) use ($to, $subject, $from, $branch) {
                $m->to($to)->subject($subject);
                if ($from) {
                    $m->from(config('mail.from.address'), $from);
                }
                if ($branch?->email) {
                    $m->replyTo($branch->email, $from);
                }
            });
        } catch (Throwable $e) {
            return $this->log($branch, 'email', $to, $subject, $text, 'failed', $context, $meta, 1, mb_substr($e->getMessage(), 0, 250));
        }

        return $this->log($branch, 'email', $to, $subject, $text, 'sent', $context, $meta, 1);
    }

    /** Replace {placeholders}. Unknown placeholders are removed. */
    public static function render(string $template, array $vars): string
    {
        $out = preg_replace_callback('/\{(\w+)\}/', fn ($m) => (string) ($vars[$m[1]] ?? ''), $template);

        return trim(preg_replace('/[ \t]{2,}/', ' ', $out));
    }

    protected function log(?Branch $branch, string $channel, string $to, ?string $subject, string $body, string $status, string $context, array $meta, int $segments, ?string $error = null): bool
    {
        if (static::ready()) {
            try {
                MessageLog::create([
                    'branch_id' => $branch?->id, 'channel' => $channel, 'to' => mb_substr($to, 0, 150), 'subject' => $subject ? mb_substr($subject, 0, 250) : null,
                    'body' => $body, 'status' => $status, 'context' => mb_substr($context, 0, 40), 'segments' => $segments, 'error' => $error,
                    'campaign_id' => $meta['campaign_id'] ?? null, 'order_id' => $meta['order_id'] ?? null,
                ]);
            } catch (Throwable) {
                // logging must never break the caller
            }
        }

        return $status === 'sent';
    }
}
