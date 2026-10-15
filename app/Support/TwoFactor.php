<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Two-factor (TOTP) helper shared by branch owners, staff and the Super Admin.
 * Columns on branches / staff / users: two_factor_secret (encrypted), two_factor_recovery_codes (encrypted:array),
 * two_factor_confirmed_at.
 */
class TwoFactor
{
    public const SESSION = 'two_factor_login';

    public static function ready(Model $user): bool
    {
        return Schema::hasColumn($user->getTable(), 'two_factor_secret');
    }

    public static function enabled(?Model $user): bool
    {
        return $user && $user->getAttribute('two_factor_confirmed_at') !== null && $user->getAttribute('two_factor_secret');
    }

    public static function recoveryCodes(): array
    {
        return collect(range(1, 8))->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))->all();
    }

    /**
     * Call right after a successful password login. When the user has 2FA on, the session login is undone and
     * the user is sent to the code screen; returns null when no challenge is needed.
     */
    public static function intercept(Request $request, string $guard, Model $user, bool $remember, string $then, string $challengeRoute)
    {
        if (! static::enabled($user)) {
            return null;
        }
        Auth::guard($guard)->logout();
        $request->session()->regenerate();
        $request->session()->put(self::SESSION, ['guard' => $guard, 'id' => $user->getKey(), 'remember' => $remember, 'then' => $then, 'expires' => now()->addMinutes(5)->timestamp]);

        return redirect()->route($challengeRoute);
    }

    /** Verify a TOTP or a one-time recovery code for a pending login. */
    public static function check(Model $user, string $input): bool
    {
        $input = trim($input);
        $key = 'totp_used:'.$user->getTable().':'.$user->getKey();
        if (preg_match('/^\d{6}$/', preg_replace('/\s/', '', $input))) {
            $step = Totp::verify((string) $user->two_factor_secret, $input);
            // a code can be used only once (replay protection)
            if ($step !== null && (int) Cache::get($key) < $step) {
                Cache::put($key, $step, now()->addMinutes(3));

                return true;
            }

            return false;
        }
        $codes = (array) ($user->two_factor_recovery_codes ?? []);
        $i = array_search(Str::upper($input), $codes, true);
        if ($i !== false) {
            unset($codes[$i]);
            $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

            return true;
        }

        return false;
    }
}
