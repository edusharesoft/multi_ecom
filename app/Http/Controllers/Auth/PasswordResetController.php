<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Services\Sms\ShatajSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Phase 12 — "Forgot your password?" for branch owners: a one-time code is sent by SMS to the
 * store's registered mobile, then a new password is set. The code lives only in the cache
 * (hashed, 10 minutes, 5 attempts), so it never touches the registration OTP fields.
 * The response is the same whether or not the account exists (no account guessing).
 */
class PasswordResetController extends Controller
{
    protected const TTL = 10;          // minutes

    protected const RESEND = 60;       // seconds between two codes

    protected const ATTEMPTS = 5;

    protected const DAILY = 5;         // codes per store per day

    public function request()
    {
        return view('auth.forgot');
    }

    public function send(Request $request, ShatajSmsService $sms)
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:150']]);
        $login = Str::lower(trim($data['login']));
        $mobile = preg_replace('/^\+?88/', '', preg_replace('/[^\d+]/', '', $login));

        $branch = Branch::where('email', $login)->orWhere('slug', $login)
            ->when(preg_match('/^01[3-9]\d{8}$/', $mobile), fn ($q) => $q->orWhere('mobile', $mobile))
            ->first();

        // at most 5 codes per store per day (no SMS flooding through a known store name)
        if ($branch && ! $branch->isSuspended() && ! RateLimiter::tooManyAttempts('pwreset-day:'.$branch->id, self::DAILY)) {
            $key = 'pwreset:'.$branch->id;
            $old = Cache::get($key);
            if (! $old || now()->timestamp - ($old['sent'] ?? 0) >= self::RESEND) {
                RateLimiter::hit('pwreset-day:'.$branch->id, 86400);
                $len = (int) config('shataj.otp.length', 6);
                $code = str_pad((string) random_int(0, 10 ** $len - 1), $len, '0', STR_PAD_LEFT);
                $expires = now()->addMinutes(self::TTL);
                Cache::put($key, ['hash' => Hash::make($code), 'sent' => now()->timestamp, 'expires' => $expires->timestamp], $expires);
                Cache::forget($key.':tries');
                $sms->send($branch->mobile, "{$code} is your password reset code for ".config('app.name').'. Valid for '.self::TTL.' minutes. Do not share it.', true);
                if (! $sms->isLive() && app()->isLocal()) {
                    session()->flash('dev_otp', $code);
                }
                ActivityLog::record('auth.password_reset_requested', 'Password reset code sent', $branch, [], $branch->id);
            }
        }
        // same answer (and the same page) for unknown accounts — nothing tells whether the account exists
        $request->session()->put('pwreset_branch', $branch?->id ?? 0);

        return redirect()->route('password.reset')->with('success', 'অ্যাকাউন্ট পাওয়া গেলে রেজিস্টার্ড মোবাইলে একটি কোড পাঠানো হয়েছে।');
    }

    public function edit(Request $request)
    {
        if (! $request->session()->has('pwreset_branch')) {
            return redirect()->route('password.forgot');
        }

        return view('auth.reset', ['hint' => null]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'digits:'.config('shataj.otp.length', 6)],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $id = (int) $request->session()->get('pwreset_branch');
        $key = 'pwreset:'.$id;
        $entry = $id ? Cache::get($key) : null;

        // attempts are counted atomically before checking, so parallel guesses cannot exceed the limit
        $tries = $entry ? RateLimiter::hit($key.':tries', max(1, ($entry['expires'] ?? 0) - now()->timestamp)) : self::ATTEMPTS + 1;
        if (! $entry || $tries > self::ATTEMPTS) {
            Cache::forget($key);

            return back()->withErrors(['code' => 'কোডের মেয়াদ শেষ বা অনেকবার ভুল হয়েছে — আবার কোড নিন।']);
        }
        if (! Hash::check($data['code'], $entry['hash'])) {
            return back()->withErrors(['code' => 'কোড সঠিক নয়।']);
        }

        $branch = Branch::find($id);
        if (! $branch) {
            return redirect()->route('password.forgot');
        }
        $branch->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();   // cast "hashed"; other devices are signed out
        Cache::forget($key);
        RateLimiter::clear($key.':tries');
        $request->session()->forget('pwreset_branch');
        ActivityLog::record('auth.password_reset', 'Password changed with SMS code', $branch, [], $branch->id);

        return redirect()->route('login', ['branch' => $branch->slug])->with('success', 'পাসওয়ার্ড পরিবর্তন হয়েছে — নতুন পাসওয়ার্ড দিয়ে লগইন করুন।');
    }
}
