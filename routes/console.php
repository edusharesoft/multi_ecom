<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SaaS automation: reminders (7/3/1/0 days), renewal invoices, grace period, expiry.
// Laragon/Windows: keep `php artisan schedule:work` running, or add a Task Scheduler job for `php artisan schedule:run` every minute.
Schedule::command('saas:process')->hourly()->withoutOverlapping();

// Phase 5: courier status sync + campaign sending (browser progress bar also sends, this is the fallback)
Schedule::command('courier:sync')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('marketing:send')->everyMinute()->withoutOverlapping();

// Phase 7: nightly backup (enable in Super Admin → Backup)
Schedule::command('backup:run --uploads')->dailyAt('02:30')->withoutOverlapping();

// Phase 9: remove expired file-cache entries (versioned store caches leave old keys behind)
Schedule::command('cache:prune-expired')->dailyAt('03:30')->withoutOverlapping();
