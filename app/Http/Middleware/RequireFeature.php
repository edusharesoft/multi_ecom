<?php

namespace App\Http\Middleware;

use App\Models\Plan;
use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usage: ->middleware('feature:pos') — plan based feature lock. */
class RequireFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $branch = Tenancy::branch();

        if (! $branch || ! $branch->hasFeature($feature)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => (Plan::FEATURES[$feature] ?? $feature).' ফিচারটি আপনার বর্তমান প্ল্যানে নেই। আপগ্রেড করুন।'], 402);
            }

            return redirect()->route('manager.billing')->with('warning', (Plan::FEATURES[$feature] ?? $feature).' ফিচারটি আপনার বর্তমান প্ল্যানে নেই। আপগ্রেড করুন।');
        }

        return $next($request);
    }
}
