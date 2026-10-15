<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AppNotification;
use App\Models\Branch;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('branch')
            ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->branch))
            ->when($request->input('scope') === 'platform', fn ($q) => $q->whereNull('branch_id'))
            ->when($request->filled('actor'), fn ($q) => $q->where('actor_type', $request->actor))
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', $request->input('action').'%'))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('description', 'like', '%'.$request->q.'%')->orWhere('ip', 'like', '%'.$request->q.'%')))
            ->latest('created_at')->paginate(40)->withQueryString();

        return view('admin.activity', ['logs' => $logs, 'branches' => Branch::orderBy('store_name')->get(['id', 'store_name', 'name'])]);
    }

    public function notifications()
    {
        $items = AppNotification::forAdmin()->latest()->paginate(30);
        AppNotification::forAdmin()->unread()->update(['read_at' => now()]);

        return view('admin.notifications', compact('items'));
    }
}
