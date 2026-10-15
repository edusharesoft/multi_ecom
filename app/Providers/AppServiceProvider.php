<?php

namespace App\Providers;

use App\Support\StoreCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // @money($amount)  =>  ৳ 1,234.00
        // @perm('products.add') ... @endperm   — branch staff permission check
        Blade::if('perm', fn (string $permission) => \App\Support\Tenancy::can($permission));

        Blade::directive('money', fn ($e) => "<?php echo '৳ '.number_format((float) ($e), 2); ?>");

        $this->registerCacheInvalidation();
        $this->registerLazyLoadingLog();
    }

    /**
     * Phase 9 — storefront cache (App\Support\StoreCache) is dropped for a store whenever one of
     * the models it is built from changes. Updates done with query builder increment/decrement
     * (stock, sold_count) fire no events; the 5-minute TTL covers those.
     */
    protected function registerCacheInvalidation(): void
    {
        // listeners never return a value (returning false would stop the other listeners of the event)
        $byBranch = function ($m) {
            StoreCache::bump($m->branch_id ?? null);
        };
        foreach ([\App\Models\Product::class, \App\Models\Slider::class, \App\Models\Brand::class, \App\Models\Post::class,
            \App\Models\Page::class, \App\Models\Domain::class, \App\Models\Subscription::class, \App\Models\BlogCategory::class] as $model) {
            $model::saved($byBranch);
            $model::deleted($byBranch);
        }

        // children of a product (no branch_id column) → the product's store
        $byProduct = function ($m) {
            StoreCache::bump($m->product_id ? \App\Models\Product::whereKey($m->product_id)->value('branch_id') : null);
        };
        foreach ([\App\Models\ProductVariant::class, \App\Models\ProductImage::class, \App\Models\Review::class] as $model) {
            $model::saved($byProduct);
            $model::deleted($byProduct);
        }

        // store settings (theme, sections, menu, payment options …)
        \App\Models\Branch::saved(function ($b) {
            if ($b->wasChanged('remember_token') && count($b->getChanges()) <= 2) {
                return;   // only a login "remember me" token changed
            }
            StoreCache::bump($b->id);
        });
        \App\Models\Branch::deleted(function ($b) {
            StoreCache::bump($b->id);
        });

        // platform-wide data (categories, plans) → global version 0, part of every store key
        foreach ([\App\Models\Category::class, \App\Models\Plan::class] as $model) {
            $model::saved(function () {
                StoreCache::bump(0);
            });
            $model::deleted(function () {
                StoreCache::bump(0);
            });
        }

        // manager dashboard numbers
        $dash = function ($o) {
            if ($o->branch_id) {
                \Illuminate\Support\Facades\Cache::forget('dash:'.$o->branch_id);
            }
        };
        \App\Models\Order::saved($dash);
        \App\Models\Order::deleted($dash);

        // table list used by Features::ready() is refreshed after every migrate / rollback
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Database\Events\MigrationsEnded::class, function () {
            \App\Support\Features::forget();
        });
    }

    /**
     * In debug mode, relations loaded lazily inside a loop (N+1 queries) are written to
     * storage/logs/lazy-loading.log instead of silently slowing pages down. Nothing is thrown.
     */
    protected function registerLazyLoadingLog(): void
    {
        if (! config('app.debug') || $this->app->runningInConsole()) {
            return;
        }
        Model::preventLazyLoading();
        Model::handleLazyLoadingViolationUsing(function ($model, string $relation) {
            static $seen = [];
            $key = $model::class.'::'.$relation;
            if (isset($seen[$key])) {
                return;
            }
            $seen[$key] = true;
            try {
                $line = '['.now()->toDateTimeString().'] '.$key.' — '.(request()?->path() ?? '')."\n";
                file_put_contents(storage_path('logs/lazy-loading.log'), $line, FILE_APPEND | LOCK_EX);
            } catch (\Throwable) {
            }
        });
    }
}
