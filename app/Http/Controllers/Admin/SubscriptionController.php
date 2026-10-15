<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Saas\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function __construct(protected SubscriptionService $service) {}

    public function index(Request $request)
    {
        $subs = Subscription::with(['branch', 'plan', 'nextPlan'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('plan'), fn ($q) => $q->where('plan_id', $request->plan))
            ->when($request->filled('expiring'), fn ($q) => $q->whereIn('status', ['active', 'trial'])->whereBetween('ends_at', [now(), now()->addDays((int) $request->expiring)]))
            ->when($request->filled('q'), fn ($q) => $q->whereHas('branch', fn ($b) => $b->where('store_name', 'like', '%'.$request->q.'%')->orWhere('slug', 'like', '%'.$request->q.'%')))
            ->orderByRaw('ends_at is null, ends_at asc')
            ->paginate(25)->withQueryString();

        $counts = Subscription::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return view('admin.subscriptions.index', ['subs' => $subs, 'counts' => $counts, 'plans' => Plan::orderBy('sort')->get()]);
    }

    /** Admin override: set plan / cycle / expiry directly (e.g. custom deal, complimentary extension). */
    public function update(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly', 'lifetime', 'trial'])],
            'status' => ['required', Rule::in(Subscription::STATUSES)],
            'ends_at' => ['nullable', 'date'],
            'auto_renew' => ['nullable', 'boolean'],
        ]);
        $data['auto_renew'] = $request->boolean('auto_renew');
        $data['grace_ends_at'] = $data['status'] === 'grace' ? now()->addDays($this->service->graceDays()) : null;
        if (in_array($data['status'], ['active', 'trial'], true)) {
            $data['reminders_sent'] = null;
        }
        $subscription->update($data);

        $branch = $subscription->branch;
        if ($branch->suspend_reason === 'subscription_expired' && $subscription->isUsable()) {
            $branch->forceFill(['suspended_at' => null, 'suspend_reason' => null])->save();
        }
        ActivityLog::record('subscription.admin_update', "Admin set {$subscription->plan->name} / {$data['status']}".($subscription->ends_at ? ' until '.$subscription->ends_at->format('d M Y') : ''), $subscription, [], $branch->id);

        return back()->with('success', 'Subscription updated.');
    }

    public function extend(Request $request, Subscription $subscription)
    {
        $days = (int) $request->validate(['days' => ['required', 'integer', 'min:1', 'max:3650']])['days'];
        $base = $subscription->ends_at && $subscription->ends_at->isFuture() ? $subscription->ends_at : now();
        $subscription->update([
            'ends_at' => $base->copy()->addDays($days),
            'status' => in_array($subscription->status, ['trial'], true) ? 'trial' : 'active',
            'grace_ends_at' => null, 'reminders_sent' => null,
        ]);
        $branch = $subscription->branch;
        if ($branch->suspend_reason === 'subscription_expired') {
            $branch->forceFill(['suspended_at' => null, 'suspend_reason' => null])->save();
        }
        ActivityLog::record('subscription.extended', "Extended by {$days} days", $subscription, [], $subscription->branch_id);

        return back()->with('success', "Extended by {$days} days.");
    }

    public function run()
    {
        Artisan::call('saas:process');

        return back()->with('success', 'Automation run: '.trim(preg_replace('/\s+/', ' ', Artisan::output())));
    }
}
