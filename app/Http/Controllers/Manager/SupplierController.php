<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\PurchaseService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Tenancy::branch()->suppliers()
            ->withSum('purchases as purchased', 'total')
            ->withSum('returns as returned', 'total')
            ->withSum('payments as paid', 'amount')
            ->withCount('purchases')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->q.'%')->orWhere('company', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')))
            ->orderBy('name')->get();
        foreach ($suppliers as $s) {
            $s->due_amount = round((float) $s->opening_balance + (float) $s->purchased - (float) $s->returned - (float) $s->paid, 2);
        }

        return view('manager.suppliers.index', ['suppliers' => $suppliers, 'totalDue' => $suppliers->sum('due_amount')]);
    }

    public function store(Request $request)
    {
        $s = Tenancy::branch()->suppliers()->create($this->validated($request));
        ActivityLog::record('supplier.created', "Supplier added: {$s->name}", $s);

        return redirect()->route('manager.suppliers.show', $s)->with('success', 'সাপ্লায়ার যোগ হয়েছে।');
    }

    public function show(Request $request, Supplier $supplier)
    {
        $this->own($supplier);

        return view('manager.suppliers.show', [
            'supplier' => $supplier,
            'ledger' => $supplier->ledger($request->from, $request->to),
            'due' => $supplier->due(),
            'purchases' => $supplier->purchases()->latest('purchase_date')->take(20)->get(),
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->own($supplier);
        $supplier->update($this->validated($request));

        return back()->with('success', 'আপডেট হয়েছে।');
    }

    public function destroy(Supplier $supplier)
    {
        $this->own($supplier);
        if ($supplier->purchases()->exists()) {
            return back()->with('warning', 'এই সাপ্লায়ারের ক্রয় আছে — মুছা যাবে না, Inactive করুন।');
        }
        $supplier->delete();

        return redirect()->route('manager.suppliers.index')->with('success', 'মুছে ফেলা হয়েছে।');
    }

    public function pay(Request $request, Supplier $supplier, PurchaseService $service)
    {
        $this->own($supplier);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', Rule::in(array_keys(SupplierPayment::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['required', 'date'],
            'purchase_id' => ['nullable', Rule::exists('purchases', 'id')->where('supplier_id', $supplier->id)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $purchase = isset($data['purchase_id']) ? $supplier->purchases()->find($data['purchase_id']) : null;
        try {
            $service->pay(Tenancy::branch(), $supplier, (float) $data['amount'], $data['method'], $data['reference'] ?? null, $purchase, $data['payment_date'], $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', 'পেমেন্ট রেকর্ড হয়েছে।');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);   // NOT NULL column

        return $data;
    }

    protected function own(Supplier $supplier): void
    {
        abort_unless($supplier->branch_id === Tenancy::branch()->id, 404);
    }
}
