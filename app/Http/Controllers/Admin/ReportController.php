<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\ReportService;
use App\Support\Csv;
use Illuminate\Http\Request;

/** Platform-level (SaaS) reports for the Super Admin. */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = ReportService::range($request->from, $request->to);
        if (! $request->filled('from')) {
            $from = now()->subMonths(11)->startOfMonth();
        }

        // subscription revenue per month
        $revenue = Invoice::where('status', 'paid')->whereBetween('paid_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') p, count(*) c, sum(total) amt")->groupBy('p')->orderBy('p')->get()->keyBy('p');
        $gmv = Order::whereNotIn('status', ReportService::VOID)->whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') p, count(*) c, sum(total) amt, sum(commission_amount) comm")->groupBy('p')->orderBy('p')->get()->keyBy('p');
        $signups = Branch::whereBetween('created_at', [$from, $to])->selectRaw("DATE_FORMAT(created_at, '%Y-%m') p, count(*) c")->groupBy('p')->pluck('c', 'p');

        $months = collect();
        for ($m = $from->copy()->startOfMonth(); $m->lte($to); $m->addMonthNoOverflow()) {
            $k = $m->format('Y-m');
            $months->push([
                'month' => $k, 'invoices' => (int) ($revenue[$k]->c ?? 0), 'subscription_revenue' => (float) ($revenue[$k]->amt ?? 0),
                'orders' => (int) ($gmv[$k]->c ?? 0), 'gmv' => (float) ($gmv[$k]->amt ?? 0), 'commission' => (float) ($gmv[$k]->comm ?? 0),
                'new_branches' => (int) ($signups[$k] ?? 0),
            ]);
        }

        if ($request->query('export') === 'csv') {
            return Csv::download('platform-report', ['Month', 'Paid invoices', 'Subscription revenue', 'Orders', 'GMV', 'Commission', 'New branches'], $months);
        }

        $active = Subscription::with('plan')->whereIn('status', ['active', 'grace'])->get();
        $mrr = round($active->sum(fn ($s) => match ($s->billing_cycle) {
            'yearly' => (float) $s->amount / 12, 'lifetime' => 0, default => (float) $s->amount,
        }), 2);

        $byPlan = Subscription::selectRaw('plan_id, status, count(*) c')->groupBy('plan_id', 'status')->get()->groupBy('plan_id');

        $leaders = Order::with('branch:id,name,slug')->whereNotIn('status', ReportService::VOID)->whereBetween('created_at', [$from, $to])
            ->selectRaw('branch_id, count(*) orders, sum(total) gmv, sum(commission_amount) commission, sum(refunded_amount) refunded')
            ->groupBy('branch_id')->orderByDesc('gmv')->limit(20)->get();

        return view('admin.reports', [
            'from' => $from, 'to' => $to, 'months' => $months, 'mrr' => $mrr, 'arr' => round($mrr * 12, 2),
            'plans' => Plan::orderBy('sort')->get(), 'byPlan' => $byPlan, 'leaders' => $leaders,
            'totals' => [
                'branches' => Branch::count(), 'active' => $active->count(),
                'trial' => Subscription::where('status', 'trial')->count(),
                'expired' => Subscription::whereIn('status', ['expired', 'cancelled'])->count(),
                'pending_invoices' => Invoice::where('status', 'pending_verification')->count(),
                'unpaid_amount' => (float) Invoice::where('status', 'unpaid')->sum('total'),
            ],
        ]);
    }
}
