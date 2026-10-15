<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AccountCategory;
use App\Models\Voucher;
use App\Services\AccountingService;
use App\Services\ReportService;
use App\Support\Csv;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Expense & income vouchers. */
class VoucherController extends Controller
{
    public function __construct(protected AccountingService $accounting) {}

    public function index(Request $request)
    {
        $branch = Tenancy::branch();
        $this->accounting->ensureDefaults($branch);
        $kind = $request->query('kind') === 'income' ? 'income' : 'expense';
        [$from, $to] = ReportService::range($request->from, $request->to);

        $q = $branch->vouchers()->with(['category', 'account'])->where('kind', $kind)
            ->whereBetween('voucher_date', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('category'), fn ($q) => $q->where('account_category_id', $request->category))
            ->when($request->filled('account'), fn ($q) => $q->where('account_id', $request->account))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('party', 'like', '%'.$request->q.'%')->orWhere('number', 'like', '%'.$request->q.'%')->orWhere('note', 'like', '%'.$request->q.'%')));

        if ($request->query('export') === 'csv') {
            return Csv::download($kind.'s', ['Number', 'Date', 'Category', 'Account', 'Party', 'Reference', 'Amount', 'Note'],
                (clone $q)->orderBy('voucher_date')->get()->map(fn ($v) => [$v->number, $v->voucher_date->toDateString(), $v->category?->name, $v->account?->name, $v->party, $v->reference, (float) $v->amount, $v->note]));
        }

        $byCategory = (clone $q)->reorder()->selectRaw('account_category_id, sum(amount) amt')->groupBy('account_category_id')->pluck('amt', 'account_category_id');

        return view('manager.accounts.vouchers', [
            'kind' => $kind, 'from' => $from, 'to' => $to,
            'vouchers' => $q->latest('voucher_date')->latest('id')->paginate(30)->withQueryString(),
            'total' => (float) $byCategory->sum(),
            'byCategory' => $byCategory,
            'categories' => AccountCategory::where('branch_id', $branch->id)->where('kind', $kind)->orderBy('name')->get(),
            'accounts' => $branch->accounts()->where('is_active', true)->orderByDesc('is_default')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $branch = Tenancy::branch();
        $data = $request->validate([
            'kind' => ['required', Rule::in(['expense', 'income'])],
            'account_category_id' => ['required', Rule::exists('account_categories', 'id')->where('branch_id', $branch->id)->where('kind', $request->kind)->where('is_active', true)],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('branch_id', $branch->id)->where('is_active', true)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999'],
            'voucher_date' => ['required', 'date'],
            'party' => ['nullable', 'string', 'max:150'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
        ]);
        if ($request->hasFile('attachment')) {
            if (! $branch->hasStorageFor($request->file('attachment'))) {
                return back()->withInput()->with('warning', 'আপনার প্ল্যানের স্টোরেজ শেষ।');
            }
            $data['attachment'] = $branch->storeUpload($request->file('attachment'), "branches/{$branch->id}/vouchers");
        } else {
            unset($data['attachment']);
        }
        $v = $this->accounting->createVoucher($branch, $data);

        return redirect()->route('manager.vouchers.index', ['kind' => $v->kind])->with('success', ($v->kind === 'expense' ? 'খরচ' : 'আয়')." {$v->number} সংরক্ষিত হয়েছে।");
    }

    public function destroy(Voucher $voucher)
    {
        $branch = Tenancy::branch();
        abort_unless($voucher->branch_id === $branch->id, 404);
        $branch->deleteUpload($voucher->attachment);
        $this->accounting->deleteVoucher($voucher);

        return back()->with('success', 'ভাউচার মুছে ফেলা হয়েছে।');
    }
}
