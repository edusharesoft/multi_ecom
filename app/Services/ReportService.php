<?php

namespace App\Services;

use App\Models\AccountTransaction;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StockMovement;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Read-only branch reports. All money is BDT; periods are inclusive dates. */
class ReportService
{
    /** order statuses that never count as a sale */
    public const VOID = ['cancelled', 'failed'];

    /** stock movement types that are cost of goods sold (−qty) or reverse it (+qty) */
    public const COGS_TYPES = ['sale', 'pos_sale', 'sale_return', 'pos_return', 'cancel_restock'];

    public static function range(?string $from, ?string $to, string $default = 'month'): array
    {
        $to = $to ? Carbon::parse($to)->endOfDay() : now()->endOfDay();
        $from = $from ? Carbon::parse($from)->startOfDay() : ($default === 'month' ? now()->startOfMonth() : now()->subDays(29)->startOfDay());
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    protected function orders(Branch $b, Carbon $from, Carbon $to)
    {
        return Order::where('branch_id', $b->id)->whereNotIn('status', self::VOID)->whereBetween('created_at', [$from, $to]);
    }

    /* ---------------- Sales ---------------- */

    public function salesSummary(Branch $b, Carbon $from, Carbon $to): array
    {
        $o = $this->orders($b, $from, $to)->selectRaw('count(*) c, coalesce(sum(subtotal),0) sub, coalesce(sum(discount),0) disc, coalesce(sum(shipping),0) ship, coalesce(sum(tax),0) tax, coalesce(sum(total),0) total, coalesce(sum(refunded_amount),0) ref, coalesce(sum(commission_amount),0) comm')->first();
        $cancelled = Order::where('branch_id', $b->id)->whereIn('status', self::VOID)->whereBetween('created_at', [$from, $to])->count();

        return [
            'orders' => (int) $o->c, 'subtotal' => (float) $o->sub, 'discount' => (float) $o->disc, 'shipping' => (float) $o->ship,
            'tax' => (float) $o->tax, 'total' => (float) $o->total, 'refunded' => (float) $o->ref, 'commission' => (float) $o->comm,
            'net' => round((float) $o->total - (float) $o->ref, 2),
            'avg' => $o->c ? round((float) $o->total / $o->c, 2) : 0, 'cancelled' => $cancelled,
        ];
    }

    /** rows grouped by day | month */
    public function salesByPeriod(Branch $b, Carbon $from, Carbon $to, string $group = 'day'): Collection
    {
        $fmt = $group === 'month' ? '%Y-%m' : '%Y-%m-%d';
        $rows = $this->orders($b, $from, $to)
            ->selectRaw("DATE_FORMAT(created_at, '{$fmt}') p, count(*) c, sum(subtotal) sub, sum(discount) disc, sum(shipping) ship, sum(tax) tax, sum(total) total, sum(refunded_amount) ref")
            ->groupBy('p')->orderBy('p')->get()->keyBy('p');

        // fill empty periods so charts are continuous
        $out = collect();
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $key = $group === 'month' ? $cursor->format('Y-m') : $cursor->format('Y-m-d');
            $r = $rows[$key] ?? null;
            $out->push([
                'period' => $key, 'orders' => (int) ($r->c ?? 0), 'subtotal' => (float) ($r->sub ?? 0), 'discount' => (float) ($r->disc ?? 0),
                'shipping' => (float) ($r->ship ?? 0), 'tax' => (float) ($r->tax ?? 0), 'total' => (float) ($r->total ?? 0),
                'refunded' => (float) ($r->ref ?? 0), 'net' => round((float) ($r->total ?? 0) - (float) ($r->ref ?? 0), 2),
            ]);
            $group === 'month' ? $cursor->addMonthNoOverflow()->startOfMonth() : $cursor->addDay();
            if ($out->count() > 800) {
                break;
            }
        }

        return $out;
    }

    public function salesBy(Branch $b, Carbon $from, Carbon $to, string $column): Collection
    {
        abort_unless(in_array($column, ['payment_method', 'source', 'staff_name', 'status'], true), 400);

        return $this->orders($b, $from, $to)
            ->selectRaw("coalesce({$column}, '—') k, count(*) c, sum(total) total, sum(refunded_amount) ref")
            ->groupBy('k')->orderByDesc('total')->get();
    }

    /* ---------------- Products / COGS ---------------- */

