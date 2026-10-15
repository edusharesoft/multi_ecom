<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Services\OrderService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/** Return request → approval → pickup → received → inspection → refund / replacement / exchange */
class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $returns = Tenancy::branch()->returns()->with(['order', 'customer'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(25)->withQueryString();

        return view('manager.returns.index', compact('returns'));
    }

    public function show(ReturnRequest $return)
    {
        $this->own($return);

        return view('manager.returns.show', ['return' => $return->load(['order.items', 'order.customer', 'customer'])]);
    }

    public function update(Request $request, ReturnRequest $return, OrderService $orders)
    {
        $this->own($return);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(ReturnRequest::STATUSES))],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'refund_method' => ['nullable', Rule::in(['wallet', 'original', 'cash', 'bkash', 'nagad'])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'restock' => ['nullable', 'boolean'],
        ]);
        if (! $return->isOpen()) {
            return back()->with('warning', 'এই রিটার্ন আগেই নিষ্পত্তি হয়েছে।');
        }
        if (in_array($data['status'], ['approved', 'rejected', 'refunded'], true) && ! Tenancy::can('returns.approve')) {
            abort(403);
        }
        $flow = array_keys(\App\Models\ReturnRequest::STATUSES);   // requested → approved → pickup → received → inspected → final states
        $finalFrom = array_search('refunded', $flow, true);
        $cur = array_search($return->status, $flow, true);
        $new = array_search($data['status'], $flow, true);
        if ($new < $finalFrom && $new <= $cur) {
            return back()->with('warning', 'রিটার্ন ওয়ার্কফ্লো পেছনে নেওয়া যায় না।');
        }
        if ($request->has('restock')) {
            $return->restock = $request->boolean('restock');
        }

        try {
            $orders->processReturn($return, $data['status'], $data);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', 'রিটার্ন স্ট্যাটাস: '.ReturnRequest::STATUSES[$data['status']][0]);
    }

    protected function own(ReturnRequest $return): void
    {
        abort_unless($return->branch_id === Tenancy::branch()->id, 404);
    }
}
