<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Services\OrderService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function index(Request $request)
    {
        $branch = Tenancy::branch();
        $orders = $branch->orders()->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('payment'), fn ($q) => $q->where('payment_status', $request->payment))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
            ->when(\App\Support\Dates::start($request->from), fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when(\App\Support\Dates::end($request->to), fn ($q, $d) => $q->where('created_at', '<=', $d))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('order_number', 'like', '%'.$request->q.'%')
                ->orWhere('customer_name', 'like', '%'.$request->q.'%')
                ->orWhere('customer_phone', 'like', '%'.$request->q.'%')
                ->orWhere('transaction_id', 'like', '%'.$request->q.'%')))
            ->latest()->paginate(20)->withQueryString();

        $counts = $branch->orders()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return view('manager.orders.index', compact('orders', 'counts'));
    }

    public function show(Order $order)
    {
        $this->own($order);

        return view('manager.orders.show', ['order' => $order->load(['items.product', 'payments', 'histories', 'customer', 'returns'])]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->own($order);
        $data = $request->validate([
            'status' => ['required', Rule::in(Order::STATUSES)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        try {
            $this->orders->changeStatus($order, $data['status'], $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', 'অর্ডার স্ট্যাটাস আপডেট হয়েছে: '.Order::STATUS_META[$data['status']][0]);
    }

    /** Bulk status change from the list. */
    public function bulkStatus(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'status' => ['required', Rule::in(Order::STATUSES)],
        ]);
        $orders = Tenancy::branch()->orders()->whereIn('id', $data['ids'])->get();
        $done = 0;
        foreach ($orders as $order) {
            try {
                $this->orders->changeStatus($order, $data['status'], 'Bulk update');
                $done++;
            } catch (RuntimeException) {
                // skip cancelled / failed orders
            }
        }

        return back()->with('success', $done.' টি অর্ডার আপডেট হয়েছে।'.($done < $orders->count() ? ' (বাতিল অর্ডারগুলো বাদ দেওয়া হয়েছে)' : ''));
    }

    /** Verify / reject manual bKash-Nagad payments, or mark COD collected. */
    public function updatePayment(Request $request, Order $order)
    {
        $this->own($order);
        $action = $request->validate(['action' => ['required', Rule::in(['approve', 'reject', 'collected'])]])['action'];

        DB::transaction(function () use ($order, $action) {
            $payment = $order->payments()->where('status', 'initiated')->first();
            if ($action === 'reject') {
                $order->update(['payment_status' => 'failed']);
                $payment?->update(['status' => 'failed']);
                $order->addHistory($order->status, 'Payment rejected');

                return;
            }
            $payment?->update(['status' => 'success']);
            if ($action === 'collected' && ! $payment && ! $order->isPaid()) {
                $order->payments()->create([
                    'gateway' => 'cod', 'reference' => $order->order_number.'-COD', 'amount' => $order->payable, 'status' => 'success',
                ]);
            }
            $order->markPaid();
            $order->addHistory($order->status, $action === 'collected' ? 'Cash collected' : 'Payment verified');
        });
        ActivityLog::record('order.payment', "Order {$order->order_number} payment: {$action}", $order);

        return back()->with('success', 'পেমেন্ট স্ট্যাটাস আপডেট হয়েছে।');
    }

    public function refund(Request $request, Order $order)
    {
        $this->own($order);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'method' => ['required', Rule::in(['wallet', 'original', 'cash', 'bkash', 'nagad'])],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        try {
            $this->orders->refund($order, (float) $data['amount'], $data['method'], $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', 'রিফান্ড রেকর্ড হয়েছে।');
    }

    /** invoice | packing | label */
    public function print(Order $order, string $type)
    {
        $this->own($order);
        abort_unless(in_array($type, ['invoice', 'packing', 'label'], true), 404);

        return view('manager.orders.print', ['order' => $order->load(['items', 'branch']), 'type' => $type]);
    }

    protected function own(Order $order): void
    {
        abort_unless($order->branch_id === Tenancy::branch()->id, 403);
    }
}
