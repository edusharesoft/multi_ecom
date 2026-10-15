<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Plan;
use App\Services\Saas\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function __construct(protected SubscriptionService $service) {}

    public function index(Request $request)
    {
        $invoices = Invoice::with(['branch', 'plan'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch_id', $request->branch))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('number', 'like', '%'.$request->q.'%')->orWhere('transaction_id', 'like', '%'.$request->q.'%')))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'branches' => Branch::orderBy('store_name')->get(['id', 'store_name', 'name']),
            'plans' => Plan::orderBy('sort')->get(),
            'pendingTotal' => (float) Invoice::whereIn('status', ['unpaid', 'pending_verification'])->sum('total'),
        ]);
    }

    public function show(Invoice $invoice)
    {
        return view('manager.billing.invoice', ['invoice' => $invoice->load(['plan', 'branch', 'payments']), 'pay' => [], 'adminView' => true]);
    }

    /** Admin issues an invoice (custom deal, lifetime plan, etc.) */
    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $branch = Branch::findOrFail($data['branch_id']);
        $plan = Plan::findOrFail($data['plan_id']);
        $invoice = $this->service->createInvoice($branch, $plan, $data['billing_cycle'], 'custom');
        if (isset($data['amount']) && $invoice->status !== 'paid') {
            $invoice->update(['amount' => $data['amount'], 'discount' => 0, 'total' => $data['amount']]);
        }

        return back()->with('success', "Invoice {$invoice->number} issued to {$branch->display_name}.");
    }

    public function approve(Invoice $invoice)
    {
        $this->service->markInvoicePaid($invoice, $invoice->payment_method ?: 'manual', $invoice->transaction_id);

        return back()->with('success', "{$invoice->number} marked paid — subscription activated.");
    }

    public function reject(Request $request, Invoice $invoice)
    {
        $this->service->markInvoiceFailed($invoice, (string) $request->input('reason'));

        return back()->with('success', "{$invoice->number} rejected.");
    }

    public function cancel(Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 400);
        $invoice->update(['status' => 'cancelled']);
        ActivityLog::record('invoice.cancelled', "Invoice {$invoice->number} cancelled by admin", $invoice, [], $invoice->branch_id);

        return back()->with('success', 'Invoice cancelled.');
    }
}
