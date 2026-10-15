<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Throwable;

class BackupController extends Controller
{
    public function __construct(protected BackupService $backups) {}

    public function index()
    {
        return view('admin.backups', ['files' => $this->backups->list(), 'keep' => (int) (Setting::get('backup_keep') ?: 7), 'auto' => (bool) Setting::get('backup_auto')]);
    }

    public function create(Request $request)
    {
        $type = $request->validate(['type' => ['required', 'in:database,uploads']])['type'];
        try {
            $name = $type === 'database' ? $this->backups->database() : $this->backups->uploads();
        } catch (Throwable $e) {
            report($e);

            return back()->with('warning', 'Backup failed: '.$e->getMessage());
        }
        ActivityLog::record('backup.created', "Backup created: {$name}");

        return back()->with('success', "Backup তৈরি হয়েছে: {$name}");
    }

    public function download(string $name)
    {
        try {
            return response()->download($this->backups->path($name));
        } catch (Throwable) {
            abort(404);
        }
    }

    public function destroy(string $name)
    {
        try {
            @unlink($this->backups->path($name));
        } catch (Throwable) {
            abort(404);
        }

        return back()->with('success', 'Deleted.');
    }

    public function restore(Request $request, string $name)
    {
        $request->validate(['confirm' => ['required', 'in:RESTORE']], ['confirm.in' => 'নিশ্চিত করতে বড় হাতে RESTORE লিখুন।']);
        try {
            // safety copy of the current state first
            $safety = $this->backups->database();
            $n = $this->backups->restore($name);
        } catch (Throwable $e) {
            report($e);

            return back()->with('warning', 'Restore failed: '.$e->getMessage());
        }

        return redirect()->route('admin.login')->with('success', "Restore সম্পন্ন ({$n} statements)। আগের অবস্থার কপি: {$safety}। আবার লগইন করুন।");
    }

    public function settings(Request $request)
    {
        $data = $request->validate(['backup_auto' => ['nullable', 'boolean'], 'backup_keep' => ['required', 'integer', 'min:1', 'max:60']]);
        Setting::put(['backup_auto' => ! empty($data['backup_auto']) ? '1' : '', 'backup_keep' => (string) $data['backup_keep']]);

        return back()->with('success', 'Saved.');
    }
}
