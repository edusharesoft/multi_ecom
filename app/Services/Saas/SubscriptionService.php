<?php

namespace App\Services\Saas;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\Subscription;
use App\Services\Notifier;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * All subscription state changes go through this class:
 * choose plan, trial, invoice, payment applied, upgrade/downgrade, cancel, renew, expiry processing.
 */
class SubscriptionService
{
    public function __construct(protected Notifier $notifier) {}

    public static function periodEnd(CarbonInterface $from, string $cycle): ?CarbonInterface
    {
        return match ($cycle) {
            'monthly' => $from->copy()->addMonthNoOverflow(),
            'yearly' => $from->copy()->addYearNoOverflow(),
            'lifetime' => null,
            default => throw new InvalidArgumentException("Unknown cycle {$cycle}"),
        };
    }

    public function graceDays(): int
    {
        return max(0, (int) Setting::get('grace_days', 3));
    }

    /* ------------------------------------------------------------------ */
    /*  Choosing a plan (onboarding / upgrade / downgrade)                */
    /* ------------------------------------------------------------------ */

    /**
     * @return array{subscription: Subscription, invoice: ?Invoice, message: string}
     */
    public function choose(Branch $branch, Plan $plan, string $cycle, bool $startTrial = false): array
    {
        abort_unless($plan->is_active && ($plan->branch_id === null || $plan->branch_id === $branch->id), 404);
        if (! $plan->offersCycle($cycle)) {
            throw new InvalidArgumentException('This plan is not available for the selected billing cycle.');
        }

        return DB::transaction(function () use ($branch, $plan, $cycle, $startTrial) {
            $sub = $branch->subscription()->lockForUpdate()->first();

            // 1) Free plan: immediate
            if ($plan->is_free) {
                if ($sub && $sub->isUsable() && ! $sub->plan->is_free && $sub->ends_at && $sub->ends_at->isFuture()) {
                    // Paid → Free: keep paid features until period end
                    $sub->update(['next_plan_id' => $plan->id, 'next_cycle' => 'lifetime', 'auto_renew' => false]);
                    $this->log($branch, 'subscription.downgrade_scheduled', "Downgrade to {$plan->name} scheduled for {$sub->ends_at->format('d M Y')}", $sub);

                    return ['subscription' => $sub, 'invoice' => null, 'message' => "আপনার বর্তমান প্ল্যান {$sub->ends_at->format('d M Y')} পর্যন্ত চলবে, তারপর {$plan->name} এ চলে যাবে।"];
                }
                $sub = $this->activate($branch, $plan, 'lifetime', 0, now(), $sub);

                return ['subscription' => $sub, 'invoice' => null, 'message' => "{$plan->name} প্ল্যান চালু হয়েছে।"];
            }

            // 2) Free trial (once per branch)
            if ($startTrial && $cycle !== 'lifetime' && $plan->trial_days > 0 && ! $branch->trial_used_at) {
                $end = now()->addDays($plan->trial_days);
                $attrs = ['plan_id' => $plan->id, 'billing_cycle' => $cycle, 'status' => 'trial', 'amount' => $plan->priceFor($cycle),
                    'starts_at' => now(), 'ends_at' => $end, 'trial_ends_at' => $end, 'grace_ends_at' => null,
                    'cancelled_at' => null, 'auto_renew' => true, 'next_plan_id' => null, 'next_cycle' => null, 'reminders_sent' => null];
                $sub = $sub ? tap($sub)->update($attrs) : $branch->subscription()->create($attrs);
                $branch->forceFill(['trial_used_at' => now()])->save();
                $this->log($branch, 'subscription.trial_started', "{$plan->trial_days}-day trial of {$plan->name} started", $sub);
                $this->notifier->branch($branch, 'subscription.trial', "{$plan->name} ফ্রি ট্রায়াল শুরু", "ট্রায়াল শেষ হবে {$end->format('d M Y')}", route('manager.billing'), 'success');

                return ['subscription' => $sub, 'invoice' => null, 'message' => "{$plan->trial_days} দিনের ফ্রি ট্রায়াল শুরু হয়েছে।"];
            }

            // 3) Paid: downgrade to a cheaper paid plan is scheduled at period end
            $current = $sub?->plan;
            $isDowngrade = $sub && $sub->status === 'active' && $current && ! $current->is_free
                && self::monthlyValue($plan, $cycle) < self::monthlyValue($current, $sub->billing_cycle) && $sub->ends_at?->isFuture();
            if ($isDowngrade) {
                $sub->update(['next_plan_id' => $plan->id, 'next_cycle' => $cycle]);
                $this->log($branch, 'subscription.downgrade_scheduled', "Downgrade to {$plan->name} ({$cycle}) scheduled", $sub);

                return ['subscription' => $sub, 'invoice' => null, 'message' => "{$plan->name} এ ডাউনগ্রেড {$sub->ends_at->format('d M Y')} থেকে কার্যকর হবে।"];
            }

            // 4) New / upgrade: create invoice; upgrade gets credit for unused days
            $type = ! $sub || ! $sub->isUsable() || $sub->plan->is_free || $sub->status === 'trial' ? 'new' : ($sub->plan_id === $plan->id && $sub->billing_cycle === $cycle ? 'renewal' : 'upgrade');
            $credit = $type === 'upgrade' ? $this->unusedCredit($sub) : 0.0;
            $invoice = $this->createInvoice($branch, $plan, $cycle, $type, $credit);

            // (a fully credit-covered invoice was already applied and may have created the subscription)
            $sub ??= $branch->subscription()->first();
            if (! $sub) {
                // first time: keep a pending subscription (Free limits apply until paid)
                $sub = $branch->subscription()->create([
                    'plan_id' => $plan->id, 'billing_cycle' => $cycle, 'status' => 'pending', 'amount' => $invoice->total,
                ]);
            } elseif ($sub->status === 'pending') {
                // still unpaid: follow the latest choice
                $sub->update(['plan_id' => $plan->id, 'billing_cycle' => $cycle, 'amount' => $invoice->total]);
            }

            return ['subscription' => $sub, 'invoice' => $invoice, 'message' => "ইনভয়েস {$invoice->number} তৈরি হয়েছে, পেমেন্ট সম্পন্ন করুন।"];
        });
    }

