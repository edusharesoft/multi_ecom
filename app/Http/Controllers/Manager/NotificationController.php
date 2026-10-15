<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Support\Tenancy;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = AppNotification::where('branch_id', Tenancy::branch()->id)->latest()->paginate(25);

        return view('manager.notifications', compact('notifications'));
    }

    public function readAll()
    {
        AppNotification::where('branch_id', Tenancy::branch()->id)->unread()->update(['read_at' => now()]);

        return back();
    }
}
