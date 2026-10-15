<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use App\Support\StoreCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/** Makes the current branch store available to every storefront view as $store. */
class ResolveStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->route('branch');

        abort_unless($store && $store->isOpen(), 404);

        // optional 301 from platform URL to the store's own primary domain (cached — was one query per page view)
        if ($request->isMethod('GET') && ! app()->bound('custom_domain_branch') && \App\Support\Features::ready('domains')) {
            $primaryHost = StoreCache::remember($store, 'primary_domain', fn () => (string) \App\Models\Domain::where('branch_id', $store->id)
                ->where('status', 'active')->where('is_primary', true)->where('redirect_to_domain', true)->value('host'), 3600);
            if ($primaryHost !== '') {
                return redirect()->away($request->getScheme().'://'.$primaryHost.$request->getRequestUri(), 301);
            }
        }

        $cart = new CartService($store);
        app()->instance(CartService::class, $cart);
        $cartCount = $cart->count();
        $miniCart = $cartCount ? $cart->items() : collect();   // resolved once, reused for the totals

        View::share([
            'store' => $store,
            'theme' => \App\Support\StoreTheme::theme($store),
            'storeMenu' => \App\Support\StoreTheme::menu($store),
            'storeSeo' => \App\Support\StoreTheme::seo($store),
            'footerPages' => \App\Support\Features::ready('pages')
                ? StoreCache::remember($store, 'footer_pages', fn () => \App\Models\Page::where('branch_id', $store->id)->where('is_published', true)->orderBy('sort')
                    ->get(['id', 'branch_id', 'title', 'slug', 'show_in_header', 'show_in_footer']), 3600)
                : collect(),
            'hasBlog' => $store->hasFeature('blog') && \App\Support\Features::ready('posts')
                && StoreCache::remember($store, 'has_blog', fn () => \App\Models\Post::where('branch_id', $store->id)->published()->exists()),
            'cartCount' => $cartCount,
            'cartTotal' => $cartCount ? $cart->totals($miniCart)['total'] : 0,
            'wishlistCount' => count(\App\Http\Controllers\Store\WishlistController::ids($store)),
            'menuCategories' => StoreCache::categories(),
            'storeCustomer' => $storeCustomer = \App\Support\StoreCustomer::current($store),
            'miniCart' => $miniCart,
        ]);

        $response = $next($request);
        if ($storeCustomer) {
            $response->headers->set('X-PWA-Private', '1');   // Phase 10: the service worker never saves pages of a logged-in customer
        }

        return $response;
    }
}
