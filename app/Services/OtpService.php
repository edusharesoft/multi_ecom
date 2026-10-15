<?php

namespace App\Services;

use App\Models\Branch;
use App\Services\Sms\ShatajSmsService;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function __construct(protected ShatajSmsService $sms) {}

    public function secondsUntilResend(Branch $branch): int
    {
        if (! $branch->otp_sent_at) {
            return 0;
        }
        $wait = config('shataj.otp.resend_seconds') - (int) $branch->otp_sent_at->diffInSeconds(now());

        return max(0, $wait);
    }

    public function send(Branch $branch): bool
    {
        $len = (int) config('shataj.otp.length');
        $code = str_pad((string) random_int(0, 10 ** $len - 1), $len, '0', STR_PAD_LEFT);
        $minutes = (int) config('shataj.otp.ttl_minutes');

        $branch->forceFill([
            'otp_code' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes($minutes),
            'otp_sent_at' => now(),
            'otp_attempts' => 0,
        ])->save();

        $message = strtr(config('shataj.otp.message'), [
            ':app' => config('app.name'),
            ':code' => $code,
            ':minutes' => $minutes,
        ]);

        // Local testing without SMS: show the code on the OTP page (only when SMS driver = log and APP_ENV = local)
        if (! $this->sms->isLive() && app()->isLocal()) {
            session()->flash('dev_otp', $code);
        }

        return $this->sms->send($branch->mobile, $message, true);   // OTP = priority
    }

    /** @return string|null error message, null on success */
    public function verify(Branch $branch, string $code): ?string
    {
        if (! $branch->otp_code || ! $branch->otp_expires_at || $branch->otp_expires_at->isPast()) {
            return 'OTP-এর মেয়াদ শেষ হয়ে গেছে। আবার OTP পাঠান।';
        }
        if ($branch->otp_attempts >= config('shataj.otp.max_attempts')) {
            return 'অনেকবার ভুল OTP দেওয়া হয়েছে। নতুন OTP পাঠান।';
        }
        if (! Hash::check($code, $branch->otp_code)) {
            $branch->increment('otp_attempts');

            return 'OTP সঠিক নয়।';
        }

        $branch->forceFill([
            'mobile_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_attempts' => 0,
        ])->save();

        return null;
    }
}
