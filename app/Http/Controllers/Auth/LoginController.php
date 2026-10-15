<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    /** Login with Email (or USER / sub-folder name) + Password. */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $login = Str::lower(trim($request->input('email')));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'slug';

        if (! Auth::guard('branch')->attempt([$field => $login, 'password' => $request->input('password')], $request->boolean('remember'))) {
            // Not an owner — try branch staff (email only)
            if ($field === 'email' && Auth::guard('staff')->attempt(['email' => $login, 'password' => $request->input('password'), 'is_active' => true], $request->boolean('remember'))) {
                $request->session()->regenerate();
                $staff = Auth::guard('staff')->user();
                if ($staff->branch) {
                    \Illuminate\Support\Facades\Cookie::queue('mb_last_branch', $staff->branch->slug, 60 * 24 * 365);
                }
                $staff->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
                \App\Models\ActivityLog::record('auth.login', "Staff login: {$staff->name}", $staff, [], $staff->branch_id);

                return \App\Support\TwoFactor::intercept($request, 'staff', $staff, $request->boolean('remember'), route('manager.dashboard'), 'two-factor.challenge')
                    ?? redirect()->intended(route('manager.dashboard'));
            }

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'ইমেইল/ইউজার অথবা পাসওয়ার্ড সঠিক নয়।']);
        }

        $request->session()->regenerate();
        $branch = Auth::guard('branch')->user();

        // Suspended by Super Admin => no access
        // Suspended for non-payment => owner may log in, but only to reach Billing and pay
        if ($branch->isSuspended() && $branch->suspend_reason === 'subscription_expired') {
            return \App\Support\TwoFactor::intercept($request, 'branch', $branch, $request->boolean('remember'), route('manager.billing'), 'two-factor.challenge')
                ?? redirect()->route('manager.billing');
        }
        if ($branch->isSuspended()) {
            Auth::guard('branch')->logout();

            return back()->withInput($request->only('email'))->withErrors(['email' => 'আপনার ব্রাঞ্চ Super Admin কর্তৃক স্থগিত করা হয়েছে।'.($branch->suspend_reason ? ' কারণ: '.$branch->suspend_reason : '')]);
        }

        // status "inactive" only hides the storefront (Settings > Store Status); the manager must still be able to log in to re-activate it.
        if (! $branch->isVerified()) {
            return \App\Support\TwoFactor::intercept($request, 'branch', $branch, $request->boolean('remember'), route('otp.show'), 'two-factor.challenge')
                ?? redirect()->route('otp.show');
        }
        \App\Models\ActivityLog::record('auth.login', "Owner login: {$branch->name}", $branch, [], $branch->id);
        \Illuminate\Support\Facades\Cookie::queue('mb_last_branch', $branch->slug, 60 * 24 * 365);   // Phase 12: login page shows this branch's logo next time

        return \App\Support\TwoFactor::intercept($request, 'branch', $branch, $request->boolean('remember'), route('manager.dashboard'), 'two-factor.challenge')
            ?? redirect()->intended(route('manager.dashboard'));
    }

    public function logout(Request $request)
    {
        // Super Admin was "logged in as" this branch -> go back to admin panel
        if ($request->session()->pull('impersonating')) {
            Auth::guard('branch')->logout();

            return redirect()->route('admin.branches.index')->with('success', 'Branch session closed.');
        }

        Auth::guard('branch')->logout();
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
