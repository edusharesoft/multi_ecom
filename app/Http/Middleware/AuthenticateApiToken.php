<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('api.token:products.read')
 * Header: Authorization: Bearer mb_<branch>_<random>
 * Sets the tenant (branch) for the request; the plan must include the "api" feature.
 */
class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, ?string $ability = null): Response
    {
        $plain = (string) $request->bearerToken();
        $token = $plain !== '' ? ApiToken::with('branch')->where('token_hash', hash('sha256', $plain))->first() : null;

        if (! $token || $token->expired()) {
            return response()->json(['message' => 'Unauthenticated. Send a valid token: Authorization: Bearer <token>'], 401);
        }
        $branch = $token->branch;
        if (! $branch || $branch->isSuspended()) {
            return response()->json(['message' => 'Store is suspended.'], 403);
        }
        if (! $branch->hasFeature('api')) {
            return response()->json(['message' => 'REST API is not included in the current plan.'], 402);
        }
        if ($ability && ! $token->can($ability)) {
            return response()->json(['message' => "This token lacks the '{$ability}' ability."], 403);
        }
        if (! $request->isMethodSafe() && $branch->isReadOnly()) {
            return response()->json(['message' => 'Subscription expired — the store is read-only. Renew to make changes.'], 403);
        }

        Tenancy::set($branch, $branch);
        $request->attributes->set('api_token', $token);

        // write "last used" at most once a minute
        if (! $token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now(), 'last_used_ip' => $request->ip()])->saveQuietly();
        }

        return $next($request);
    }
}
