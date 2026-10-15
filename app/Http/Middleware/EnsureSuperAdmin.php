<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if (! $user || ! $user->is_super_admin) {
            auth('web')->logout();

            return redirect()->route('admin.login')->withErrors(['email' => 'Super Admin access required.']);
        }

        return $next($request);
    }
}
