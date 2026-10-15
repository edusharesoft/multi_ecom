<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Warehouse;
use App\Support\DocNumber;
use App\Support\Tenancy;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Purchases (stock in), purchase returns (stock out) and supplier payments. */
class PurchaseService
{
    public function __construct(protected InventoryService $inventory) {}

    /**
     * @param  array  $lines  [['product_id'=>, 'variant_id'=>, 'quantity'=>, 'unit_cost'=>], ...]
     */
    public function create(Branch $branch, Supplier $supplier, Warehouse $warehouse, array $lines, array $meta): Purchase
    {
        abort_unless($supplier->branch_id === $branch->id && $warehouse->branch_id === $branch->id, 403);

        return DB::transaction(function () use ($branch, $supplier, $warehouse, $lines, $meta) {
            $products = $branch->products()->with('variants')->whereIn('id', array_column($lines, 'product_id'))->get()->keyBy('id');
            $rows = [];
            foreach ($lines as $l) {
                $p = $products[$l['product_id']] ?? null;
                $vid = (int) ($l['variant_id'] ?? 0);
                if (! $p || ($vid && ! $p->variants->firstWhere('id', $vid)) || (int) $l['quantity'] < 1) {
                    continue;
                }
                $v = $vid ? $p->variants->firstWhere('id', $vid) : null;
                $rows[] = [
                    'product' => $p, 'variant_id' => $vid, 'name' => $p->name.($v ? " ({$v->name})" : ''),
                    'quantity' => (int) $l['quantity'], 'unit_cost' => round((float) $l['unit_cost'], 2),
                ];
            }
            if (! $rows) {
                throw new RuntimeException('অন্তত একটি সঠিক পণ্য যোগ করুন।');
            }

            $subtotal = round(collect($rows)->sum(fn ($r) => $r['quantity'] * $r['unit_cost']), 2);
            $discount = round((float) ($meta['discount'] ?? 0), 2);
            $shipping = round((float) ($meta['shipping'] ?? 0), 2);
            $tax = round((float) ($meta['tax'] ?? 0), 2);
            $total = max(0, round($subtotal - $discount + $shipping + $tax, 2));

            $purchase = $branch->purchases()->create([
                'supplier_id' => $supplier->id, 'warehouse_id' => $warehouse->id,
                'number' => DocNumber::make('PUR', Purchase::class),
                'reference' => $meta['reference'] ?? null,
                'purchase_date' => $meta['purchase_date'] ?? now()->toDateString(),
                'subtotal' => $subtotal, 'discount' => $discount, 'shipping' => $shipping, 'tax' => $tax, 'total' => $total,
                'note' => $meta['note'] ?? null, 'actor_name' => Tenancy::actorName(),
            ]);

            foreach ($rows as $r) {
                $purchase->items()->create([
                    'product_id' => $r['product']->id, 'variant_id' => $r['variant_id'], 'name' => $r['name'],
                    'quantity' => $r['quantity'], 'unit_cost' => $r['unit_cost'], 'total' => round($r['quantity'] * $r['unit_cost'], 2),
                ]);
                $this->inventory->move($branch, $warehouse, $r['product']->id, $r['variant_id'], $r['quantity'], 'purchase', $purchase, $r['unit_cost'], "Purchase {$purchase->number}");
                // last purchase price becomes the product cost
                Product::whereKey($r['product']->id)->update(['purchase_price' => $r['unit_cost']]);
            }

            $paid = round((float) ($meta['paid'] ?? 0), 2);
            if ($paid > 0) {
                $this->pay($branch, $supplier, min($paid, $total), $meta['payment_method'] ?? 'cash', $meta['payment_reference'] ?? null, $purchase);
            }

            ActivityLog::record('purchase.created', "Purchase {$purchase->number} from {$supplier->name} ৳ {$total}", $purchase);

            return $purchase->fresh(['items', 'supplier']);
        });
    }

    public function pay(Branch $branch, Supplier $supplier, float $amount, string $method, ?string $reference = null, ?Purchase $purchase = null, ?string $date = null, ?string $note = null): SupplierPayment
    {
        abort_unless($supplier->branch_id === $branch->id, 403);
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be positive.');
        }

        return DB::transaction(function () use ($branch, $supplier, $amount, $method, $reference, $purchase, $date, $note) {
            $payment = SupplierPayment::create([
                'branch_id' => $branch->id, 'supplier_id' => $supplier->id, 'purchase_id' => $purchase?->id,
                'amount' => round($amount, 2), 'method' => $method, 'reference' => $reference,
                'payment_date' => $date ?? now()->toDateString(), 'note' => $note, 'actor_name' => Tenancy::actorName(),
            ]);
            if ($purchase) {
                $purchase->increment('paid', round($amount, 2));
            }
            ActivityLog::record('supplier.payment', "Paid ৳ {$amount} to {$supplier->name} ({$method})", $payment);

            return $payment;
        });
    }

    /** @param  array  $qty  [purchase_item_id => quantity] */
    public function returnItems(Branch $branch, Purchase $purchase, array $qty, ?string $note = null): PurchaseReturn
    {
        abort_unless($purchase->branch_id === $branch->id, 403);

        return DB::transaction(function () use ($branch, $purchase, $qty, $note) {
            $purchase = Purchase::whereKey($purchase->id)->lockForUpdate()->with(['items', 'warehouse', 'supplier'])->first();
            $lines = [];
            foreach ($purchase->items as $item) {
                $q = min((int) ($qty[$item->id] ?? 0), $item->quantity - $item->returned_qty);
                if ($q > 0) {
                    $lines[] = [$item, $q];
                }
            }
            if (! $lines) {
                throw new RuntimeException('ফেরত দেওয়ার পরিমাণ দিন।');
            }

            $total = round(collect($lines)->sum(fn ($l) => $l[1] * (float) $l[0]->unit_cost), 2);
            $return = PurchaseReturn::create([
                'branch_id' => $branch->id, 'purchase_id' => $purchase->id, 'supplier_id' => $purchase->supplier_id,
                'number' => DocNumber::make('PRT', PurchaseReturn::class), 'return_date' => now()->toDateString(),
                'total' => $total, 'note' => $note, 'actor_name' => Tenancy::actorName(),
            ]);
            foreach ($lines as [$item, $q]) {
                $return->items()->create(['purchase_item_id' => $item->id, 'quantity' => $q, 'unit_cost' => $item->unit_cost, 'total' => round($q * (float) $item->unit_cost, 2)]);
                $item->increment('returned_qty', $q);
                $this->inventory->move($branch, $purchase->warehouse, $item->product_id, (int) $item->variant_id, -$q, 'purchase_return', $return, (float) $item->unit_cost, "Return to {$purchase->supplier->name}", allowNegative: false);
            }
            $purchase->increment('returned', $total);
            ActivityLog::record('purchase.returned', "Purchase return {$return->number} ৳ {$total}", $return);

            return $return;
        });
    }
}
