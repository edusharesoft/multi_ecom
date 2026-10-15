<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index()
    {
        $branch = Tenancy::branch();
        $branch->defaultWarehouse();   // ensure one exists

        $stats = WarehouseStock::join('products', 'products.id', '=', 'warehouse_stocks.product_id')
            ->whereIn('warehouse_id', $branch->warehouses()->pluck('id'))
            ->groupBy('warehouse_id')
            ->selectRaw('warehouse_id, sum(warehouse_stocks.quantity) as units, sum(warehouse_stocks.quantity * coalesce(products.purchase_price, 0)) as value')
            ->get()->keyBy('warehouse_id');

        return view('manager.inventory.warehouses', [
            'warehouses' => $branch->warehouses()->with('manager')->orderByDesc('is_default')->orderBy('name')->get(),
            'stats' => $stats,
            'staff' => $branch->staff()->orderBy('name')->get(['id', 'name']),
            'limit' => $branch->planLimit('max_warehouses'),
        ]);
    }

    public function store(Request $request)
    {
        $branch = Tenancy::branch();
        if (! $branch->withinLimit('max_warehouses')) {
            return back()->with('warning', "আপনার প্ল্যানে সর্বোচ্চ {$branch->planLimit('max_warehouses')}টি ওয়্যারহাউস রাখা যায়।");
        }
        $w = $branch->warehouses()->create($this->validated($request));
        ActivityLog::record('warehouse.created', "Warehouse added: {$w->name}", $w);

        return back()->with('success', 'ওয়্যারহাউস যোগ হয়েছে।');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $this->own($warehouse);
        $data = $this->validated($request);
        DB::transaction(function () use ($warehouse, $data, $request) {
            if ($request->boolean('is_default')) {
                Tenancy::branch()->warehouses()->update(['is_default' => false]);
                $data['is_default'] = true;
            }
            $warehouse->update($data);
        });

        return back()->with('success', 'আপডেট হয়েছে।');
    }

    public function destroy(Warehouse $warehouse)
    {
        $this->own($warehouse);
        if ($warehouse->is_default) {
            return back()->with('warning', 'ডিফল্ট ওয়্যারহাউস মুছা যাবে না।');
        }
        if ($warehouse->stocks()->where('quantity', '!=', 0)->exists()) {
            return back()->with('warning', 'এই ওয়্যারহাউসে স্টক আছে — আগে ট্রান্সফার করুন।');
        }
        // purchases / POS sessions restrict the FK; ledger, adjustments & transfers would cascade away
        if (\App\Models\StockMovement::where('warehouse_id', $warehouse->id)->exists()
            || \App\Models\Purchase::where('warehouse_id', $warehouse->id)->exists()
            || \App\Models\PosSession::where('warehouse_id', $warehouse->id)->exists()) {
            return back()->with('warning', 'এই ওয়্যারহাউসের লেনদেনের ইতিহাস আছে — মুছা যাবে না, Inactive করুন।');
        }
        $warehouse->delete();

        return back()->with('success', 'মুছে ফেলা হয়েছে।');
    }

    protected function validated(Request $request): array
    {
        $branchId = Tenancy::branch()->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20'],
            'type' => ['required', Rule::in(array_keys(Warehouse::TYPES))],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'manager_id' => ['nullable', Rule::exists('staff', 'id')->where('branch_id', $branchId)],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }

    protected function own(Warehouse $warehouse): void
    {
        abort_unless($warehouse->branch_id === Tenancy::branch()->id, 404);
    }
}
