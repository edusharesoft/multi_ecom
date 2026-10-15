<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The branch owner must verify the mobile number (OTP) before using the backend. */
class EnsureBranchVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $branch = Tenancy::branch() ?? $request->user('branch');

        if ($branch && ! $branch->isVerified()) {
            if (Tenancy::isOwner() || $request->user('branch')) {
                return redirect()->route('otp.show')->with('warning', 'আগে আপনার মোবাইল নম্বর OTP দিয়ে ভেরিফাই করুন।');
            }
            abort(403, 'Branch is not verified yet.');
        }

        return $next($request);
    }
}
