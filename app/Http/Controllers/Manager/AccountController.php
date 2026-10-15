<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountTransfer;
use App\Models\ActivityLog;
use App\Services\AccountingService;
use App\Services\ReportService;
use App\Support\Csv;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/** Money accounts, categories, transfers and the cash book. */
class AccountController extends Controller
{
    public function __construct(protected AccountingService $accounting) {}

    public function index()
    {
        $branch = Tenancy::branch();
        $this->accounting->ensureDefaults($branch);
        $accounts = $branch->accounts()->orderByDesc('is_default')->orderBy('id')->get();
        foreach ($accounts as $a) {
            $a->current = $a->balance();
        }

        return view('manager.accounts.index', [
            'accounts' => $accounts,
            'total' => $accounts->where('is_active', true)->sum('current'),
            'categories' => AccountCategory::where('branch_id', $branch->id)->orderBy('kind')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $a = Tenancy::branch()->accounts()->create($this->validated($request));
        if ($a->is_default) {
            Account::where('branch_id', $a->branch_id)->whereKeyNot($a->id)->update(['is_default' => false]);
        }
        ActivityLog::record('account.created', "Account added: {$a->name}", $a);

        return back()->with('success', 'অ্যাকাউন্ট যোগ হয়েছে।');
    }

    public function update(Request $request, Account $account)
    {
        $this->own($account);
        $data = $this->validated($request);
        if ($account->is_default && empty($data['is_active'])) {
            return back()->with('warning', 'ডিফল্ট অ্যাকাউন্ট Inactive করা যাবে না।');
        }
        $data['is_default'] = $account->is_default || $data['is_default'];   // pick another default instead of un-setting it
        $account->update($data);
        if ($account->is_default) {
            Account::where('branch_id', $account->branch_id)->whereKeyNot($account->id)->update(['is_default' => false]);
        }

        return back()->with('success', 'আপডেট হয়েছে।');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Account::TYPES))],
            'account_number' => ['nullable', 'string', 'max:60'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'methods' => ['nullable', 'array'], 'methods.*' => [Rule::in(array_keys(Account::METHODS))],
            'opening_balance' => ['nullable', 'numeric'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['methods'] = array_values($data['methods'] ?? []);
        $data['opening_balance'] = (float) ($data['opening_balance'] ?? 0);
        $data['is_default'] = (bool) ($data['is_default'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }

    /* ---- categories ---- */

    public function storeCategory(Request $request)
    {
        $branch = Tenancy::branch();
        $data = $request->validate([
            'kind' => ['required', Rule::in(['expense', 'income'])],
            'name' => ['required', 'string', 'max:100', Rule::unique('account_categories')->where('branch_id', $branch->id)->where('kind', $request->kind)],
        ]);
        AccountCategory::create($data + ['branch_id' => $branch->id]);

        return back()->with('success', 'ক্যাটাগরি যোগ হয়েছে।');
    }

    public function toggleCategory(AccountCategory $category)
    {
        abort_unless($category->branch_id === Tenancy::branch()->id, 404);
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('success', 'আপডেট হয়েছে।');
    }

    /* ---- transfers ---- */

    public function transfers()
    {
        $branch = Tenancy::branch();

        return view('manager.accounts.transfers', [
            'accounts' => $branch->accounts()->where('is_active', true)->get(),
            'transfers' => AccountTransfer::where('branch_id', $branch->id)->with(['from', 'to'])->latest('transfer_date')->latest('id')->paginate(30),
        ]);
    }

    public function storeTransfer(Request $request)
    {
        $data = $request->validate([
            'from_account_id' => ['required', 'integer'], 'to_account_id' => ['required', 'integer', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'], 'transfer_date' => ['required', 'date'], 'note' => ['nullable', 'string', 'max:255'],
        ]);
        try {
            $this->accounting->transfer(Tenancy::branch(), (int) $data['from_account_id'], (int) $data['to_account_id'], (float) $data['amount'], $data['transfer_date'], $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        }

        return back()->with('success', 'ট্রান্সফার সম্পন্ন হয়েছে।');
    }

    public function destroyTransfer(AccountTransfer $transfer)
    {
        abort_unless($transfer->branch_id === Tenancy::branch()->id, 404);
        $this->accounting->deleteTransfer($transfer);
        ActivityLog::record('account.transfer_deleted', "Transfer #{$transfer->id} deleted (৳ {$transfer->amount})");

        return back()->with('success', 'ট্রান্সফার মুছে ফেলা হয়েছে।');
    }

    /* ---- cash book / day book ---- */

    public function cashbook(Request $request, ReportService $reports)
    {
        $branch = Tenancy::branch();
        $this->accounting->ensureDefaults($branch);
        [$from, $to] = ReportService::range($request->from, $request->to);
        $accountId = $request->filled('account') ? (int) $branch->accounts()->findOrFail($request->account)->id : null;
        $book = $reports->cashbook($branch, $from, $to, $accountId);

        if ($request->query('export') === 'csv') {
            return Csv::download('cashbook', ['Date', 'Account', 'Type', 'Description', 'In', 'Out', 'Balance'],
                $book['rows']->map(fn ($r) => [$r->txn_date->toDateString(), $r->account->name, $r->category, $r->description,
                    $r->direction === 'in' ? (float) $r->amount : '', $r->direction === 'out' ? (float) $r->amount : '', $r->running]));
        }

        return view('manager.accounts.cashbook', $book + ['from' => $from, 'to' => $to, 'accountId' => $accountId]);
    }

    protected function own(Account $a): void
    {
        abort_unless($a->branch_id === Tenancy::branch()->id, 404);
    }
}
