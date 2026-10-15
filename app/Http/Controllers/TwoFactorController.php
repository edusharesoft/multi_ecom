<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\User;
use App\Support\Tenancy;
use App\Support\Totp;
use App\Support\TwoFactor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/** 2FA login challenge + enable / disable screens for branch owner, staff and Super Admin. */
class TwoFactorController extends Controller
{
    /* ---------------- login challenge ---------------- */

    public function challenge(Request $request)
    {
        $p = $request->session()->get(TwoFactor::SESSION);
        if (! $p || $p['expires'] < now()->timestamp) {
            $request->session()->forget(TwoFactor::SESSION);

            return redirect()->route($request->routeIs('admin.*') ? 'admin.login' : 'login')->withErrors(['email' => 'সময় শেষ — আবার লগইন করুন।']);
        }

        return view('auth.two-factor', ['action' => route($request->routeIs('admin.*') ? 'admin.two-factor.verify' : 'two-factor.verify'), 'platform' => $request->routeIs('admin.*')]);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => ['required', 'string', 'max:20']]);
        $p = $request->session()->get(TwoFactor::SESSION);
        $loginRoute = $request->routeIs('admin.*') ? 'admin.login' : 'login';
        if (! $p || $p['expires'] < now()->timestamp) {
            $request->session()->forget(TwoFactor::SESSION);

            return redirect()->route($loginRoute)->withErrors(['email' => 'সময় শেষ — আবার লগইন করুন।']);
        }
        if (($request->routeIs('admin.*') && $p['guard'] !== 'web') || (! $request->routeIs('admin.*') && $p['guard'] === 'web')) {
            abort(403);
        }
        $model = ['branch' => Branch::class, 'staff' => Staff::class, 'web' => User::class][$p['guard']];
        $user = $model::find($p['id']);
        if (! $user || ! TwoFactor::check($user, (string) $request->code)) {
            return back()->withErrors(['code' => 'কোডটি সঠিক নয়। অ্যাপের নতুন কোড দিন, অথবা একটি Recovery code।']);
        }
        $request->session()->forget(TwoFactor::SESSION);
        Auth::guard($p['guard'])->login($user, (bool) $p['remember']);
        $request->session()->regenerate();

        return redirect()->intended($p['then']);
    }

    /* ---------------- settings (manager: owner / staff) ---------------- */

    public function manager(Request $request)
    {
        return $this->page($request, Tenancy::actor(), 'manager.security', 'layouts.manager');
    }

    public function admin(Request $request)
    {
        return $this->page($request, Auth::guard('web')->user(), 'admin.security', 'layouts.admin');
    }

    public function enable(Request $request)
    {
        $user = $this->user($request);
        $data = $request->validate(['code' => ['required', 'string'], 'password' => ['required', 'string']]);
        $secret = $request->session()->get('two_factor_setup');
        if (! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'পাসওয়ার্ড সঠিক নয়।']);
        }
        if (! $secret || Totp::verify($secret, $data['code']) === null) {
            return back()->withErrors(['code' => 'কোড মেলেনি — ফোনের সময় ঠিক আছে কিনা দেখুন এবং নতুন কোড দিন।']);
        }
        $codes = TwoFactor::recoveryCodes();
        // new remember token: "remember me" cookies issued before 2FA was on can no longer skip the code
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => $codes, 'two_factor_confirmed_at' => now(), 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
        $request->session()->forget('two_factor_setup');
        $request->session()->flash('recovery_codes', $codes);
        $this->log($user, '2fa.enabled', 'Two-factor authentication enabled');

        return back()->with('success', '2FA চালু হয়েছে। Recovery code গুলো নিরাপদে লিখে রাখুন — এগুলো আর দেখানো হবে না।');
    }

    public function disable(Request $request)
    {
        $user = $this->user($request);
        $data = $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['password' => 'পাসওয়ার্ড সঠিক নয়।']);
        }
        $user->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        $this->log($user, '2fa.disabled', 'Two-factor authentication disabled');

        return back()->with('success', '2FA বন্ধ করা হয়েছে।');
    }

    public function regenerate(Request $request)
    {
        $user = $this->user($request);
        $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'পাসওয়ার্ড সঠিক নয়।']);
        }
        abort_unless(TwoFactor::enabled($user), 422);
        $codes = TwoFactor::recoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();
        $request->session()->flash('recovery_codes', $codes);

        return back()->with('success', 'নতুন Recovery code তৈরি হয়েছে (আগেরগুলো আর কাজ করবে না)।');
    }

    protected function page(Request $request, Model $user, string $view, string $layout)
    {
        abort_unless(TwoFactor::ready($user), 503, 'Run php artisan migrate (update.bat) first.');
        $secret = null;
        if (! TwoFactor::enabled($user)) {
            $secret = $request->session()->get('two_factor_setup') ?: Totp::secret();
            $request->session()->put('two_factor_setup', $secret);
        }
        $account = $user->email ?: ($user->mobile ?? $user->name);

        return view('security.two-factor', [
            'layout' => $layout, 'user' => $user, 'enabled' => TwoFactor::enabled($user), 'secret' => $secret,
            'uri' => $secret ? Totp::uri($secret, (string) $account, \App\Models\Setting::siteName()) : null,
            'prefix' => $request->routeIs('admin.*') ? 'admin.security' : 'manager.security',
            'codesLeft' => count((array) ($user->two_factor_recovery_codes ?? [])),
        ]);
    }

    protected function user(Request $request): Model
    {
        return $request->routeIs('admin.*') ? Auth::guard('web')->user() : Tenancy::actor();
    }

    protected function log(Model $user, string $action, string $desc): void
    {
        ActivityLog::record($action, $desc.' — '.($user->name ?? ''), $user instanceof User ? null : $user);
    }
}
