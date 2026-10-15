<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

/** Platform commission earned from branch orders (based on each plan's commission %). */
class CommissionController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        $rows = Order::with('branch')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('branch_id, count(*) as orders, sum(total) as sales, sum(commission_amount) as commission, sum(case when payment_status = ? then commission_amount else 0 end) as paid_commission', ['paid'])
            ->groupBy('branch_id')->orderByDesc('commission')->get();

        return view('admin.commission', ['rows' => $rows, 'from' => $from, 'to' => $to]);
    }
}
