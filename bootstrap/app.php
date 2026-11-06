<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // custom domains / store sub-domains → /{slug}/... (global = runs before routing; appended so TrustProxies has
        // already run and getSchemeAndHttpHost() reports https behind a proxy)
        $middleware->append(\App\Http\Middleware\ResolveCustomDomain::class);
        $middleware->api(prepend: [\App\Http\Middleware\ForceJsonResponse::class]);
        $middleware->web(append: [\App\Http\Middleware\SetLocale::class]);
        $middleware->alias([
            'branch.verified' => \App\Http\Middleware\EnsureBranchVerified::class,
            'store' => \App\Http\Middleware\ResolveStore::class,
            'super.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'tenant' => \App\Http\Middleware\ResolveTenant::class,
            'onboarded' => \App\Http\Middleware\EnsureOnboarded::class,
            'perm' => \App\Http\Middleware\CheckPermission::class,
            'readonly' => \App\Http\Middleware\EnforceReadOnly::class,
            'feature' => \App\Http\Middleware\RequireFeature::class,
            'customer.auth' => \App\Http\Middleware\CustomerAuth::class,
            'api.token' => \App\Http\Middleware\AuthenticateApiToken::class,
        ]);
        $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $r) => $r->is('admin', 'admin/*') ? route('admin.login') : route('login'));
        $middleware->redirectUsersTo(fn (\Illuminate\Http\Request $r) => $r->is('admin', 'admin/*') ? route('admin.dashboard') : route('manager.dashboard'));
        // ShatajPay posts server-to-server / cross-site to these
        $middleware->validateCsrfTokens(except: ['payment/shatajpay/*', 'webhooks/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