    /** Price normalised to one month, so plans on different cycles can be compared. */
    public static function monthlyValue(Plan $plan, string $cycle): float
    {
        return match ($cycle) {
            'yearly' => $plan->priceFor('yearly') / 12,
            'lifetime' => $plan->priceFor('lifetime') / 60,   // treat lifetime as ~5 years
            default => $plan->priceFor('monthly'),
        };
    }

    /** Value of the unused part of the current paid period (for upgrades). */
    public function unusedCredit(?Subscription $sub): float
    {
        if (! $sub || $sub->status !== 'active' || ! $sub->starts_at || ! $sub->ends_at || $sub->ends_at->isPast() || $sub->amount <= 0) {
            return 0.0;
        }
        // One billing period ending at ends_at ($sub->amount is the price of one period; starts_at is
        // kept across early renewals, so starts_at..ends_at can span several periods)
        $periodStart = $sub->billing_cycle === 'yearly' ? $sub->ends_at->copy()->subYearNoOverflow() : $sub->ends_at->copy()->subMonthNoOverflow();
        $total = max(1, $periodStart->diffInSeconds($sub->ends_at));
        $left = max(0, now()->diffInSeconds($sub->ends_at));

        return round((float) $sub->amount * $left / $total, 2);
    }

    public function createInvoice(Branch $branch, Plan $plan, string $cycle, string $type = 'new', float $credit = 0): Invoice
    {
        // Re-use an identical open invoice instead of piling up duplicates
        $open = $branch->invoices()->where('plan_id', $plan->id)->where('billing_cycle', $cycle)
            ->whereIn('status', ['unpaid', 'failed'])->first();
        if ($open) {
            return $open;
        }

        $amount = $plan->priceFor($cycle);
        $discount = min($amount, round($credit, 2));
        $sub = $branch->subscription;
        $start = $type === 'renewal' && $sub?->ends_at?->isFuture() ? $sub->ends_at->copy() : now();

        $invoice = $branch->invoices()->create([
            'number' => Invoice::generateNumber(),
            'plan_id' => $plan->id,
            'type' => $type,
            'billing_cycle' => $cycle,
            'amount' => $amount,
            'discount' => $discount,
            'total' => max(0, $amount - $discount),
            'status' => 'unpaid',
            'due_date' => ($type === 'renewal' && $sub?->ends_at ? $sub->ends_at : now()->addDays(3))->toDateString(),
            'period_start' => $start,
            'period_end' => self::periodEnd($start, $cycle),
            'notes' => $discount > 0 ? "Credit for unused days of current plan: ৳ {$discount}" : null,
        ]);

        $this->log($branch, 'invoice.created', "Invoice {$invoice->number} ({$plan->name}, {$cycle}) ৳ {$invoice->total}", $invoice);

        if ($invoice->total <= 0) {       // fully covered by credit
            $this->markInvoicePaid($invoice, 'free', null);
        }

        return $invoice;
    }

