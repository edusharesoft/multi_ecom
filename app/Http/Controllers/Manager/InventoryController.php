<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\WarehouseStock;
use App\Services\InventoryService;
use App\Support\DocNumber;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/** Stock overview, ledger, alerts, adjustments, transfers, valuation. */
class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventory) {}

    /** Warehouse-wise stock + valuation. */
    public function stock(Request $request)
    {
        $branch = Tenancy::branch();
        $warehouses = $branch->warehouses()->orderByDesc('is_default')->get();
        $products = $branch->products()->with(['variants', 'category'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->q.'%')->orWhere('sku', 'like', '%'.$request->q.'%')->orWhere('barcode', $request->q)))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->category))
            ->when($request->input('filter') === 'low', fn ($q) => $q->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0))
            ->when($request->input('filter') === 'out', fn ($q) => $q->where('stock', '<=', 0))
            ->where('product_type', 'physical')
            ->orderBy('name')->paginate(30)->withQueryString();

        $split = WarehouseStock::whereIn('product_id', $products->pluck('id'))
            ->get()->groupBy(fn ($r) => $r->product_id.'-'.$r->variant_id)
            ->map(fn ($g) => $g->pluck('quantity', 'warehouse_id'));

        $valuation = [
            'units' => (int) $branch->products()->where('product_type', 'physical')->sum('stock'),
            'cost' => (float) $branch->products()->where('product_type', 'physical')->selectRaw('sum(stock * coalesce(purchase_price, 0)) as v')->value('v'),
            'retail' => (float) $branch->products()->where('product_type', 'physical')->selectRaw('sum(stock * price) as v')->value('v'),
            'low' => $branch->products()->where('product_type', 'physical')->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0)->count(),
            'out' => $branch->products()->where('product_type', 'physical')->where('stock', '<=', 0)->count(),
        ];

        if ($request->input('export') === 'csv' && Tenancy::can('inventory.export')) {
            $all = $branch->products()->with('variants')->where('product_type', 'physical')->orderBy('name')->get();

            return response()->streamDownload(function () use ($all) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Product', 'Variant', 'SKU', 'Stock', 'Min', 'Cost', 'Value']);
                foreach ($all as $p) {
                    foreach ($p->variants->isEmpty() ? [null] : $p->variants as $v) {
                        $qty = $v ? $v->stock : $p->stock;
                        fputcsv($out, [$p->name, $v?->name, $p->sku, $qty, $p->min_stock, $p->purchase_price, $qty * (float) $p->purchase_price]);
                    }
                }
                fclose($out);
            }, 'stock-'.now()->format('Ymd').'.csv');
        }

        return view('manager.inventory.stock', [
            'products' => $products, 'warehouses' => $warehouses, 'split' => $split, 'valuation' => $valuation,
            'categories' => Category::orderBy('sort')->get(),
        ]);
    }

    /** Stock ledger / movement history. */
    public function ledger(Request $request)
    {
        $branch = Tenancy::branch();
        $movements = StockMovement::where('branch_id', $branch->id)->with(['product', 'variant', 'warehouse', 'reference'])
            ->when($request->filled('warehouse'), fn ($q) => $q->where('warehouse_id', $request->warehouse))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('product'), fn ($q) => $q->where('product_id', $request->product))
            ->when(\App\Support\Dates::start($request->from), fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when(\App\Support\Dates::end($request->to), fn ($q, $d) => $q->where('created_at', '<=', $d))
            ->latest('id')->paginate(40)->withQueryString();

        return view('manager.inventory.ledger', [
            'movements' => $movements,
            'warehouses' => $branch->warehouses()->get(),
            'product' => $request->filled('product') ? $branch->products()->find($request->product) : null,
        ]);
    }

    /** JSON product lookup for item pickers (purchase, adjustment, transfer, POS). */
    public function lookup(Request $request)
    {
        abort_unless(Tenancy::can('inventory.view') || Tenancy::can('purchases.view') || Tenancy::can('pos.view'), 403);   // exposes cost prices
        $branch = Tenancy::branch();
        $term = trim((string) $request->query('q'));
        $warehouseId = (int) $request->query('warehouse');
        if ($term === '') {
            return response()->json([]);
        }

        $products = $branch->products()->with('variants')
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', $term)->orWhere('product_code', $term)->orWhere('barcode', $term))
            ->orderByRaw('barcode = ? desc', [$term])->take(15)->get();

        $stock = $warehouseId
            ? WarehouseStock::where('warehouse_id', $warehouseId)->whereIn('product_id', $products->pluck('id'))->get()->keyBy(fn ($r) => $r->product_id.'-'.$r->variant_id)
            : collect();

        $out = [];
        foreach ($products as $p) {
            $rows = $p->variants->isEmpty() ? [null] : $p->variants;
            foreach ($rows as $v) {
                $key = $p->id.'-'.($v?->id ?? 0);
                $out[] = [
                    'product_id' => $p->id,
                    'variant_id' => $v?->id ?? 0,
                    'name' => $p->name.($v ? ' — '.$v->name : ''),
                    'sku' => $p->sku,
                    'barcode' => $p->barcode,
                    'price' => $v ? $v->effectivePrice() : $p->currentPrice(),
                    'cost' => (float) ($p->purchase_price ?? 0),
                    'tax_rate' => (float) $p->tax_percent > 0 ? (float) $p->tax_percent : (float) $branch->tax_percent,   // same rule as PosService::checkout
                    'stock' => $warehouseId ? (int) ($stock[$key]->quantity ?? 0) : (int) ($v ? $v->stock : $p->stock),
                    'physical' => $p->product_type === 'physical',
                    'image' => $p->image_url,
                    'exact' => $p->barcode === $term || $p->sku === $term,
                ];
            }
        }

        return response()->json($out);
    }

    /* ---------------- Adjustments ---------------- */

    public function adjustments()
    {
        return view('manager.inventory.adjustments', [
            'adjustments' => StockAdjustment::where('branch_id', Tenancy::branch()->id)->with('warehouse')->withCount('items')->latest()->paginate(20),
            'warehouses' => Tenancy::branch()->warehouses()->where('is_active', true)->get(),
        ]);
    }

    public function storeAdjustment(Request $request)
    {
        $branch = Tenancy::branch();
        $data = $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('branch_id', $branch->id)],
            'type' => ['required', Rule::in(array_keys(StockAdjustment::TYPES))],
            'reason' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);
        $warehouse = $branch->warehouses()->findOrFail($data['warehouse_id']);

        try {
            $adj = DB::transaction(function () use ($branch, $warehouse, $data) {
                $adj = StockAdjustment::create([
                    'branch_id' => $branch->id, 'warehouse_id' => $warehouse->id, 'number' => DocNumber::make('ADJ', StockAdjustment::class),
                    'type' => $data['type'], 'reason' => $data['reason'] ?? null, 'note' => $data['note'] ?? null, 'actor_name' => Tenancy::actorName(),
                ]);
                $products = $branch->products()->with('variants')->whereIn('id', array_column($data['items'], 'product_id'))->get()->keyBy('id');
                foreach ($data['items'] as $line) {
                    $p = $products[$line['product_id']] ?? null;
                    $vid = (int) ($line['variant_id'] ?? 0);
                    if (! $p || ($vid && ! $p->variants->firstWhere('id', $vid))) {
                        continue;
                    }
                    $qty = (int) $line['quantity'];
                    $signed = $data['type'] === 'in' ? $qty : -$qty;
                    $type = ['in' => 'adjustment_in', 'out' => 'adjustment_out', 'damage' => 'damage'][$data['type']];
                    $adj->items()->create(['product_id' => $p->id, 'variant_id' => $vid, 'name' => $p->name.($vid ? ' — '.$p->variants->firstWhere('id', $vid)->name : ''), 'quantity' => $signed, 'unit_cost' => $p->purchase_price]);
                    $this->inventory->move($branch, $warehouse, $p->id, $vid, $signed, $type, $adj, (float) $p->purchase_price, $data['reason'] ?? null, allowNegative: $signed > 0);
                }
                if ($adj->items()->doesntExist()) {
                    throw new RuntimeException('কোনো সঠিক পণ্য নেই।');
                }

                return $adj;
            });
        } catch (RuntimeException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        }
        ActivityLog::record('inventory.adjusted', "Stock adjustment {$adj->number} ({$adj->type})", $adj);

        return redirect()->route('manager.inventory.adjustments')->with('success', "অ্যাডজাস্টমেন্ট {$adj->number} সম্পন্ন।");
    }

    /* ---------------- Transfers ---------------- */

    public function transfers()
    {
        $branch = Tenancy::branch();

        return view('manager.inventory.transfers', [
            'transfers' => StockTransfer::where('branch_id', $branch->id)->with(['from', 'to'])->withCount('items')->latest()->paginate(20),
            'warehouses' => $branch->warehouses()->where('is_active', true)->get(),
        ]);
    }

    public function storeTransfer(Request $request)
    {
        $branch = Tenancy::branch();
        $data = $request->validate([
            'from_warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('branch_id', $branch->id)],
            'to_warehouse_id' => ['required', 'different:from_warehouse_id', Rule::exists('warehouses', 'id')->where('branch_id', $branch->id)],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ], ['to_warehouse_id.different' => 'উৎস ও গন্তব্য ওয়্যারহাউস আলাদা হতে হবে।']);
        $from = $branch->warehouses()->findOrFail($data['from_warehouse_id']);
        $to = $branch->warehouses()->findOrFail($data['to_warehouse_id']);

        try {
            $tr = DB::transaction(function () use ($branch, $from, $to, $data) {
                $tr = StockTransfer::create([
                    'branch_id' => $branch->id, 'number' => DocNumber::make('TRF', StockTransfer::class),
                    'from_warehouse_id' => $from->id, 'to_warehouse_id' => $to->id, 'note' => $data['note'] ?? null, 'actor_name' => Tenancy::actorName(),
                ]);
                $products = $branch->products()->with('variants')->whereIn('id', array_column($data['items'], 'product_id'))->get()->keyBy('id');
                foreach ($data['items'] as $line) {
                    $p = $products[$line['product_id']] ?? null;
                    $vid = (int) ($line['variant_id'] ?? 0);
                    if (! $p || ($vid && ! $p->variants->firstWhere('id', $vid))) {
                        continue;
                    }
                    $qty = (int) $line['quantity'];
                    $tr->items()->create(['product_id' => $p->id, 'variant_id' => $vid, 'name' => $p->name.($vid ? ' — '.$p->variants->firstWhere('id', $vid)->name : ''), 'quantity' => $qty]);
                    // totals unchanged — stock only moves between warehouses
                    $this->inventory->move($branch, $from, $p->id, $vid, -$qty, 'transfer_out', $tr, null, "To {$to->name}", updateTotals: false, allowNegative: false);
                    $this->inventory->move($branch, $to, $p->id, $vid, $qty, 'transfer_in', $tr, null, "From {$from->name}", updateTotals: false);
                }
                if ($tr->items()->doesntExist()) {
                    throw new RuntimeException('কোনো সঠিক পণ্য নেই।');
                }

                return $tr;
            });
        } catch (RuntimeException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        }
        ActivityLog::record('inventory.transfer', "Stock transfer {$tr->number}: {$from->name} → {$to->name}", $tr);

        return redirect()->route('manager.inventory.transfers')->with('success', "ট্রান্সফার {$tr->number} সম্পন্ন।");
    }

    public function document(string $type, string $number)
    {
        $branchId = Tenancy::branch()->id;
        $doc = match ($type) {
            'adjustment' => StockAdjustment::where('branch_id', $branchId)->where('number', $number)->with(['items', 'warehouse'])->firstOrFail(),
            'transfer' => StockTransfer::where('branch_id', $branchId)->where('number', $number)->with(['items', 'from', 'to'])->firstOrFail(),
            default => abort(404),
        };

        return view('manager.inventory.document', compact('doc', 'type'));
    }
}
