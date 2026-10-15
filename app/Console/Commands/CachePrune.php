<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Phase 9 — the file cache only deletes an expired entry when that exact key is read again.
 * Store caches are versioned (old keys are never read after a change), so expired files are
 * removed here once a day. Only used when CACHE_STORE=file; other stores expire on their own.
 */
class CachePrune extends Command
{
    protected $signature = 'cache:prune-expired';

    protected $description = 'Delete expired entries from the file cache';

    public function handle(): int
    {
        if (config('cache.default') !== 'file') {
            $this->line('Cache store is not "file" — nothing to do.');

            return self::SUCCESS;
        }

        $dir = (string) config('cache.stores.file.path', storage_path('framework/cache/data'));
        if (! is_dir($dir)) {
            return self::SUCCESS;
        }

        $now = time();
        $deleted = 0;
        foreach (File::allFiles($dir) as $file) {
            $h = @fopen($file->getPathname(), 'r');
            if (! $h) {
                continue;
            }
            $expires = fread($h, 10);   // FileStore writes a 10-digit expiry timestamp first
            fclose($h);
            if (ctype_digit((string) $expires) && (int) $expires < $now && @unlink($file->getPathname())) {
                $deleted++;
            }
        }
        $this->info("Deleted {$deleted} expired cache file(s).");

        return self::SUCCESS;
    }
}
