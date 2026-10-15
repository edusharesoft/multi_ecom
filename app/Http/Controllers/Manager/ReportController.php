<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Supplier;
use App\Services\ReportService;
use App\Support\Csv;
use App\Support\Tenancy;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reports) {}

    public function index(Request $request)
    {
        $b = Tenancy::branch();
        [$from, $to] = ReportService::range(null, null);
        $today = ReportService::range(now()->toDateString(), now()->toDateString());

        return view('manager.reports.index', [
            'month' => $this->reports->salesSummary($b, $from, $to),
            'today' => $this->reports->salesSummary($b, ...$today),
            'trend' => $this->reports->salesByPeriod($b, now()->subDays(29)->startOfDay(), now()->endOfDay()),
            'hasAccounting' => $b->hasFeature('accounting'),
            'pl' => $b->hasFeature('accounting') ? $this->reports->profitLoss($b, $from, $to) : null,
        ]);
    }

    public function sales(Request $request)
    {
        $b = Tenancy::branch();
        [$from, $to] = ReportService::range($request->from, $request->to);
        $group = $request->query('group') === 'month' ? 'month' : 'day';
        $rows = $this->reports->salesByPeriod($b, $from, $to, $group);

        if ($request->query('export') === 'csv') {
            return Csv::download('sales-report', ['Period', 'Orders', 'Subtotal', 'Discount', 'Shipping', 'Tax', 'Total', 'Refunded', 'Net'], $rows);
        }

        return view('manager.reports.sales', [
            'from' => $from, 'to' => $to, 'group' => $group, 'rows' => $rows,
            'summary' => $this->reports->salesSummary($b, $from, $to),
            'byMethod' => $this->reports->salesBy($b, $from, $to, 'payment_method'),
            'bySource' => $this->reports->salesBy($b, $from, $to, 'source'),
            'byStaff' => $this->reports->salesBy($b, $from, $to, 'staff_name'),
            'byStatus' => $this->reports->salesBy($b, $from, $to, 'status'),
        ]);
    }

    public function products(Request $request)
    {
        $b = Tenancy::branch();
        [$from, $to] = ReportService::range($request->from, $request->to);
        $rows = $this->reports->products($b, $from, $to, $request->integer('category') ?: null, (string) $request->query('sort', 'revenue'));

        if ($request->query('export') === 'csv') {
            return Csv::download('product-sales', ['Product ID', 'Product', 'SKU', 'Category', 'Qty', 'Revenue', 'Orders', 'Cost', 'Profit', 'Margin %'],
                $rows->map(fn ($r) => [$r->product_id, $r->name, $r->sku, $r->category, (int) $r->qty, $r->revenue, (int) $r->orders, $r->cost, $r->profit, $r->margin]));
        }

        return view('manager.reports.products', [
            'from' => $from, 'to' => $to, 'rows' => $rows,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function profitLoss(Request $request)
    {
        [$from, $to] = ReportService::range($request->from, $request->to);

        return view('manager.reports.profit-loss', ['from' => $from, 'to' => $to, 'pl' => $this->reports->profitLoss(Tenancy::branch(), $from, $to)]);
    }

    public function purchases(Request $request)
    {
        $b = Tenancy::branch();
        [$from, $to] = ReportService::range($request->from, $request->to);
        $rows = $this->reports->purchases($b, $from, $to);

        if ($request->query('export') === 'csv') {
            return Csv::download('purchase-report', ['Supplier ID', 'Supplier', 'Purchases', 'Total', 'Returned', 'Paid', 'Due'], $rows);
        }

        $dues = $b->suppliers()->withSum('purchases as purchased', 'total')->withSum('returns as returned', 'total')->withSum('payments as paid', 'amount')->get()
            ->map(function (Supplier $s) {
                $s->due_amount = round((float) $s->opening_balance + (float) $s->purchased - (float) $s->returned - (float) $s->paid, 2);

                return $s;
            })->where('due_amount', '!=', 0)->sortByDesc('due_amount');

        return view('manager.reports.purchases', ['from' => $from, 'to' => $to, 'rows' => $rows, 'dues' => $dues]);
    }

    public function tax(Request $request)
    {
        $b = Tenancy::branch();
        [$from, $to] = ReportService::range($request->from, $request->to ?? null, 'month');
        if (! $request->filled('from')) {
            $from = now()->startOfYear();
        }
        $rows = $this->reports->tax($b, $from, $to);

        if ($request->query('export') === 'csv') {
            return Csv::download('vat-tax-report', ['Month', 'Orders', 'Taxable amount', 'Tax collected'], $rows->map(fn ($r) => [$r->p, (int) $r->c, (float) $r->taxable, (float) $r->tax]));
        }

        return view('manager.reports.tax', ['from' => $from, 'to' => $to, 'rows' => $rows, 'rate' => (float) $b->tax_percent]);
    }

    public function customers(Request $request)
    {
        $b = Tenancy::branch();
        [$from, $to] = ReportService::range($request->from, $request->to);
        $data = $this->reports->customers($b, $from, $to);

        if ($request->query('export') === 'csv') {
            return Csv::download('top-customers', ['Name', 'Phone', 'Orders', 'Spent', 'Last order'],
                $data['top']->map(fn ($r) => [$r->name, $r->phone, (int) $r->c, (float) $r->spent, (string) $r->last_at]));
        }

        return view('manager.reports.customers', $data + ['from' => $from, 'to' => $to]);
    }

    public function dues(Request $request)
    {
        $b = Tenancy::branch();
        $rows = $this->reports->receivables($b);

        if ($request->query('export') === 'csv') {
            return Csv::download('receivables', ['Order', 'Date', 'Customer', 'Phone', 'Method', 'Payment', 'Status', 'Amount due'],
                $rows->map(fn ($o) => [$o->order_number, $o->created_at->toDateString(), $o->customer_name, $o->customer_phone, $o->payment_method, $o->payment_status, $o->status, $o->payable]));
        }

        return view('manager.reports.dues', ['rows' => $rows]);
    }
}
