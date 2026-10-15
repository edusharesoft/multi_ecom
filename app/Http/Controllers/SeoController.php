<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Domain;
use App\Support\StoreTheme;

/** Platform robots.txt and sitemap index (also answers on custom domains). */
class SeoController extends Controller
{
    public function robots()
    {
        $branch = app()->bound('custom_domain_branch') ? app('custom_domain_branch') : null;
        $lines = ['User-agent: *'];
        if ($branch && StoreTheme::seo($branch)['noindex']) {
            $lines[] = 'Disallow: /';
        } else {
            foreach (['/manager', '/admin', '/login', '/register', '/otp', '/payment', '/webhooks', '/*/cart', '/*/checkout', '/*/account', '/*/wishlist'] as $p) {
                $lines[] = 'Disallow: '.$p;
            }
            $lines[] = '';
            $lines[] = 'Sitemap: '.($branch ? route('store.sitemap', $branch) : route('sitemap'));
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemapIndex()
    {
        if (app()->bound('custom_domain_branch')) {
            return redirect()->route('store.sitemap', app('custom_domain_branch'));
        }
        $branches = Branch::whereNotNull('onboarded_at')->get()->filter(fn (Branch $b) => $b->isOpen() && ! StoreTheme::seo($b)['noindex']);

        return response()->view('seo.sitemap-index', ['branches' => $branches])->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
