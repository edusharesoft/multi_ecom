<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ReportService;
use App\Support\Features;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $branch = Tenancy::branch();
        if (! Tenancy::can('dashboard.view')) {
            return view('manager.welcome');   // staff role without dashboard access
        }

        // statistic cards — cached 2 minutes, dropped as soon as an order of this branch changes
        $stats = Cache::remember('dash:'.$branch->id, 120, function () use ($branch) {
            $orders = $branch->orders();
            $today = [now()->startOfDay(), now()->endOfDay()];   // range instead of whereDate() → (branch_id, created_at) index

            return [
                'products' => $branch->products()->count(),
                'low_stock' => $branch->products()->whereColumn('stock', '<=', 'min_stock')->whereDoesntHave('variants')->count(),
                'orders' => (clone $orders)->count(),
                'pending' => (clone $orders)->whereIn('status', ['new', 'pending'])->count(),
                'to_verify' => (clone $orders)->where('payment_status', 'pending_verification')->count(),
                'completed' => (clone $orders)->whereIn('status', ['delivered', 'completed'])->count(),
                'cancelled' => (clone $orders)->whereIn('status', ['cancelled', 'failed'])->count(),
                'revenue' => (float) (clone $orders)->where('payment_status', 'paid')->sum('total'),
                'today' => (float) (clone $orders)->whereBetween('created_at', $today)->whereNotIn('status', ReportService::VOID)->sum('total'),
                'month' => (float) (clone $orders)->where('created_at', '>=', now()->startOfMonth())->whereNotIn('status', ReportService::VOID)->sum('total'),
                'customers' => $branch->customers()->count(),
                'returns' => Features::ready('return_requests') ? $branch->returns()->whereIn('status', ['requested', 'approved', 'pickup', 'received', 'inspected'])->count() : 0,
            ];
        });

        $years = $this->years($branch);
        $pick = fn (string $key) => in_array((int) $request->query($key), $years, true) ? (int) $request->query($key) : $years[0];
        $yPay = $pick('y_pay');
        $yIe = $pick('y_ie');
        $ySr = $pick('y_sr');

        // year charts — cached 5 minutes per year choice
        $charts = Cache::remember("dash:{$branch->id}:charts:{$yPay}:{$yIe}:{$ySr}", 300, fn () => [
            'payment' => $this->byPayment($branch, $yPay),
            'income' => $this->monthly($branch, $yIe, 'total'),
            'expense' => $this->expenses($branch, $yIe),
            'orders' => $this->monthly($branch, $ySr, 'count'),
        ]);

        // "New Order" list with status filter
        $os = $request->query('os');
        $os = is_string($os) && isset(Order::STATUS_META[$os]) ? $os : null;
        $recent = $branch->orders()->when($os, fn ($q) => $q->where('status', $os))->latest()->take(8)
            ->get(['id', 'branch_id', 'order_number', 'customer_name', 'customer_phone', 'total', 'status', 'source', 'created_at']);

        return view('manager.dashboard', compact('branch', 'stats', 'charts', 'recent', 'years', 'yPay', 'yIe', 'ySr', 'os'));
    }

    /** years that have orders (newest first, at least the current year, max 6) */
    protected function years($branch): array
    {
        $first = $branch->orders()->min('created_at');
        $from = $first ? max((int) substr((string) $first, 0, 4), (int) now()->year - 5) : (int) now()->year;

        return range((int) now()->year, min($from, (int) now()->year));
    }

    /** 12 values (Jan…Dec): order count or sales total, cancelled / failed excluded */
    protected function monthly($branch, int $year, string $what): array
    {
        $rows = $branch->orders()->whereNotIn('status', ReportService::VOID)
            ->whereBetween('created_at', [now()->setDate($year, 1, 1)->startOfDay(), now()->setDate($year, 12, 31)->endOfDay()])
            ->selectRaw('MONTH(created_at) m, '.($what === 'count' ? 'COUNT(*)' : 'SUM(total)').' v')->groupBy('m')->pluck('v', 'm');

        return array_map(fn ($m) => round((float) ($rows[$m] ?? 0), 2), range(1, 12));
    }

    /** 12 monthly expense totals from the accounting vouchers (Phase 4) */
    protected function expenses($branch, int $year): array
    {
        if (! Features::ready('vouchers')) {
            return array_fill(0, 12, 0);
        }
        $rows = $branch->vouchers()->where('kind', 'expense')->whereYear('voucher_date', $year)
            ->selectRaw('MONTH(voucher_date) m, SUM(amount) v')->groupBy('m')->pluck('v', 'm');

        return array_map(fn ($m) => round((float) ($rows[$m] ?? 0), 2), range(1, 12));
    }

    /** sales total per payment method for the year */
    protected function byPayment($branch, int $year): array
    {
        return $branch->orders()->whereNotIn('status', ReportService::VOID)
            ->whereBetween('created_at', [now()->setDate($year, 1, 1)->startOfDay(), now()->setDate($year, 12, 31)->endOfDay()])
            ->selectRaw('payment_method k, SUM(total) v, COUNT(*) c')->groupBy('k')->orderByDesc('v')->get()
            ->map(fn ($r) => ['label' => Order::METHOD_LABELS[$r->k] ?? ucfirst((string) $r->k), 'total' => round((float) $r->v, 2), 'count' => (int) $r->c])
            ->all();
    }
}
