<?php

namespace App\Support;

use App\Models\Branch;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Versioned cache per store (branch).
 *
 * Every key contains the branch's version number and the global version (id 0, used for
 * platform-wide data such as categories). Changing a product / slider / page / domain etc.
 * calls bump() (see AppServiceProvider), which changes the version, so all the store's
 * cached data is ignored at once — no need to know every key. Old entries simply expire.
 *
 * The cache is only an accelerator: when the cache store fails, the callback runs directly.
 */
class StoreCache
{
    /** default life of a cached block (seconds) — also bounds staleness for changes made without model events (stock / sold_count updates) */
    public const TTL = 300;

    protected static array $versions = [];

    public static function remember(Branch|int $branch, string $name, Closure $callback, ?int $ttl = null): mixed
    {
        $id = $branch instanceof Branch ? (int) $branch->id : $branch;
        $key = static::key($id, $name);

        try {
            $hit = Cache::get($key);
        } catch (Throwable) {
            return $callback();
        }
        if (is_array($hit) && array_key_exists('v', $hit)) {
            return $hit['v'];
        }

        $value = $callback();
        try {
            Cache::put($key, ['v' => $value], $ttl ?? self::TTL);   // wrapped so false / null / empty results are cached too
        } catch (Throwable) {
            // not cacheable (or cache store down) — still return the fresh value
        }

        return $value;
    }

    /** Platform-wide category tree (top level + children), shared by every store's menu and filters. */
    public static function categories(): \Illuminate\Support\Collection
    {
        return static::remember(0, 'category_tree', fn () => \App\Models\Category::top()->with('children')->orderBy('sort')->get(), 86400);
    }

    /** Invalidate everything cached for one store (0 = platform-wide data, which every store key includes). */
    public static function bump(?int $branchId): void
    {
        if ($branchId === null) {
            return;
        }
        static::write($branchId);
        // a change made inside a transaction must not be re-cached from the old rows by a parallel request
        try {
            if (DB::transactionLevel() > 0) {
                DB::afterCommit(fn () => static::write($branchId));
            }
        } catch (Throwable) {
        }
    }

    public static function key(int $branchId, string $name): string
    {
        return 'store:'.$branchId.':v'.static::version($branchId).':g'.static::version(0).':'.$name;
    }

    protected static function version(int $branchId): int
    {
        // memoised for one web request only — long-running workers always read the current version
        if (isset(static::$versions[$branchId]) && ! app()->runningInConsole()) {
            return static::$versions[$branchId];
        }
        try {
            $version = (int) Cache::rememberForever('store_ver:'.$branchId, fn () => 1);
        } catch (Throwable) {
            $version = 1;
        }

        return static::$versions[$branchId] = $version;
    }

    protected static function write(int $branchId): void
    {
        // a new unique number — no read-modify-write race between two requests
        $version = (int) (microtime(true) * 1000) + random_int(0, 999);
        static::$versions[$branchId] = $version;
        try {
            Cache::forever('store_ver:'.$branchId, $version);
        } catch (Throwable) {
        }
    }
}
