<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PosSession;
use App\Models\Product;
use App\Support\Tenancy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Point-of-sale: sell from a register session's warehouse, returns, drawer closing. */
class PosService
{
    public function __construct(protected InventoryService $inventory, protected OrderService $orders) {}

    public function currentSession(Branch $branch): ?PosSession
    {
        return $branch->posSessions()->whereNull('closed_at')->latest('opened_at')->first();
    }

    public function open(Branch $branch, int $warehouseId, float $openingCash): PosSession
    {
        if ($this->currentSession($branch)) {
            throw new RuntimeException('একটি POS সেশন আগে থেকেই খোলা আছে।');
        }
        $warehouse = $branch->warehouses()->findOrFail($warehouseId);
        $session = $branch->posSessions()->create([
            'warehouse_id' => $warehouse->id, 'opened_by' => Tenancy::actorName(), 'opening_cash' => $openingCash, 'opened_at' => now(),
        ]);
        ActivityLog::record('pos.opened', "POS register opened ({$warehouse->name}) with ৳ {$openingCash}", $session);

        return $session;
    }

    public function close(PosSession $session, float $counted, ?string $note = null): PosSession
    {
        $t = $session->totals();
        $session->update([
            'closed_at' => now(), 'closed_by' => Tenancy::actorName(), 'expected_cash' => $t['expected_cash'],
            'counted_cash' => $counted, 'difference' => round($counted - $t['expected_cash'], 2), 'summary' => $t, 'note' => $note,
        ]);
        ActivityLog::record('pos.closed', "POS closed — expected ৳ {$t['expected_cash']}, counted ৳ {$counted}", $session);

        return $session;
    }

    /**
     * @param  array  $lines  [['product_id', 'variant_id', 'quantity', 'price'?], ...]
     * @param  array  $pay  ['method' => cash|card|bkash|nagad|rocket, 'tendered' => float, 'reference' => ?string]
     * @param  array  $opts  ['client_ref' => ?string, 'offline' => bool] — offline = sale already made without internet (Phase 10)
     */
    public function checkout(Branch $branch, PosSession $session, array $lines, ?Customer $customer, array $discount, array $pay, ?string $note = null, array $opts = []): Order
    {
        abort_unless($session->branch_id === $branch->id && $session->isOpen(), 409, 'POS session is closed.');
        $warehouse = $session->warehouse;

        $offline = ! empty($opts['offline']);

        return DB::transaction(function () use ($branch, $session, $warehouse, $lines, $customer, $discount, $pay, $note, $opts, $offline) {
            $products = $branch->products()->with('variants')->whereIn('id', array_column($lines, 'product_id'))->get()->keyBy('id');
            $items = [];
            foreach ($lines as $l) {
                $p = $products[$l['product_id']] ?? null;
                $vid = (int) ($l['variant_id'] ?? 0);
                $v = $vid ? $p?->variants->firstWhere('id', $vid) : null;
                $v?->setRelation('product', $p);
                $qty = (int) ($l['quantity'] ?? 0);
                if (! $p || ($vid && ! $v) || $qty < 1) {
                    continue;
                }
                // POS may override the unit price only with discount permission
                $price = $v ? $v->effectivePrice() : $p->currentPrice();
                if (isset($l['price']) && Tenancy::can('pos.approve')) {
                    $price = max(0, (float) $l['price']);
                }
                $items[] = ['product' => $p, 'variant' => $v, 'qty' => $qty, 'price' => round($price, 2)];
            }
            if (! $items) {
                throw new RuntimeException('কার্ট খালি।');
            }

            $subtotal = round(collect($items)->sum(fn ($i) => $i['qty'] * $i['price']), 2);
            $disc = (float) ($discount['value'] ?? 0);
            $disc = ($discount['type'] ?? 'fixed') === 'percent' ? $subtotal * min(100, $disc) / 100 : $disc;
            $disc = round(min($subtotal, max(0, $disc)), 2);
            if ($disc > 0 && ! Tenancy::can('pos.approve') && $disc > $subtotal * 0.1) {
                throw new RuntimeException('১০% এর বেশি ডিসকাউন্ট দিতে ম্যানেজারের অনুমতি (pos.approve) লাগবে।');
            }
            $ratio = $subtotal > 0 ? ($subtotal - $disc) / $subtotal : 0;
            $tax = round(collect($items)->sum(function ($i) use ($ratio, $branch) {
                $rate = (float) $i['product']->tax_percent > 0 ? (float) $i['product']->tax_percent : (float) $branch->tax_percent;

                return $i['qty'] * $i['price'] * $ratio * $rate / 100;
            }), 2);
            $total = round($subtotal - $disc + $tax, 2);

            $method = $pay['method'] ?? 'cash';
            $tendered = $method === 'cash' ? round((float) ($pay['tendered'] ?? $total), 2) : $total;
            if ($offline) {
                $tendered = max($tendered, $total);   // money was already taken at the counter; prices may have changed since
            }
            if ($method === 'cash' && $tendered < $total) {
                throw new RuntimeException('প্রদত্ত টাকা মোট বিলের চেয়ে কম।');
            }

            $order = $branch->orders()->create([
                'order_number' => Order::generateNumber(),
                'customer_id' => $customer?->id,
                'warehouse_id' => $warehouse->id,
                'pos_session_id' => $session->id,
                'customer_name' => $customer?->name ?? 'Walk-in Customer',
                'customer_phone' => $customer?->phone ?? '-',
                'customer_email' => $customer?->email,
                'shipping_address' => 'POS — '.$warehouse->name,
                'note' => $note,
                'subtotal' => $subtotal, 'discount' => $disc, 'shipping' => 0, 'tax' => $tax, 'total' => $total,
                'commission_percent' => 0, 'commission_amount' => 0,
                'points_earned' => $customer ? (int) floor($total / 100 * (float) $branch->option('points_per_100')) : 0,
                'payment_method' => $method, 'payment_status' => 'paid', 'paid_at' => $opts['sold_at'] ?? now(),
                'transaction_id' => $pay['reference'] ?? null,
                'tendered' => $tendered, 'change_due' => round($tendered - $total, 2),
                'status' => 'completed', 'source' => 'pos', 'staff_name' => Tenancy::actorName(),
                'client_ref' => $opts['client_ref'] ?? null,
            ]);

            foreach ($items as $i) {
                $order->items()->create([
                    'product_id' => $i['product']->id, 'product_variant_id' => $i['variant']?->id,
                    'product_name' => $i['product']->name, 'variant_name' => $i['variant']?->name,
                    'price' => $i['price'], 'quantity' => $i['qty'], 'total' => round($i['qty'] * $i['price'], 2),
                ]);
                if (($i['product']->product_type ?? 'physical') === 'physical') {
                    $this->inventory->move($branch, $warehouse, $i['product']->id, (int) ($i['variant']?->id ?? 0), -$i['qty'], 'pos_sale', $order, (float) $i['product']->purchase_price, null, allowNegative: $offline || (bool) $branch->option('pos_allow_negative', false));   // an offline sale has physically happened
                }
                Product::whereKey($i['product']->id)->increment('sold_count', $i['qty']);
            }

            $order->payments()->create([
                'gateway' => $method, 'reference' => $order->order_number.'-POS', 'amount' => $total,
                'transaction_id' => $pay['reference'] ?? null, 'status' => 'success',
            ]);
            $order->addHistory('completed', 'POS sale');

            if ($customer && $order->points_earned > 0) {
                Customer::whereKey($customer->id)->increment('loyalty_points', $order->points_earned);
                $order->forceFill(['points_credited' => true])->save();
            }

            return $order;
        });
    }

