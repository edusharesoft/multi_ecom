<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Single entry point for every stock change.
 *  - warehouse_stocks: quantity per warehouse (variant_id 0 = simple product)
 *  - products.stock / product_variants.stock: total across warehouses
 *  - stock_movements: ledger row for each change
 */
class InventoryService
{
    /**
     * @param  int  $qty  + stock in / − stock out
     * @param  bool  $updateTotals  false when the caller already changed products/variants.stock (online checkout)
     * @param  bool  $allowNegative  false = throw when the warehouse would go below 0
     */
    public function move(Branch $branch, Warehouse $warehouse, int $productId, int $variantId, int $qty, string $type,
        ?Model $reference = null, ?float $unitCost = null, ?string $note = null, bool $updateTotals = true, bool $allowNegative = true): StockMovement
    {
        abort_unless($warehouse->branch_id === $branch->id, 403);

        return DB::transaction(function () use ($branch, $warehouse, $productId, $variantId, $qty, $type, $reference, $unitCost, $note, $updateTotals, $allowNegative) {
            $row = WarehouseStock::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'product_id' => $productId, 'variant_id' => $variantId],
                ['quantity' => 0]
            );
            $row = WarehouseStock::whereKey($row->id)->lockForUpdate()->first();
            $new = (int) $row->quantity + $qty;
            if (! $allowNegative && $new < 0) {
                $name = Product::whereKey($productId)->value('name');
                throw new RuntimeException("{$name} — {$warehouse->name} এ পর্যাপ্ত স্টক নেই (আছে {$row->quantity})।");
            }
            $row->update(['quantity' => $new]);

            if ($updateTotals && $qty !== 0) {
                if ($variantId) {
                    ProductVariant::whereKey($variantId)->increment('stock', $qty);
                    $this->syncProductTotal($productId);
                } else {
                    Product::whereKey($productId)->increment('stock', $qty);
                }
            }

            return StockMovement::create([
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'type' => $type,
                'quantity' => $qty,
                'balance_after' => $new,
                'unit_cost' => $unitCost,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'note' => $note,
                'actor_name' => Tenancy::actorName(),
            ]);
        });
    }

    public function available(Warehouse $warehouse, int $productId, int $variantId = 0): int
    {
        return (int) WarehouseStock::where(['warehouse_id' => $warehouse->id, 'product_id' => $productId, 'variant_id' => $variantId])->value('quantity');
    }

    /** products.stock = Σ variant stock for variable products. */
    public function syncProductTotal(int $productId): void
    {
        if (ProductVariant::where('product_id', $productId)->exists()) {
            Product::whereKey($productId)->update(['stock' => (int) ProductVariant::where('product_id', $productId)->sum('stock')]);
        }
    }

    /**
     * After the product form saved new totals, push the difference into the default warehouse
     * (so warehouse split + ledger stay consistent) and drop rows of deleted variants.
     */
    public function reconcile(Product $product, string $note = 'Stock edited from product form'): void
    {
        $branch = $product->branch;
        $warehouse = $branch->defaultWarehouse();
        $product->load('variants');
        $variantIds = $product->variants->pluck('id')->all();

        DB::transaction(function () use ($product, $branch, $warehouse, $variantIds, $note) {
            // remove rows for deleted variants / variant-type switch
            WarehouseStock::where('product_id', $product->id)
                ->when($variantIds, fn ($q) => $q->whereNotIn('variant_id', $variantIds), fn ($q) => $q->where('variant_id', '!=', 0))
                ->delete();
            if ($variantIds) {
                WarehouseStock::where('product_id', $product->id)->where('variant_id', 0)->delete();
            }

            $lines = $product->variants->isEmpty()
                ? [[0, (int) $product->stock]]
                : $product->variants->map(fn ($v) => [$v->id, (int) $v->stock])->all();

            foreach ($lines as [$vid, $total]) {
                $inWarehouses = (int) WarehouseStock::where('product_id', $product->id)->where('variant_id', $vid)->sum('quantity');
                $diff = $total - $inWarehouses;
                if ($diff !== 0) {
                    $type = $inWarehouses === 0 && ! StockMovement::where('product_id', $product->id)->where('variant_id', $vid)->exists() ? 'opening' : ($diff > 0 ? 'adjustment_in' : 'adjustment_out');
                    $this->move($branch, $warehouse, $product->id, $vid, $diff, $type, $product, (float) $product->purchase_price, $note, updateTotals: false);
                }
            }
        });
    }

    /** Stock of one product/variant per warehouse. */
    public function split(Branch $branch, int $productId, int $variantId = 0): array
    {
        return WarehouseStock::whereIn('warehouse_id', $branch->warehouses()->pluck('id'))
            ->where('product_id', $productId)->where('variant_id', $variantId)
            ->pluck('quantity', 'warehouse_id')->all();
    }
}
