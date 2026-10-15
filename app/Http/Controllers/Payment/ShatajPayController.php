<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payment\ShatajPayService;
use App\Services\Saas\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * ShatajPay callback for both customer orders (storefront) and SaaS subscription invoices (branch billing).
 *
 * ShatajPay sends the customer back to ONE callback URL (set in the ShatajPay merchant panel):
 *     {APP_URL}/gateway/payment/callback?status=Complete|Failed&orderId=...
 */
class ShatajPayController extends Controller
{
    public function __construct(protected ShatajPayService $shatajPay, protected SubscriptionService $subscriptions) {}

    /** Browser callback (GET or POST). */
    public function callback(Request $request)
    {
        $r = $this->process($request);
        $payment = $r['payment'];

        if (! $payment) {
            return redirect()->to(url('/'))->with('warning', 'পেমেন্ট তথ্য পাওয়া যায়নি।');
        }

        $text = match ($r['result']) {
            'success' => null,
            'cancelled' => 'পেমেন্ট বাতিল করা হয়েছে।',
            default => 'পেমেন্ট ব্যর্থ হয়েছে। আবার চেষ্টা করুন অথবা অন্য পদ্ধতি বেছে নিন।',
        };

        if ($invoice = $payment->invoice) {
            return $invoice->status === 'paid'
                ? redirect()->route('manager.billing')->with('success', "পেমেন্ট সফল! ইনভয়েস {$invoice->number} পরিশোধিত।")
                : redirect()->route('manager.billing')->with('warning', $text ?? 'পেমেন্ট সম্পন্ন হয়নি।');
        }

        $order = $payment->order;
        $branch = $order->branch;

        if ($order->isPaid()) {
            return redirect()->to($this->signed('store.order.success', $branch, $order))->with('success', 'পেমেন্ট সফল হয়েছে! ধন্যবাদ।');
        }

        return redirect()->to($this->signed('store.payment', $branch, $order))->with('warning', $text ?? 'পেমেন্ট সম্পন্ন হয়নি।');
    }

    /** Same callback, answered as JSON (if ShatajPay also calls it server-to-server). */
    public function ipn(Request $request)
    {
        $r = $this->process($request);
        $p = $r['payment'];

        return response()->json([
            'received' => (bool) $p,
            'paid' => $p ? ($p->invoice ? $p->invoice->status === 'paid' : $p->order->isPaid()) : false,
        ], $p ? 200 : 404);
    }

    /** Built-in test gateway page (mode = fake). */
    public function fake(string $reference)
    {
        $payment = Payment::where('reference', $reference)->with(['order.branch', 'invoice.branch'])->firstOrFail();
        abort_unless(data_get($payment->payload, 'mode') === 'fake', 404);

        return view('store.shatajpay-test', [
            'payment' => $payment,
            'merchant' => $payment->invoice ? \App\Models\Setting::siteName().' (Subscription)' : $payment->order->branch->display_name,
            'successUrl' => URL::temporarySignedRoute('shatajpay.callback', now()->addMinutes(30), ['status' => 'Complete', 'orderId' => $reference]),
            'failUrl' => route('shatajpay.callback', ['status' => 'Failed', 'orderId' => $reference]),
            'cancelUrl' => route('shatajpay.callback', ['status' => 'Cancel', 'orderId' => $reference]),
        ]);
    }

    /** Update payment + order / invoice (idempotent, row-locked). */
    protected function process(Request $request): array
    {
        $r = $this->shatajPay->readCallback($request);
        $payment = $r['payment'];
        if (! $payment) {
            return $r;
        }

        DB::transaction(function () use ($payment, $r) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if ($payment->status === 'success') {
                return;   // already completed, a repeated callback changes nothing
            }

            if ($payment->invoice_id) {
                $payment->update(['status' => $r['result']]);
                if ($r['result'] === 'success') {
                    $this->subscriptions->markInvoicePaid($payment->invoice, 'shatajpay', $r['transaction_id']);
                } elseif ($r['result'] === 'failed') {
                    $this->subscriptions->markInvoiceFailed($payment->invoice, $r['message']);
                }

                return;
            }

            $order = $payment->order()->lockForUpdate()->first();
            if ($order->isPaid()) {
                return;
            }
            $payment->update(['status' => $r['result']]);
            if ($r['result'] === 'success') {
                $order->markPaid($r['transaction_id']);
            } elseif ($r['result'] === 'failed') {
                $order->update(['payment_status' => 'failed']);
            }
        });

        $payment->refresh()->load(['order.branch', 'invoice']);

        return $r;
    }

    protected function signed(string $route, $branch, $order): string
    {
        return URL::temporarySignedRoute($route, now()->addHours(2), [$branch, $order]);
    }
}
