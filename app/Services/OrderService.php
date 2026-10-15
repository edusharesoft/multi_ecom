<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Order lifecycle: place, status changes (stock, loyalty, history), refunds and returns. */
class OrderService
{
    public function __construct(protected Notifier $notifier, protected InventoryService $inventory, protected \App\Services\Messaging\OrderNotifier $orderNotifier) {}

    /**
     * Create an order from the cart. Stock is reserved atomically (no overselling).
     *
     * @throws RuntimeException when stock ran out meanwhile
     */
    public function place(Branch $branch, CartService $cart, array $data, ?Customer $customer = null, float $walletRequested = 0, string $source = 'online'): Order
    {
        $items = $cart->items();
        if ($items->isEmpty()) {
            throw new RuntimeException('আপনার কার্ট খালি।');
        }
        $totals = $cart->totals($items, $customer, $data['customer_phone'] ?? null, $walletRequested);
        $commissionPct = (float) $branch->effectivePlan()->commission_percent;
        $pointsRate = (float) $branch->option('points_per_100');

        $warehouse = $branch->defaultWarehouse();

        $order = DB::transaction(function () use ($branch, $data, $items, $totals, $customer, $commissionPct, $pointsRate, $source, $warehouse) {
            $paidByWallet = $totals['wallet'] > 0 && $totals['payable'] <= 0;

            $order = $branch->orders()->create(array_merge($data, [
                'order_number' => Order::generateNumber(),
                'customer_id' => $customer?->id,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'coupon_code' => $totals['coupon']?->code,
                'shipping' => $totals['shipping'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'wallet_used' => $totals['wallet'],
                'commission_percent' => $commissionPct,
                'commission_amount' => round($totals['total'] * $commissionPct / 100, 2),
                'points_earned' => (int) floor($totals['total'] / 100 * $pointsRate),
                'payment_method' => $paidByWallet ? 'wallet' : $data['payment_method'],
                'payment_status' => $paidByWallet ? 'paid' : 'unpaid',
                'paid_at' => $paidByWallet ? now() : null,
                'status' => $paidByWallet ? 'confirmed' : 'new',
                'source' => $source,
            ]));

            foreach ($items as $item) {
                // conditional decrement = atomic stock check
                $ok = $item->variant
                    ? tap(ProductVariant::whereKey($item->variant->id)->where('stock', '>=', $item->quantity)->decrement('stock', $item->quantity), fn () => $this->syncProductStock($item->product->id))
                    : Product::whereKey($item->product->id)->where('stock', '>=', $item->quantity)->decrement('stock', $item->quantity);
                if (! $ok && ($item->product->product_type ?? 'physical') === 'physical') {
                    throw new RuntimeException("{$item->product->name} — পর্যাপ্ত স্টক নেই।");
                }
                if ($ok) {   // warehouse split + ledger (totals already decremented above)
                    $this->inventory->move($branch, $warehouse, $item->product->id, (int) ($item->variant?->id ?? 0), -$item->quantity, 'sale', $order, (float) $item->product->purchase_price, null, updateTotals: false);
                }
                Product::whereKey($item->product->id)->increment('sold_count', $item->quantity);

                $order->items()->create([
                    'product_id' => $item->product->id,
                    'product_variant_id' => $item->variant?->id,
                    'product_name' => $item->product->name,
                    'variant_name' => $item->variant?->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                ]);
            }

            if ($coupon = $totals['coupon']) {
                // atomic usage-limit check (two checkouts racing for the last use)
                $claimed = Coupon::whereKey($coupon->id)
                    ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
                    ->increment('used_count');
                if (! $claimed) {
                    throw new RuntimeException('এই কুপনের ব্যবহারসীমা শেষ।');
                }
                CouponUsage::create(['coupon_id' => $coupon->id, 'order_id' => $order->id, 'customer_id' => $customer?->id,
                    'phone' => $data['customer_phone'] ?? null, 'discount' => $totals['discount']]);
            }
            if ($customer && $totals['wallet'] > 0) {
                $customer->adjustWallet(-$totals['wallet'], 'order_payment', "Order {$order->order_number}", $order->id);
            }

            $order->addHistory($order->status, 'Order placed'.($source === 'pos' ? ' (POS)' : ''), $customer?->name ?? $data['customer_name'] ?? 'Customer');

            return $order;
        });

        $cart->clear();
        $cart->setCoupon(null);

        ActivityLog::record('order.created', "New order {$order->order_number} (৳ {$order->total})", $order, [], $branch->id);
        $this->notifier->branch($branch, 'order.new', "নতুন অর্ডার {$order->order_number}", "{$order->customer_name} — ৳ ".number_format((float) $order->total, 2), route('manager.orders.show', $order), 'success', ['app']);
        $this->orderNotifier->placed($order);

        return $order;
    }

    /** Change status with stock release / re-take, loyalty points and history. */
    public function changeStatus(Order $order, string $status, ?string $note = null): void
    {
        if ($order->status === $status) {
            return;
        }

        // Cancelled / failed orders are final (stock, wallet and coupon were already given back)
        if (in_array($order->status, Order::STOCK_RELEASED, true)) {
            throw new RuntimeException('বাতিল/ব্যর্থ অর্ডার আবার চালু করা যায় না — নতুন অর্ডার তৈরি করুন।');
        }

        DB::transaction(function () use ($order, $status, $note) {
            $wasReleased = in_array($order->status, Order::STOCK_RELEASED, true);
            $willRelease = in_array($status, Order::STOCK_RELEASED, true);

            if (! $wasReleased && $willRelease) {
                $this->adjustStock($order, +1);
            } elseif ($wasReleased && ! $willRelease) {
                $this->adjustStock($order, -1);
            }

            $order->update(['status' => $status]);
            $order->addHistory($status, $note);

            if (! $wasReleased && $willRelease) {
                // free the coupon use so it can be used again
                foreach (CouponUsage::where('order_id', $order->id)->get() as $usage) {
                    Coupon::whereKey($usage->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
                    $usage->delete();
                }
                // give back wallet money spent on the cancelled order (not already refunded)
                $walletBack = round(min((float) $order->wallet_used, (float) $order->total - (float) $order->refunded_amount), 2);
                if ($walletBack > 0 && $order->customer_id && $order->customer) {
                    $this->refund($order, $walletBack, 'wallet', 'Order '.$status.' — wallet amount returned');
                }
            }

            // Loyalty points once the order is delivered / completed
            if (in_array($status, ['delivered', 'completed'], true) && ! $order->points_credited && $order->customer_id && $order->points_earned > 0) {
                Customer::whereKey($order->customer_id)->increment('loyalty_points', $order->points_earned);
                $order->forceFill(['points_credited' => true])->save();
            }
            // COD collected on delivery
            if ($status === 'completed' && $order->payment_method === 'cod' && $order->payment_status === 'unpaid') {
                if (! $order->payments()->where('status', 'success')->exists()) {
                    $order->payments()->create([
                        'gateway' => 'cod', 'reference' => $order->order_number.'-COD-'.strtoupper(Str::random(4)), 'amount' => $order->payable, 'status' => 'success',
                    ]);
                }
                $order->markPaid();
            }
        });

        ActivityLog::record('order.status', "Order {$order->order_number} → {$status}", $order);
        DB::afterCommit(fn () => $this->orderNotifier->statusChanged($order, $status));
    }

    /** Stock back (+1) or out again (−1) for all items of an order. */
    public function adjustStock(Order $order, int $sign, ?array $onlyItems = null, ?string $type = null): void
    {
        $branch = $order->branch;
        $warehouse = $order->warehouse_id ? ($branch->warehouses()->find($order->warehouse_id) ?? $branch->defaultWarehouse()) : $branch->defaultWarehouse();
        $type ??= $sign > 0 ? ($order->source === 'pos' ? 'pos_return' : 'cancel_restock') : ($order->source === 'pos' ? 'pos_sale' : 'sale');

        foreach ($order->items as $item) {
            // full release skips units already returned at the POS (they are back in stock)
            $qty = $onlyItems === null ? $item->quantity - (int) $item->returned_qty : (int) ($onlyItems[$item->id] ?? 0);
            if ($qty <= 0 || ! $item->product_id) {
                continue;
            }
            // POS never took stock for digital / service items — don't put any back
            if ($order->source === 'pos' && (Product::whereKey($item->product_id)->value('product_type') ?? 'physical') !== 'physical') {
                continue;
            }
            // reuse the cost recorded at sale time so returns / cancels reverse COGS exactly
            $cost = \App\Models\StockMovement::where('reference_type', $order->getMorphClass())->where('reference_id', $order->id)
                ->where('product_id', $item->product_id)->where('variant_id', (int) ($item->product_variant_id ?? 0))
                ->whereIn('type', ['sale', 'pos_sale'])->whereNotNull('unit_cost')->value('unit_cost');
            $this->inventory->move($branch, $warehouse, $item->product_id, (int) ($item->product_variant_id ?? 0), $sign * $qty, $type, $order, $cost !== null ? (float) $cost : null, "Order {$order->order_number}");
            $sign > 0
                ? Product::whereKey($item->product_id)->where('sold_count', '>=', $qty)->decrement('sold_count', $qty)
                : Product::whereKey($item->product_id)->increment('sold_count', $qty);
        }
    }

    /** Keep products.stock = sum of variant stock (used by filters & low-stock alerts). */
    protected function syncProductStock(int $productId): void
    {
        if (ProductVariant::where('product_id', $productId)->exists()) {
            Product::whereKey($productId)->update(['stock' => (int) ProductVariant::where('product_id', $productId)->sum('stock')]);
        }
    }

    /**
     * Full or partial refund. method: wallet (customer account) | original | cash | bkash | nagad
     */
    public function refund(Order $order, float $amount, string $method, ?string $note = null): void
    {
        $max = (float) $order->total - (float) $order->refunded_amount;
        $amount = round(min($amount, $max), 2);
        if ($amount <= 0) {
            throw new RuntimeException('Refund amount is not valid.');
        }
        if ($method === 'wallet' && ! $order->customer_id) {
            throw new RuntimeException('Guest order — wallet refund is not possible.');
        }

        DB::transaction(function () use ($order, $amount, $method, $note) {
            $order->payments()->create([
                'gateway' => $method,
                'reference' => $order->order_number.'-RF-'.strtoupper(Str::random(5)),
                'amount' => -$amount,
                'status' => 'refunded',
                'payload' => ['note' => $note],
            ]);
            $refunded = (float) $order->refunded_amount + $amount;
            $order->update([
                'refunded_amount' => $refunded,
                'payment_status' => $refunded >= (float) $order->total ? 'refunded' : 'partially_refunded',
            ]);
            if ($method === 'wallet') {
                $order->customer->adjustWallet($amount, 'refund', "Refund for {$order->order_number}", $order->id);
            }
            $order->addHistory($order->status, "Refund ৳ {$amount} via {$method}".($note ? " — {$note}" : ''));
        });

        ActivityLog::record('order.refund', "Refund ৳ {$amount} ({$method}) for {$order->order_number}", $order);
    }

    /** Move a return request through its workflow. */
    public function processReturn(ReturnRequest $return, string $status, array $data = []): void
    {
        DB::transaction(function () use ($return, $status, $data) {
            $order = $return->order()->with('items')->first();

            // Restock once, when the parcel arrives back (also when "received" is skipped in the workflow)
            $backStates = ['received', 'inspected', 'refunded', 'replaced', 'exchanged'];
            if (in_array($status, $backStates, true) && $return->restock && ! in_array($return->status, $backStates, true)) {
                $qty = collect($return->items)->mapWithKeys(fn ($i) => [(int) $i['order_item_id'] => (int) $i['quantity']])->all();
                $this->adjustStock($order, +1, $qty, 'sale_return');
            }

            if ($status === 'refunded') {
                // decimal cast gives "0.00" (truthy) — compare as floats
                $amount = (float) ($data['refund_amount'] ?? 0) ?: ((float) $return->refund_amount ?: $return->itemsValue());
                $this->refund($order, $amount, $data['refund_method'] ?? 'wallet', "Return {$return->number}");
                $return->refund_amount = $amount;
                $return->refund_method = $data['refund_method'] ?? 'wallet';
                $allReturned = collect($return->items)->sum('quantity') >= $order->items->sum('quantity');
                $this->changeStatus($order->fresh(), $allReturned ? 'refunded' : $order->status, "Return {$return->number} refunded");
            }
            if (in_array($status, ['replaced', 'exchanged'], true)) {
                $order->addHistory($order->status, "Return {$return->number}: {$status}");
            }

            $return->fill([
                'status' => $status,
                'admin_note' => $data['admin_note'] ?? $return->admin_note,
                'resolved_at' => in_array($status, ['refunded', 'replaced', 'exchanged', 'rejected'], true) ? now() : null,
            ])->save();
        });

        ActivityLog::record('return.'.$status, "Return {$return->number} → {$status}", $return);
    }
}
