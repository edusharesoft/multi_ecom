<?php

namespace App\Http\Middleware;

use App\Support\StoreCustomer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Storefront "My Account" area: customer must be logged in to THIS store. */
class CustomerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $branch = $request->route('branch');

        if (! StoreCustomer::current($branch)) {
            session()->put('customer.intended', $request->fullUrl());

            return redirect()->route('store.account.login', $branch)->with('warning', 'অনুগ্রহ করে লগইন করুন।');
        }

        return $next($request);
    }
}
