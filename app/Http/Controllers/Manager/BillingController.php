<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Plan;
use App\Services\Payment\ShatajPayService;
use App\Services\Saas\SubscriptionService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Throwable;

/** Branch: current plan, usage, upgrade/downgrade, renew, cancel, invoices & payment. */
class BillingController extends Controller
{
    public function __construct(protected SubscriptionService $service) {}

    public function index()
    {
        $branch = Tenancy::branch();
        $sub = $branch->subscription?->load(['plan', 'nextPlan']);
        $plan = $branch->effectivePlan();

        $usage = collect(Plan::LIMITS)->map(fn ($label, $key) => [
            'label' => $label,
            'used' => $branch->usage($key),
            'limit' => $plan->limit($key),
        ]);

        return view('manager.billing.index', [
            'branch' => $branch,
            'sub' => $sub,
            'plan' => $plan,
            'usage' => $usage,
            'invoices' => $branch->invoices()->with('plan')->paginate(10),
            'openInvoice' => $branch->invoices()->whereIn('status', ['unpaid', 'failed', 'pending_verification'])->first(),
            'history' => ActivityLog::where('branch_id', $branch->id)->where(fn ($q) => $q->where('action', 'like', 'subscription.%')->orWhere('action', 'like', 'invoice.%'))->latest('created_at')->take(15)->get(),
        ]);
    }

    public function plans()
    {
        $branch = Tenancy::branch();

        return view('manager.billing.plans', [
            'branch' => $branch,
            'sub' => $branch->subscription,
            'plans' => Plan::availableFor($branch)->get(),
        ]);
    }

    public function change(Request $request)
    {
        $branch = Tenancy::branch();
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'cycle' => ['required', Rule::in(array_keys(Plan::CYCLES))],
            'trial' => ['nullable', 'boolean'],
        ]);

        try {
            $result = $this->service->choose($branch, Plan::findOrFail($data['plan_id']), $data['cycle'], (bool) ($data['trial'] ?? false));
        } catch (InvalidArgumentException $e) {
            return back()->with('warning', $e->getMessage());
        }

        if ($result['invoice'] && $result['invoice']->isPayable()) {
            return redirect()->route('manager.billing.invoice', $result['invoice'])->with('success', $result['message']);
        }

        return redirect()->route('manager.billing')->with('success', $result['message']);
    }

    public function renew()
    {
        $invoice = $this->service->renewalInvoice(Tenancy::branch());
        if (! $invoice) {
            return back()->with('warning', 'এই প্ল্যানের রিনিউয়াল প্রয়োজন নেই।');
        }

        return redirect()->route('manager.billing.invoice', $invoice)->with('success', "রিনিউয়াল ইনভয়েস {$invoice->number} তৈরি হয়েছে।");
    }

    public function cancel()
    {
        $sub = Tenancy::branch()->subscription;
        abort_unless($sub && $sub->isUsable() && ! $sub->plan->is_free, 400);
        $this->service->cancel($sub);

        return back()->with('success', 'অটো-রিনিউয়াল বন্ধ করা হয়েছে। বর্তমান মেয়াদ শেষ পর্যন্ত সব সুবিধা পাবেন।');
    }

    public function resume()
    {
        $sub = Tenancy::branch()->subscription;
        abort_unless($sub, 400);
        $this->service->resume($sub);

        return back()->with('success', 'অটো-রিনিউয়াল আবার চালু হয়েছে।');
    }

    public function invoice(Invoice $invoice)
    {
        $this->own($invoice);

        return view('manager.billing.invoice', [
            'invoice' => $invoice->load(['plan', 'branch', 'payments']),
            'pay' => OnboardingController::paySettings(),
        ]);
    }

    public function pay(Request $request, Invoice $invoice, ShatajPayService $shatajPay)
    {
        $this->own($invoice);
        if (! $invoice->isPayable()) {
            return back()->with('warning', 'এই ইনভয়েসটি পরিশোধযোগ্য নয়।');
        }

        $method = $request->validate(['method' => ['required', Rule::in(['shatajpay', 'bkash', 'nagad', 'bank'])]])['method'];

        if ($method === 'shatajpay') {
            try {
                return redirect()->away($shatajPay->initiate($invoice));
            } catch (Throwable $e) {
                Log::error('[ShatajPay] invoice initiate failed: '.$e->getMessage(), ['invoice' => $invoice->number]);

                return back()->with('warning', 'ShatajPay এর সাথে সংযোগ করা যায়নি। পরে চেষ্টা করুন অথবা ম্যানুয়াল পেমেন্ট করুন।');
            }
        }

        $data = $request->validate([
            'sender_number' => ['required', 'string', 'max:30'],
            'transaction_id' => ['required', 'string', 'min:4', 'max:60', Rule::unique('invoices', 'transaction_id')->ignore($invoice->id)],
        ], ['transaction_id.unique' => 'এই Transaction ID আগে ব্যবহার হয়েছে।']);

        $this->service->submitManual($invoice, $method, $data['sender_number'], $data['transaction_id']);

        $next = Tenancy::branch()->isOnboarded() ? route('manager.billing') : route('manager.onboarding.store');

        return redirect()->to($next)->with('success', 'পেমেন্ট তথ্য জমা হয়েছে। Super Admin ভেরিফাই করলে প্ল্যান চালু হবে (ততক্ষণ Free লিমিট)।');
    }

    protected function own(Invoice $invoice): void
    {
        abort_unless($invoice->branch_id === Tenancy::branch()->id, 404);
    }
}
