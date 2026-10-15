<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Models\Domain;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Global (runs before routing). When the request comes in on a merchant's custom domain
 * (shop.example.com) or a store sub-domain (barishal.platform.com), the path is internally prefixed
 * with the store slug so the normal /{branch}/... storefront routes answer it:
 *     shop.example.com/            → /barishal
 *     shop.example.com/products    → /barishal/products
 * Links generated on that host keep using the same host.
 */
class ResolveCustomDomain
{
    /** First path segments that are never rewritten (assets, callbacks, SEO files). */
    protected const PASS = ['storage', 'build', 'css', 'js', 'images', 'img', 'vendor', 'payment', 'webhooks', 'api', 'robots.txt', 'sitemap.xml', 'favicon.ico', 'up'];

    /** Back-office areas are only served on the main platform domain. */
    protected const APP_ONLY = ['manager', 'admin', 'login', 'register', 'otp', 'logout', 'password', 'two-factor'];

    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($host === '' || $host === $appHost || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return $next($request);
        }

        $branch = $this->branchFor($host, $appHost);
        if (! $branch) {
            return $next($request);
        }

        $path = trim($request->path(), '/');
        $first = explode('/', $path)[0];

        if (in_array($first, self::APP_ONLY, true)) {
            $qs = $request->getQueryString();
            if (in_array($first, ['login', 'register', 'password'], true) && ! $request->query('branch')) {
                $qs = ltrim(($qs ? $qs.'&' : '').'branch='.urlencode($branch->slug), '&');   // show this store's logo on the login page
            }

            return redirect()->away(rtrim((string) config('app.url'), '/').'/'.$path.($qs ? '?'.$qs : ''));
        }

        // a SESSION_DOMAIN like ".platform.com" would make browsers drop the session cookie here (→ 419 on every POST)
        $cookieDomain = ltrim(strtolower((string) config('session.domain')), '.');
        if ($cookieDomain !== '' && $host !== $cookieDomain && ! str_ends_with($host, '.'.$cookieDomain)) {
            config(['session.domain' => null]);
        }

        URL::forceRootUrl($request->getSchemeAndHttpHost());
        app()->instance('custom_domain_branch', $branch);

        if ($first === $branch->slug || in_array($first, self::PASS, true)) {
            return $next($request);
        }

        $uri = '/'.$branch->slug.($path === '' ? '' : '/'.$path);
        $query = $request->getQueryString();
        $new = $request->duplicate();
        $new->server->set('REQUEST_URI', $uri.($query ? '?'.$query : ''));

        return $next($new);
    }

    protected function branchFor(string $host, string $appHost): ?Branch
    {
        try {
            $id = Cache::remember('domain_host:'.$host, now()->addMinutes(10), function () use ($host, $appHost) {
                if (Schema::hasTable('domains')) {
                    $bare = preg_replace('/^www\./', '', $host);
                    $d = Domain::whereIn('host', array_unique([$host, $bare]))->where('status', 'active')->first();
                    if ($d) {
                        return $d->branch_id;
                    }
                }
                // {slug}.platform-domain sub-domains (needs wildcard DNS)
                $base = strtolower((string) (Setting::get('store_base_domain') ?: $appHost));
                if (Setting::get('store_subdomains') && $base && str_ends_with($host, '.'.$base)) {
                    $slug = substr($host, 0, -strlen('.'.$base));
                    if ($slug !== 'www' && ! str_contains($slug, '.')) {
                        return Branch::where('slug', $slug)->value('id') ?: 0;
                    }
                }

                return 0;
            });
        } catch (Throwable) {
            return null;
        }

        return $id ? Branch::find($id) : null;
    }
}
