<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\Tenancy;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::where('branch_id', Tenancy::branch()->id)
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('description', 'like', '%'.$request->q.'%')->orWhere('actor_name', 'like', '%'.$request->q.'%')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', $request->input('action').'%'))
            ->latest('created_at')->paginate(30)->withQueryString();

        return view('manager.activity', compact('logs'));
    }
}
