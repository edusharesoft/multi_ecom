<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;

/** SaaS owner dashboard. */
class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $paidSubs = Subscription::with('plan')->where('status', 'active')->whereHas('plan', fn ($q) => $q->where('is_free', false))->get();
        // MRR: normalise every paid active subscription to a monthly value
        $mrr = $paidSubs->sum(fn ($s) => match ($s->billing_cycle) {
            'yearly' => (float) $s->amount / 12,
            'monthly' => (float) $s->amount,
            default => 0,
        });

        $paidInvoices = Invoice::where('status', 'paid');
        $stats = [
            'branches' => Branch::count(),
            'active' => Subscription::where('status', 'active')->count(),
            'trial' => Subscription::where('status', 'trial')->count(),
            'grace' => Subscription::where('status', 'grace')->count(),
            'expired' => Subscription::where('status', 'expired')->count(),
            'pending' => Subscription::where('status', 'pending')->count(),
            'cancelled' => Subscription::whereNotNull('cancelled_at')->count(),
            'suspended' => Branch::whereNotNull('suspended_at')->count(),
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'month_revenue' => (float) (clone $paidInvoices)->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
            'year_revenue' => (float) (clone $paidInvoices)->where('paid_at', '>=', now()->startOfYear())->sum('total'),
            'pending_payments' => Invoice::where('status', 'pending_verification')->count(),
            'pending_amount' => (float) Invoice::whereIn('status', ['unpaid', 'pending_verification'])->sum('total'),
            'failed_payments' => Invoice::where('status', 'failed')->where('updated_at', '>=', now()->subDays(30))->count(),
            'new_registrations' => Branch::where('created_at', '>=', now()->startOfMonth())->count(),
            'renewals' => Invoice::where('status', 'paid')->where('type', 'renewal')->where('paid_at', '>=', now()->startOfMonth())->count(),
            'gmv_month' => (float) Order::where('created_at', '>=', now()->startOfMonth())->where('status', '!=', 'cancelled')->sum('total'),
            'commission_month' => (float) Order::where('created_at', '>=', now()->startOfMonth())->where('status', '!=', 'cancelled')->sum('commission_amount'),
        ];

        // year charts (Phase 11 dashboard)
        $first = Branch::min('created_at');
        $from = $first ? max((int) substr((string) $first, 0, 4), (int) now()->year - 5) : (int) now()->year;
        $years = range((int) now()->year, min($from, (int) now()->year));
        $pick = fn (string $key) => in_array((int) $request->query($key), $years, true) ? (int) $request->query($key) : $years[0];
        $yIe = $pick('y_ie');
        $ySr = $pick('y_sr');
        $range = fn (int $y) => [now()->setDate($y, 1, 1)->startOfDay(), now()->setDate($y, 12, 31)->endOfDay()];
        $fill = fn ($rows) => array_map(fn ($m) => round((float) ($rows[$m] ?? 0), 2), range(1, 12));

        $charts = \Illuminate\Support\Facades\Cache::remember("admin_dash:charts:{$yIe}:{$ySr}", 300, fn () => [
            'income' => $fill(Invoice::where('status', 'paid')->whereBetween('paid_at', $range($yIe))
                ->selectRaw('MONTH(paid_at) m, SUM(total) v')->groupBy('m')->pluck('v', 'm')),
            'commission' => $fill(Order::whereNotIn('status', \App\Services\ReportService::VOID)->whereBetween('created_at', $range($yIe))
                ->selectRaw('MONTH(created_at) m, SUM(commission_amount) v')->groupBy('m')->pluck('v', 'm')),
            'orders' => $fill(Order::whereNotIn('status', \App\Services\ReportService::VOID)->whereBetween('created_at', $range($ySr))
                ->selectRaw('MONTH(created_at) m, COUNT(*) v')->groupBy('m')->pluck('v', 'm')),
            'signups' => $fill(Branch::whereBetween('created_at', $range($ySr))
                ->selectRaw('MONTH(created_at) m, COUNT(*) v')->groupBy('m')->pluck('v', 'm')),
        ]);

        // "New Order" across all branches, with status filter
        $os = $request->query('os');
        $os = is_string($os) && isset(Order::STATUS_META[$os]) ? $os : null;
        $recentOrders = Order::with('branch:id,name,store_name,slug')->when($os, fn ($q) => $q->where('status', $os))->latest()->take(8)
            ->get(['id', 'branch_id', 'order_number', 'customer_name', 'total', 'status', 'source', 'created_at']);

        $popularPlans = Plan::withCount(['subscriptions as subs' => fn ($q) => $q->whereIn('status', ['active', 'trial', 'grace'])])
            ->orderByDesc('subs')->get();

        $expiring = Subscription::with(['branch', 'plan'])->whereIn('status', ['active', 'trial'])
            ->whereBetween('ends_at', [now(), now()->addDays(7)])->orderBy('ends_at')->take(8)->get();
        $pendingInvoices = Invoice::with(['branch', 'plan'])->where('status', 'pending_verification')->latest()->take(6)->get();
        $newBranches = Branch::with('subscription.plan')->latest()->take(6)->get();

        return view('admin.dashboard', compact('stats', 'charts', 'years', 'yIe', 'ySr', 'os', 'recentOrders', 'popularPlans', 'expiring', 'pendingInvoices', 'newBranches'));
    }
}
