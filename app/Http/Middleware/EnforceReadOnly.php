<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Expired subscriptions (expiry action = readonly): backend can be viewed but not changed, except billing. */
class EnforceReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $branch = Tenancy::branch();

        if ($branch && ! $request->isMethodSafe() && $branch->isReadOnly()
            && ! $request->routeIs('manager.billing*', 'manager.onboarding*', 'logout', 'manager.notifications*')) {
            return back()->with('warning', 'সাবস্ক্রিপশনের মেয়াদ শেষ — অ্যাকাউন্ট Read-only। পরিবর্তন করতে রিনিউ করুন।');
        }

        return $next($request);
    }
}