    /* ------------------------------------------------------------------ */
    /*  Payments                                                          */
    /* ------------------------------------------------------------------ */

    /** Apply a paid invoice to the subscription (idempotent). */
    public function markInvoicePaid(Invoice $invoice, string $method, ?string $transactionId): void
    {
        DB::transaction(function () use ($invoice, $method, $transactionId) {
            $invoice = Invoice::whereKey($invoice->id)->lockForUpdate()->first();
            if ($invoice->status === 'paid') {
                return;
            }
            $invoice->update([
                'status' => 'paid', 'paid_at' => now(), 'payment_method' => $method,
                'transaction_id' => $transactionId ?? $invoice->transaction_id,
            ]);

            $branch = $invoice->branch;
            $sub = $branch->subscription()->lockForUpdate()->first();
            $plan = $invoice->plan ?? $sub?->plan;

            // Renewal of the same running plan extends from the current end date
            $extend = $invoice->type === 'renewal' && $sub && $sub->plan_id === $plan->id
                && in_array($sub->status, ['active', 'grace', 'trial'], true) && $sub->ends_at;
            // Carry the remaining time over: renewals, scheduled downgrades, and paying for the plan you are trialling
            $trialOfSamePlan = $sub?->status === 'trial' && $sub->plan_id === $plan->id;
            $carry = ($extend || $invoice->type === 'downgrade' || $trialOfSamePlan) && $sub?->ends_at?->isFuture();
            $start = $carry ? $sub->ends_at->copy() : now();

            $sub = $this->activate($branch, $plan, $invoice->billing_cycle, (float) $invoice->amount, $start, $sub, keepStart: $extend);
            $invoice->update(['period_start' => $start, 'period_end' => $sub->ends_at]);

            $this->log($branch, 'invoice.paid', "Invoice {$invoice->number} paid via {$method}", $invoice);
            $this->notifier->branch($branch, 'invoice.paid', 'পেমেন্ট সফল — সাবস্ক্রিপশন চালু',
                "{$plan->name} ({$invoice->billing_cycle}) ".($sub->ends_at ? 'মেয়াদ '.$sub->ends_at->format('d M Y') : 'Lifetime'), route('manager.billing'), 'success');
            $this->notifier->admin('invoice.paid', "Payment received: {$branch->display_name}", "৳ {$invoice->total} — {$invoice->number}", route('admin.invoices.index'), 'success');
        });
    }

    public function markInvoiceFailed(Invoice $invoice, string $reason = ''): void
    {
        if ($invoice->status === 'paid') {
            return;
        }
        $invoice->update(['status' => 'failed']);
        $this->log($invoice->branch, 'invoice.failed', "Payment failed for {$invoice->number}".($reason ? ": {$reason}" : ''), $invoice);
        $this->notifier->branch($invoice->branch, 'payment.failed', 'পেমেন্ট ব্যর্থ হয়েছে', "Invoice {$invoice->number}. আবার চেষ্টা করুন।", route('manager.billing'), 'danger');
        $this->notifier->admin('payment.failed', "Failed payment: {$invoice->branch->display_name}", $invoice->number, route('admin.invoices.index'), 'warning');
    }

    /** Manual payment submitted by the branch (bKash / Nagad / Bank). */
    public function submitManual(Invoice $invoice, string $method, string $sender, string $trx): void
    {
        $invoice->update(['status' => 'pending_verification', 'payment_method' => $method, 'sender_number' => $sender, 'transaction_id' => strtoupper($trx)]);
        $this->log($invoice->branch, 'invoice.manual_submitted', "Manual {$method} payment submitted for {$invoice->number} (TrxID {$trx})", $invoice);
        $this->notifier->admin('payment.pending', "Verify payment: {$invoice->branch->display_name}", "{$method} ৳ {$invoice->total}, TrxID {$trx}", route('admin.invoices.index', ['status' => 'pending_verification']), 'warning');
    }

    /* ------------------------------------------------------------------ */
    /*  State helpers                                                     */
    /* ------------------------------------------------------------------ */

