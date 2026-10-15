<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use App\Services\PurchaseService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class PurchaseController extends Controller
{
    public function __construct(protected PurchaseService $service) {}

    public function index(Request $request)
    {
        $branch = Tenancy::branch();
        $purchases = $branch->purchases()->with(['supplier', 'warehouse'])->withCount('items')
            ->when($request->filled('supplier'), fn ($q) => $q->where('supplier_id', $request->supplier))
            ->when(\App\Support\Dates::start($request->from), fn ($q, $d) => $q->where('purchase_date', '>=', $d->toDateString()))
            ->when(\App\Support\Dates::start($request->to), fn ($q, $d) => $q->where('purchase_date', '<=', $d->toDateString()))
            ->when($request->input('status') === 'due', fn ($q) => $q->whereRaw('total - returned - paid > 0'))
            ->latest('purchase_date')->latest('id')->paginate(20)->withQueryString();

        return view('manager.purchases.index', [
            'purchases' => $purchases,
            'suppliers' => $branch->suppliers()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        $branch = Tenancy::branch();

        return view('manager.purchases.create', [
            'suppliers' => $branch->suppliers()->where('is_active', true)->orderBy('name')->get(),
            'warehouses' => $branch->warehouses()->where('is_active', true)->orderByDesc('is_default')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $branch = Tenancy::branch();
        $data = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('branch_id', $branch->id)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('branch_id', $branch->id)],
            'purchase_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(array_keys(SupplierPayment::METHODS))],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $purchase = $this->service->create($branch, $branch->suppliers()->findOrFail($data['supplier_id']), $branch->warehouses()->findOrFail($data['warehouse_id']), $data['items'], $data);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        }

        return redirect()->route('manager.purchases.show', $purchase)->with('success', "ক্রয় {$purchase->number} সম্পন্ন — স্টক যোগ হয়েছে।");
    }

    public function show(Purchase $purchase)
    {
        $this->own($purchase);

        return view('manager.purchases.show', ['purchase' => $purchase->load(['items.product', 'supplier', 'warehouse', 'payments', 'returns.items'])]);
    }

    public function pay(Request $request, Purchase $purchase)
    {
        $this->own($purchase);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(array_keys(SupplierPayment::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);
        if ($purchase->due <= 0) {
            return back()->with('warning', 'এই ক্রয়ের কোনো বকেয়া নেই।');
        }
        try {
            $this->service->pay(Tenancy::branch(), $purchase->supplier, min((float) $data['amount'], $purchase->due), $data['method'], $data['reference'] ?? null, $purchase);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', 'পেমেন্ট রেকর্ড হয়েছে।');
    }

    public function storeReturn(Request $request, Purchase $purchase)
    {
        $this->own($purchase);
        $data = $request->validate(['qty' => ['required', 'array'], 'qty.*' => ['integer', 'min:0'], 'note' => ['nullable', 'string', 'max:500']]);
        try {
            $ret = $this->service->returnItems(Tenancy::branch(), $purchase, $data['qty'], $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', "পারচেজ রিটার্ন {$ret->number} — ৳ ".number_format((float) $ret->total, 2).' সাপ্লায়ারের হিসাবে কমেছে।');
    }

    protected function own(Purchase $purchase): void
    {
        abort_unless($purchase->branch_id === Tenancy::branch()->id, 404);
    }
}