    /** Cost of goods sold in the period, from the stock ledger (unit_cost at time of sale, fallback current purchase price). */
    public function cogs(Branch $b, Carbon $from, Carbon $to, bool $perProduct = false)
    {
        $q = StockMovement::query()->from('stock_movements as m')
            ->join('products as p', 'p.id', '=', 'm.product_id')
            ->where('m.branch_id', $b->id)->whereIn('m.type', self::COGS_TYPES)->whereBetween('m.created_at', [$from, $to]);

        if ($perProduct) {
            return $q->selectRaw('m.product_id, sum(-m.quantity * coalesce(m.unit_cost, p.purchase_price, 0)) cost')->groupBy('m.product_id')->pluck('cost', 'product_id');
        }

        return round((float) $q->selectRaw('coalesce(sum(-m.quantity * coalesce(m.unit_cost, p.purchase_price, 0)),0) cost')->value('cost'), 2);
    }

    public function products(Branch $b, Carbon $from, Carbon $to, ?int $categoryId = null, string $sort = 'revenue'): Collection
    {
        $rows = DB::table('order_items as i')->join('orders as o', 'o.id', '=', 'i.order_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->leftJoin('categories as c', 'c.id', '=', 'p.category_id')
            ->where('o.branch_id', $b->id)->whereNotIn('o.status', self::VOID)->whereBetween('o.created_at', [$from, $to])
            ->when($categoryId, fn ($q) => $q->where('p.category_id', $categoryId))
            ->selectRaw('i.product_id, max(i.product_name) name, max(p.sku) sku, max(c.name) category, sum(i.quantity - coalesce(i.returned_qty,0)) qty, sum((i.quantity - coalesce(i.returned_qty,0)) * i.price) revenue, count(distinct o.id) orders')
            ->groupBy('i.product_id')->get();

        $cost = $this->cogs($b, $from, $to, true);

        return $rows->map(function ($r) use ($cost) {
            $r->cost = round((float) ($cost[$r->product_id] ?? 0), 2);
            $r->revenue = round((float) $r->revenue, 2);
            $r->profit = round($r->revenue - $r->cost, 2);
            $r->margin = $r->revenue > 0 ? round($r->profit / $r->revenue * 100, 1) : 0;

            return $r;
        })->sortByDesc(fn ($r) => $r->{in_array($sort, ['qty', 'profit', 'revenue', 'margin'], true) ? $sort : 'revenue'})->values();
    }

    /* ---------------- Profit & Loss ---------------- */

    public function profitLoss(Branch $b, Carbon $from, Carbon $to): array
    {
        $s = $this->salesSummary($b, $from, $to);
        // refunds paid out in the period (by refund date), excluding the tax share is not tracked → shown gross
        $refunds = round(abs((float) Payment::whereIn('order_id', Order::where('branch_id', $b->id)->whereNotIn('status', self::VOID)->select('id'))
            ->where('status', 'refunded')->where('amount', '<', 0)->whereBetween('created_at', [$from, $to])
            ->where('gateway', '!=', 'wallet')->sum('amount')), 2);
        $walletRefunds = round(abs((float) Payment::whereIn('order_id', Order::where('branch_id', $b->id)->whereNotIn('status', self::VOID)->select('id'))
            ->where('status', 'refunded')->where('amount', '<', 0)->whereBetween('created_at', [$from, $to])
            ->where('gateway', 'wallet')->sum('amount')), 2);

        $netSales = round($s['subtotal'] - $s['discount'] + $s['shipping'], 2);
        $revenue = round($netSales - $refunds - $walletRefunds, 2);
        $cogs = $this->cogs($b, $from, $to);
        $stockLoss = round((float) StockMovement::query()->from('stock_movements as m')->join('products as p', 'p.id', '=', 'm.product_id')
            ->where('m.branch_id', $b->id)->whereIn('m.type', ['damage', 'adjustment_out', 'adjustment_in'])->whereBetween('m.created_at', [$from, $to])
            ->selectRaw('coalesce(sum(-m.quantity * coalesce(m.unit_cost, p.purchase_price, 0)),0) v')->value('v'), 2);

        $vouchers = Voucher::where('vouchers.branch_id', $b->id)->whereBetween('voucher_date', [$from->toDateString(), $to->toDateString()])
            ->join('account_categories as c', 'c.id', '=', 'vouchers.account_category_id')
            ->selectRaw('vouchers.kind, c.name, sum(vouchers.amount) amt')->groupBy('vouchers.kind', 'c.name')->orderByDesc('amt')->get();
        $expenses = $vouchers->where('kind', 'expense')->mapWithKeys(fn ($r) => [$r->name => (float) $r->amt]);
        $income = $vouchers->where('kind', 'income')->mapWithKeys(fn ($r) => [$r->name => (float) $r->amt]);

        $gross = round($revenue - $cogs, 2);
        $opex = round($expenses->sum() + $s['commission'] + $stockLoss, 2);
        $net = round($gross - $opex + $income->sum(), 2);

        return compact('s', 'netSales', 'refunds', 'walletRefunds', 'revenue', 'cogs', 'gross', 'stockLoss', 'expenses', 'income', 'opex', 'net')
            + ['tax' => $s['tax'], 'commission' => $s['commission'], 'margin' => $revenue > 0 ? round($net / $revenue * 100, 1) : 0];
    }

    /* ---------------- Purchases / Tax / Customers / Dues ---------------- */

    public function purchases(Branch $b, Carbon $from, Carbon $to): Collection
    {
        return DB::table('purchases as p')->join('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->where('p.branch_id', $b->id)->whereBetween('p.purchase_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('s.id, s.name, count(*) c, sum(p.total) total, sum(p.returned) returned, sum(p.paid) paid, sum(p.total - p.returned - p.paid) due')
            ->groupBy('s.id', 's.name')->orderByDesc('total')->get();
    }

    public function tax(Branch $b, Carbon $from, Carbon $to): Collection
    {
        return $this->orders($b, $from, $to)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') p, count(*) c, sum(subtotal - discount) taxable, sum(tax) tax")
            ->groupBy('p')->orderBy('p')->get();
    }

    public function customers(Branch $b, Carbon $from, Carbon $to): array
    {
        $top = $this->orders($b, $from, $to)->selectRaw("coalesce(customer_id, 0) cid, max(customer_name) name, customer_phone phone, count(*) c, sum(total - refunded_amount) spent, max(created_at) last_at")
            ->groupBy('cid', 'customer_phone')->orderByDesc('spent')->limit(50)->get();
        $new = DB::table('customers')->where('branch_id', $b->id)->whereBetween('created_at', [$from, $to])->count();
        $buyers = $this->orders($b, $from, $to)->distinct()->count('customer_phone');
        $repeat = $this->orders($b, $from, $to)->select('customer_phone')->groupBy('customer_phone')->havingRaw('count(*) > 1')->get()->count();

        return compact('top', 'new', 'buyers', 'repeat');
    }

    public function receivables(Branch $b): Collection
    {
        return Order::where('branch_id', $b->id)->whereNotIn('status', array_merge(self::VOID, ['returned', 'refunded']))
            ->whereIn('payment_status', ['unpaid', 'pending_verification', 'failed'])
            ->orderBy('created_at')->get(['id', 'order_number', 'customer_name', 'customer_phone', 'total', 'wallet_used', 'payment_method', 'payment_status', 'status', 'created_at']);
    }

    /* ---------------- Cash book ---------------- */

    public function cashbook(Branch $b, Carbon $from, Carbon $to, ?int $accountId = null): array
    {
        $accounts = $b->accounts()->orderByDesc('is_default')->orderBy('id')->get();
        $ids = $accountId ? [$accountId] : $accounts->pluck('id')->all();
        $opening = (float) $accounts->whereIn('id', $ids)->sum('opening_balance')
            + (float) AccountTransaction::whereIn('account_id', $ids)->where('txn_date', '<', $from->toDateString())
                ->selectRaw("coalesce(sum(case when direction='in' then amount else -amount end),0) v")->value('v');
        $rows = AccountTransaction::with('account')->whereIn('account_id', $ids)
            ->whereBetween('txn_date', [$from->toDateString(), $to->toDateString()])->orderBy('txn_date')->orderBy('id')->get();
        $bal = $opening;
        foreach ($rows as $r) {
            $bal += $r->signed();
            $r->running = round($bal, 2);
        }

        return ['accounts' => $accounts, 'opening' => round($opening, 2), 'rows' => $rows, 'closing' => round($bal, 2),
            'in' => round((float) $rows->where('direction', 'in')->sum('amount'), 2), 'out' => round((float) $rows->where('direction', 'out')->sum('amount'), 2)];
    }
}