    public function activate(Branch $branch, Plan $plan, string $cycle, float $amount, CarbonInterface $start, ?Subscription $sub = null, bool $keepStart = false): Subscription
    {
        $cycle = $plan->is_free ? 'lifetime' : $cycle;
        $attrs = [
            'plan_id' => $plan->id,
            'billing_cycle' => $cycle,
            'status' => 'active',
            'amount' => $amount,
            'ends_at' => self::periodEnd($start, $cycle),
            'trial_ends_at' => null,
            'grace_ends_at' => null,
            'cancelled_at' => null,
            'auto_renew' => ! $plan->is_free && $cycle !== 'lifetime',
            'next_plan_id' => null,
            'next_cycle' => null,
            'reminders_sent' => null,
        ];
        if (! $keepStart || ! $sub?->starts_at) {
            $attrs['starts_at'] = now();
        }

        $sub = $sub ? tap($sub)->update($attrs) : $branch->subscription()->create($attrs + ['starts_at' => now()]);
        $this->log($branch, 'subscription.activated', "{$plan->name} ({$cycle}) active".($sub->ends_at ? ' until '.$sub->ends_at->format('d M Y') : ''), $sub);

        // Paying clears a suspension caused by non-payment
        if ($branch->isSuspended() && $branch->suspend_reason === 'subscription_expired') {
            $branch->forceFill(['suspended_at' => null, 'suspend_reason' => null])->save();
        }

        return $sub->refresh();
    }

    public function cancel(Subscription $sub): void
    {
        $sub->update(['auto_renew' => false, 'cancelled_at' => now()]);
        $this->log($sub->branch, 'subscription.cancelled', 'Subscription cancelled (runs until end of period)', $sub);
        $this->notifier->admin('subscription.cancelled', "Cancelled: {$sub->branch->display_name}", $sub->plan->name, route('admin.subscriptions.index'), 'warning');
    }

    public function resume(Subscription $sub): void
    {
        $sub->update(['auto_renew' => true, 'cancelled_at' => null]);
        $this->log($sub->branch, 'subscription.resumed', 'Auto-renewal turned back on', $sub);
    }

    /** Renewal invoice for the current plan & cycle. */
    public function renewalInvoice(Branch $branch): ?Invoice
    {
        $sub = $branch->subscription;
        if (! $sub || $sub->plan->is_free || $sub->billing_cycle === 'lifetime') {
            return null;
        }
        $plan = $sub->next_plan_id && $sub->nextPlan ? $sub->nextPlan : $sub->plan;
        if ($plan->is_free) {
            return null;   // scheduled switch to Free happens at period end (processAll), nothing to invoice
        }
        $cycle = $sub->next_cycle ?: $sub->billing_cycle;
        if ($cycle === 'trial') {
            $cycle = 'monthly';
        }
        $type = $sub->status === 'trial' ? 'new' : ($plan->id === $sub->plan_id ? 'renewal' : 'downgrade');

        return $this->createInvoice($branch, $plan, $cycle, $type);
    }

    /* ------------------------------------------------------------------ */
    /*  Daily automation (saas:process)                                   */
    /* ------------------------------------------------------------------ */

