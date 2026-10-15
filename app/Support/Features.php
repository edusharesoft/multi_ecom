<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Guards for code that runs before `php artisan migrate` created a phase's tables.
 *
 * Performance: the list of existing tables is read ONCE and cached (one cache read per request)
 * instead of one information_schema query per table per request. A table that is not in the
 * cached list is re-checked live, so a fresh migration is noticed without clearing the cache.
 */
class Features
{
    protected static array $tables = [];

    protected static ?array $listing = null;

    public static function ready(string $table): bool
    {
        if (isset(static::$tables[$table])) {
            return static::$tables[$table];
        }

        if (isset(static::listing()[$table])) {
            return static::$tables[$table] = true;
        }

        $exists = Schema::hasTable($table);
        if ($exists) {
            static::forget();   // migrated since the list was cached
        }

        return static::$tables[$table] = $exists;
    }

    public static function forget(): void
    {
        static::$listing = null;
        try {
            Cache::forget('schema_tables');
        } catch (Throwable) {
        }
    }

    protected static function listing(): array
    {
        if (static::$listing !== null) {
            return static::$listing;
        }
        try {
            return static::$listing = Cache::remember('schema_tables', now()->addDay(), function () {
                $db = DB::connection()->getDatabaseName();
                $prefix = (string) DB::connection()->getTablePrefix();
                $names = array_map(fn ($n) => $prefix !== '' && str_starts_with($n, $prefix) ? substr($n, strlen($prefix)) : $n,
                    array_column(Schema::getTables($db), 'name'));

                return array_fill_keys(array_map('strtolower', $names), true);
            });
        } catch (Throwable) {
            return static::$listing = [];   // fall back to live checks
        }
    }
}
