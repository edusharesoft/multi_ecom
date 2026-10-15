<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use Illuminate\Http\Request;

/** All orders across all branches (read-only monitoring; branches process their own orders). */
class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('branch')->withCount('items')
            ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->branch))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('payment'), fn ($q) => $q->where('payment_status', $request->payment))
            ->when($request->filled('method'), fn ($q) => $q->where('payment_method', $request->input('method')))
            ->when(\App\Support\Dates::start($request->from), fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when(\App\Support\Dates::end($request->to), fn ($q, $d) => $q->where('created_at', '<=', $d))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('order_number', 'like', '%'.$request->q.'%')
                ->orWhere('customer_phone', 'like', '%'.$request->q.'%')
                ->orWhere('transaction_id', 'like', '%'.$request->q.'%')))
            ->latest();

        $total = (float) (clone $orders)->sum('total');

        return view('admin.orders.index', [
            'orders' => $orders->paginate(25)->withQueryString(),
            'total' => $total,
            'branches' => Branch::orderBy('store_name')->get(['id', 'store_name', 'name']),
        ]);
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load(['branch', 'items', 'payments'])]);
    }
}
