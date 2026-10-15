<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use Illuminate\Http\Request;

/** Platform-wide SMS / email log + SMS usage per branch this month. */
class MessageController extends Controller
{
    public function index(Request $request)
    {
        $logs = MessageLog::with('branch:id,name,slug')
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->channel))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('to', 'like', '%'.$request->q.'%')->orWhere('body', 'like', '%'.$request->q.'%')))
            ->latest('id')->paginate(50)->withQueryString();

        $usage = MessageLog::with('branch:id,name,slug')->where('channel', 'sms')->where('status', 'sent')->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('branch_id, count(*) msgs, sum(segments) parts')->groupBy('branch_id')->orderByDesc('parts')->get();

        return view('admin.messages', ['logs' => $logs, 'usage' => $usage]);
    }
}
