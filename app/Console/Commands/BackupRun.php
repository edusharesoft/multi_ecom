<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupRun extends Command
{
    protected $signature = 'backup:run {--uploads : also zip uploaded files} {--force : run even when auto-backup is off}';

    protected $description = 'Database (and optionally uploads) backup to storage/app/backups, keeping the newest N';

    public function handle(BackupService $backups): int
    {
        if (! $this->option('force') && ! Setting::get('backup_auto')) {
            $this->line('Auto backup is off (Super Admin → Backup).');

            return self::SUCCESS;
        }
        $this->info('DB: '.$backups->database());
        if ($this->option('uploads')) {
            $this->info('Uploads: '.$backups->uploads());
        }
        $backups->prune((int) (Setting::get('backup_keep') ?: 7));

        return self::SUCCESS;
    }
}
