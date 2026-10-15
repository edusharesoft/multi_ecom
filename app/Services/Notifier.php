<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Branch;
use App\Models\Setting;
use App\Services\Sms\ShatajSmsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends a notification through in-app + email + SMS channels.
 * WhatsApp / push channels plug in here in a later phase.
 */
class Notifier
{
    public function __construct(protected ShatajSmsService $sms) {}

    /** Notify a branch owner. */
    public function branch(Branch $branch, string $type, string $title, string $body = '', ?string $url = null, string $level = 'info', array $channels = ['app', 'mail']): void
    {
        if (in_array('app', $channels, true)) {
            AppNotification::create(compact('type', 'title', 'body', 'url', 'level') + ['branch_id' => $branch->id]);
        }
        if (in_array('mail', $channels, true) && $branch->email) {
            $this->mail($branch->email, $title, $body."\n\n".($url ?? ''));
        }
        if (in_array('sms', $channels, true) && Setting::get('sms_notifications')) {
            $this->smsTo($branch->mobile, $title.($body ? ' - '.$body : ''));
        }
    }

    /** Notify the Super Admin inbox. */
    public function admin(string $type, string $title, string $body = '', ?string $url = null, string $level = 'info'): void
    {
        AppNotification::create(compact('type', 'title', 'body', 'url', 'level') + ['branch_id' => null]);
        if ($email = Setting::get('site_email')) {
            $this->mail($email, '[Admin] '.$title, $body."\n\n".($url ?? ''));
        }
    }

    public function mail(string $to, string $subject, string $text): void
    {
        try {
            Mail::raw($text, fn ($m) => $m->to($to)->subject($subject));
        } catch (Throwable $e) {
            Log::warning('[Notifier] mail failed: '.$e->getMessage());
        }
    }

    public function smsTo(string $mobile, string $text): void
    {
        try {
            $this->sms->send($mobile, mb_substr($text, 0, 300));
        } catch (Throwable $e) {
            Log::warning('[Notifier] sms failed: '.$e->getMessage());
        }
    }
}
