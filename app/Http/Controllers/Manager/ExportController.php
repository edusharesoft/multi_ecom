<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\BackupService;
use App\Support\Tenancy;
use Throwable;

/** Store owner: download all of the store's own data (CSV files in a zip). */
class ExportController extends Controller
{
    public function __invoke(BackupService $backups)
    {
        abort_unless(Tenancy::isOwner(), 403, 'Only the store owner can export all data.');
        try {
            $rel = $backups->branchExport(Tenancy::branch());
        } catch (Throwable $e) {
            report($e);

            return back()->with('warning', 'Export failed: '.$e->getMessage());
        }
        ActivityLog::record('backup.export', 'Store data exported');

        return response()->download($backups->disk()->path(BackupService::DIR.'/'.$rel))->deleteFileAfterSend();
    }
}
