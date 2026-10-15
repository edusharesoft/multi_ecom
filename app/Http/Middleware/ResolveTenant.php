<?php

namespace App\Http\Middleware;

use App\Models\AppNotification;
use App\Models\Staff;
use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current tenant (branch) from the logged-in owner or staff member
 * and blocks suspended / deactivated accounts.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = auth('branch')->user() ?? auth('staff')->user();
        if (! $actor) {
            return redirect()->route('login');
        }

        if ($actor instanceof Staff) {
            if (! $actor->is_active || ! $actor->branch) {
                auth('staff')->logout();

                return redirect()->route('login')->withErrors(['email' => 'আপনার স্টাফ অ্যাকাউন্ট নিষ্ক্রিয়।']);
            }
            $branch = $actor->branch;
        } else {
            $branch = $actor;
        }

        $impersonating = $request->session()->has('impersonating');

        if ($branch->isSuspended() && ! $impersonating) {
            // Suspended for non-payment: the owner may still reach billing to pay
            $billingOnly = $branch->suspend_reason === 'subscription_expired' && $actor === $branch
                && $request->routeIs('manager.billing*', 'logout', 'manager.notifications*');
            if (! $billingOnly) {
                if ($branch->suspend_reason === 'subscription_expired' && $actor === $branch) {
                    return redirect()->route('manager.billing')->with('warning', 'সাবস্ক্রিপশন রিনিউ না করায় অ্যাকাউন্ট স্থগিত। পেমেন্ট করলে সাথে সাথে চালু হবে।');
                }
                auth('branch')->logout();
                auth('staff')->logout();

                return redirect()->route('login')->withErrors(['email' => 'আপনার ব্রাঞ্চ Super Admin কর্তৃক স্থগিত করা হয়েছে।']);
            }
        }

        Tenancy::set($branch, $actor);

        View::share([
            'tenant' => $branch,
            'actor' => $actor,
            'isOwner' => Tenancy::isOwner(),
            'unreadNotifications' => AppNotification::where('branch_id', $branch->id)->unread()->count(),
        ]);

        return $next($request);
    }
}
