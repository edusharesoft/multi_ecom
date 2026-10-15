<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After login a new branch must: 1) Select Subscription  2) Payment  3) Create Store.
 */
class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $branch = Tenancy::branch();

        if ($branch && ! $branch->isOnboarded()) {
            if (! Tenancy::isOwner()) {
                abort(403, 'Store setup is not completed by the branch owner yet.');
            }
            if (! $branch->subscription) {
                return redirect()->route('manager.onboarding.plan');
            }

            return redirect()->route('manager.onboarding.store');
        }

        return $next($request);
    }
}
