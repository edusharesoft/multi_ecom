<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usage: ->middleware('perm:products.add')  (owner always passes) */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        foreach ($permissions as $permission) {
            if (! Tenancy::can($permission)) {
                abort(403, "আপনার এই কাজের অনুমতি নেই ({$permission})।");
            }
        }

        return $next($request);
    }
}