    /** @return array<string,int> counters */
    public function processAll(): array
    {
        $stats = ['reminders' => 0, 'invoices' => 0, 'grace' => 0, 'expired' => 0, 'downgraded' => 0, 'suspended' => 0];
        $reminderDays = array_map('intval', array_filter(explode(',', (string) Setting::get('reminder_days', '7,3,1,0')), 'strlen'));

        Subscription::with(['branch', 'plan', 'nextPlan'])->whereIn('status', ['trial', 'active', 'grace'])->chunkById(100, function ($subs) use (&$stats, $reminderDays) {
            foreach ($subs as $sub) {
                $branch = $sub->branch;
                if (! $sub->ends_at || $sub->plan->is_free) {
                    continue;
                }

                // Reminders + auto-renewal invoice
                $days = (int) ceil(now()->diffInDays($sub->ends_at, false));
                if ($sub->status !== 'grace' && $days >= 0 && in_array($days, $reminderDays, true) && $this->markReminder($sub, $days)) {
                    if ($sub->auto_renew && $days <= max($reminderDays)) {
                        if ($this->renewalInvoice($branch)) {
                            $stats['invoices']++;
                        }
                    }
                    $what = $sub->status === 'trial' ? 'ফ্রি ট্রায়াল' : 'সাবস্ক্রিপশন';
                    $msg = $days === 0 ? "আপনার {$what} আজ শেষ হচ্ছে।" : "আপনার {$what} {$days} দিনের মধ্যে শেষ হবে ({$sub->ends_at->format('d M Y')})।";
                    $this->notifier->branch($branch, 'subscription.expiring', $days === 0 ? 'Subscription expires today' : "Your subscription will expire in {$days} days", $msg.' রিনিউ করতে Billing পেজে যান।', route('manager.billing'), 'warning', ['app', 'mail', 'sms']);
                    $stats['reminders']++;
                }

                // Period ended
                if ($sub->status !== 'grace' && $sub->ends_at->isPast()) {
                    // Scheduled downgrade to Free
                    if ($sub->nextPlan?->is_free) {
                        $this->activate($branch, $sub->nextPlan, 'lifetime', 0, now(), $sub);
                        $stats['downgraded']++;

                        continue;
                    }
                    $sub->update(['status' => 'grace', 'grace_ends_at' => now()->addDays($this->graceDays())]);
                    $this->log($branch, 'subscription.grace', "Grace period until {$sub->grace_ends_at->format('d M Y')}", $sub);
                    $this->notifier->branch($branch, 'subscription.grace', 'সাবস্ক্রিপশন মেয়াদ শেষ — Grace Period', "{$sub->grace_ends_at->format('d M Y')} এর মধ্যে পেমেন্ট করুন, নাহলে অ্যাকাউন্ট সীমিত হয়ে যাবে।", route('manager.billing'), 'danger', ['app', 'mail', 'sms']);
                    $stats['grace']++;

                    continue;
                }

                // Grace ended
                if ($sub->status === 'grace' && $sub->grace_ends_at && $sub->grace_ends_at->isPast()) {
                    $stats[$this->expire($sub)]++;
                }
            }
        });

        return $stats;
    }

    /** Apply the configured expiry action. Returns the stats key. */
    public function expire(Subscription $sub): string
    {
        $branch = $sub->branch;
        $action = Setting::get('expiry_action', 'downgrade');   // downgrade | readonly | suspend

        $this->notifier->admin('subscription.expired', "Expired: {$branch->display_name}", "{$sub->plan->name} → {$action}", route('admin.subscriptions.index', ['status' => 'expired']), 'danger');

        if ($action === 'downgrade') {
            $this->activate($branch, Plan::free(), 'lifetime', 0, now(), $sub);
            $this->notifier->branch($branch, 'subscription.downgraded', 'Free প্ল্যানে নামানো হয়েছে', 'পেমেন্ট না পাওয়ায় আপনার অ্যাকাউন্ট Free প্ল্যানে চলছে। যেকোনো সময় আপগ্রেড করুন।', route('manager.billing'), 'danger', ['app', 'mail', 'sms']);

            return 'downgraded';
        }

        $sub->update(['status' => 'expired']);
        $this->log($branch, 'subscription.expired', "Subscription expired ({$action})", $sub);

        if ($action === 'suspend') {
            $branch->forceFill(['suspended_at' => now(), 'suspend_reason' => 'subscription_expired'])->save();
            $this->notifier->branch($branch, 'subscription.suspended', 'অ্যাকাউন্ট স্থগিত', 'সাবস্ক্রিপশন রিনিউ না করায় স্টোর বন্ধ। পেমেন্ট করলে সাথে সাথে চালু হবে।', route('manager.billing'), 'danger', ['mail', 'sms']);

            return 'suspended';
        }

        $this->notifier->branch($branch, 'subscription.readonly', 'অ্যাকাউন্ট Read-only', 'স্টোর চালু আছে কিন্তু নতুন অর্ডার ও এডিট বন্ধ। রিনিউ করুন।', route('manager.billing'), 'danger', ['app', 'mail', 'sms']);

        return 'expired';
    }

    protected function markReminder(Subscription $sub, int $days): bool
    {
        $key = $sub->ends_at->toDateString();
        $sent = $sub->reminders_sent ?? [];
        if (in_array($days, $sent[$key] ?? [], true)) {
            return false;
        }
        $sent = [$key => array_merge($sent[$key] ?? [], [$days])];   // only keep current period
        $sub->update(['reminders_sent' => $sent]);

        return true;
    }

    protected function log(Branch $branch, string $action, string $description, $subject = null): void
    {
        ActivityLog::record($action, $description, $subject, [], $branch->id);
    }
}