    /**
     * POS return / exchange refund. $qty = [order_item_id => qty]
     */
    public function returnItems(Branch $branch, Order $order, array $qty, string $method, ?string $note = null): float
    {
        abort_unless($order->branch_id === $branch->id, 404);
        if (! in_array($order->status, ['completed', 'delivered', 'returned'], true) || ! in_array($order->payment_status, ['paid', 'partially_refunded'], true)) {
            throw new RuntimeException('শুধু সম্পন্ন ও পরিশোধিত বিল ফেরত নেওয়া যায়।');
        }
        $order->load('items');
        $clean = [];
        $amount = 0.0;
        foreach ($order->items as $item) {
            $q = min((int) ($qty[$item->id] ?? 0), $item->quantity - (int) $item->returned_qty);
            if ($q > 0) {
                $clean[$item->id] = $q;
                $amount += $q * (float) $item->price;
            }
        }
        if (! $clean) {
            throw new RuntimeException('ফেরতের পরিমাণ দিন।');
        }
        // proportional share of the invoice discount/tax
        $ratio = (float) $order->subtotal > 0 ? (float) $order->total / (float) $order->subtotal : 1;
        $amount = round(min($amount * $ratio, (float) $order->total - (float) $order->refunded_amount), 2);
        if ($amount <= 0) {
            throw new RuntimeException('এই বিলে আর রিফান্ড দেওয়া যাবে না।');
        }

        DB::transaction(function () use ($order, $clean, $amount, $method, $note) {
            foreach ($clean as $itemId => $q) {
                $updated = \App\Models\OrderItem::whereKey($itemId)->whereRaw('quantity - returned_qty >= ?', [$q])->increment('returned_qty', $q);
                if (! $updated) {
                    throw new RuntimeException('এই পণ্য আগেই ফেরত নেওয়া হয়েছে।');
                }
            }
            $this->orders->adjustStock($order, +1, $clean, 'pos_return');
            if ($method === 'cash') {
                // cash leaves the CURRENT drawer, whichever shift the sale belonged to
                $session = $this->currentSession($order->branch);
                if (! $session) {
                    throw new RuntimeException('ক্যাশ রিফান্ডের জন্য POS সেশন খোলা থাকতে হবে।');
                }
                $session->cashMovements()->create(['type' => 'out', 'amount' => $amount, 'note' => "Refund {$order->order_number}", 'actor_name' => Tenancy::actorName()]);
            }
            $this->orders->refund($order, $amount, $method === 'wallet' ? 'wallet' : $method, $note ?: 'POS return');
            $fresh = $order->fresh();
            if ((float) $fresh->refunded_amount >= (float) $fresh->total && $fresh->status !== 'returned') {
                $fresh->update(['status' => 'returned']);
                $fresh->addHistory('returned', 'All items returned at POS');
            }
        });

        return $amount;
    }
}
