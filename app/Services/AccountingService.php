<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountTransaction;
use App\Models\AccountTransfer;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\SupplierPayment;
use App\Models\Voucher;
use App\Support\DocNumber;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Single-entry cash book. Everything that moves money in or out of a branch account goes through post().
 * Order payments / refunds and supplier payments are posted automatically (model hooks).
 */
class AccountingService
{
    public const DEFAULT_ACCOUNTS = [
        ['Cash', 'cash', ['cash', 'cod'], true],
        ['Bank', 'bank', ['card', 'bank', 'cheque', 'shatajpay'], false],
        ['bKash', 'mobile', ['bkash'], false],
        ['Nagad', 'mobile', ['nagad'], false],
        ['Rocket', 'mobile', ['rocket'], false],
    ];

    public const DEFAULT_CATEGORIES = [
        'expense' => ['Rent', 'Salary', 'Electricity & Utility', 'Internet & Phone', 'Transport & Delivery', 'Marketing & Ads', 'Packaging', 'Repair & Maintenance', 'Office Supplies', 'Bank & Gateway Charges', 'Subscription Fee', 'Tax & VAT Paid', 'Others'],
        'income' => ['Service Income', 'Commission Received', 'Interest', 'Others'],
    ];

    protected static ?bool $ready = null;

    /** Accounting tables exist (false before `php artisan migrate`). */
    public static function ready(): bool
    {
        return static::$ready ??= Schema::hasTable('account_transactions');
    }

    public function ensureDefaults(Branch $branch): void
    {
        if (! Account::where('branch_id', $branch->id)->exists()) {
            foreach (self::DEFAULT_ACCOUNTS as [$name, $type, $methods, $default]) {
                Account::create(['branch_id' => $branch->id, 'name' => $name, 'type' => $type, 'methods' => $methods, 'is_default' => $default]);
            }
        }
        if (! AccountCategory::where('branch_id', $branch->id)->exists()) {
            foreach (self::DEFAULT_CATEGORIES as $kind => $names) {
                foreach ($names as $n) {
                    AccountCategory::create(['branch_id' => $branch->id, 'kind' => $kind, 'name' => $n]);
                }
            }
        }
    }

    /** The account a payment method lands in: the one listing that method, else the default (cash) account. */
    public function accountFor(Branch $branch, string $method): Account
    {
        $this->ensureDefaults($branch);
        $accounts = Account::where('branch_id', $branch->id)->where('is_active', true)->orderByDesc('is_default')->orderBy('id')->get();
        $match = $accounts->first(fn (Account $a) => in_array($method, $a->methods ?? [], true));

        return $match ?? $accounts->firstWhere('is_default', true) ?? $accounts->first()
            ?? Account::create(['branch_id' => $branch->id, 'name' => 'Cash', 'type' => 'cash', 'methods' => ['cash', 'cod'], 'is_default' => true]);
    }

    public function post(Branch $branch, Account $account, string $direction, float $amount, string $category, $date = null, ?Model $ref = null, ?string $description = null): ?AccountTransaction
    {
        $amount = round(abs($amount), 2);
        if ($amount <= 0) {
            return null;
        }
        abort_unless($account->branch_id === $branch->id, 404);
        $data = [
            'branch_id' => $branch->id, 'account_id' => $account->id, 'txn_date' => $date ? substr((string) $date, 0, 10) : now()->toDateString(),
            'direction' => $direction, 'amount' => $amount, 'category' => $category, 'description' => $description,
            'actor_name' => Tenancy::branch() ? Tenancy::actorName() : 'System',
        ];
        if ($ref) {
            // idempotent for auto-posted documents
            return AccountTransaction::firstOrCreate(
                ['reference_type' => $ref->getMorphClass(), 'reference_id' => $ref->getKey(), 'category' => $category, 'account_id' => $account->id],
                $data,
            );
        }

        return AccountTransaction::create($data);
    }

    /* ---------------- automatic postings ---------------- */

    public function postPayment(Payment $payment): void
    {
        if (! static::ready() || ! $payment->order_id || ! in_array($payment->status, ['success', 'refunded'], true) || $payment->gateway === 'wallet' || (float) $payment->amount == 0) {
            return;
        }
        $order = $payment->order;
        if (! $order || ! $order->branch) {
            return;
        }
        if (AccountTransaction::where('reference_type', $payment->getMorphClass())->where('reference_id', $payment->id)->exists()) {
            return;
        }
        $in = (float) $payment->amount > 0;
        $method = $payment->gateway === 'original' ? (string) $order->payment_method : $payment->gateway;
        if ($method === 'wallet') {
            return;
        }
        $this->post($order->branch, $this->accountFor($order->branch, $method), $in ? 'in' : 'out', (float) $payment->amount,
            $in ? 'sale' : 'refund', $payment->created_at ?? now(), $payment, ($in ? 'Sale ' : 'Refund ').$order->order_number);
    }

    /** A payment that was counted as received turned out failed → remove its cash-book line. */
    public function unpostPayment(Payment $payment): void
    {
        if (static::ready()) {
            AccountTransaction::where('reference_type', $payment->getMorphClass())->where('reference_id', $payment->id)->delete();
        }
    }

    public function postSupplierPayment(SupplierPayment $p): void
    {
        if (! static::ready()) {
            return;
        }
        $branch = Branch::find($p->branch_id);
        $this->post($branch, $this->accountFor($branch, $p->method), 'out', (float) $p->amount, 'supplier_payment', $p->payment_date, $p,
            'Paid to supplier '.($p->supplier?->name ?? '#'.$p->supplier_id));
    }

    /* ---------------- manual entries ---------------- */

    public function createVoucher(Branch $branch, array $data): Voucher
    {
        return DB::transaction(function () use ($branch, $data) {
            $account = Account::where('branch_id', $branch->id)->findOrFail($data['account_id']);
            $cat = AccountCategory::where('branch_id', $branch->id)->where('kind', $data['kind'])->findOrFail($data['account_category_id']);
            $v = Voucher::create($data + [
                'branch_id' => $branch->id,
                'number' => DocNumber::make($data['kind'] === 'expense' ? 'EXP' : 'INC', Voucher::class),
                'actor_name' => Tenancy::actorName(),
            ]);
            $this->post($branch, $account, $data['kind'] === 'expense' ? 'out' : 'in', (float) $v->amount, $data['kind'], $v->voucher_date, $v,
                $cat->name.($v->party ? ' — '.$v->party : ''));
            ActivityLog::record('voucher.created', ucfirst($v->kind)." {$v->number} ৳ {$v->amount} ({$cat->name})", $v);

            return $v;
        });
    }

    public function deleteVoucher(Voucher $v): void
    {
        DB::transaction(function () use ($v) {
            AccountTransaction::where('reference_type', $v->getMorphClass())->where('reference_id', $v->id)->delete();
            $v->delete();
        });
        ActivityLog::record('voucher.deleted', "Voucher {$v->number} deleted (৳ {$v->amount})");
    }

    public function transfer(Branch $branch, int $fromId, int $toId, float $amount, string $date, ?string $note = null): AccountTransfer
    {
        if ($fromId === $toId) {
            throw new RuntimeException('একই অ্যাকাউন্টে ট্রান্সফার করা যায় না।');
        }

        return DB::transaction(function () use ($branch, $fromId, $toId, $amount, $date, $note) {
            $from = Account::where('branch_id', $branch->id)->where('is_active', true)->findOrFail($fromId);
            $to = Account::where('branch_id', $branch->id)->where('is_active', true)->findOrFail($toId);
            $t = AccountTransfer::create(['branch_id' => $branch->id, 'from_account_id' => $from->id, 'to_account_id' => $to->id,
                'amount' => $amount, 'transfer_date' => $date, 'note' => $note, 'actor_name' => Tenancy::actorName()]);
            $this->post($branch, $from, 'out', $amount, 'transfer_out', $date, $t, "Transfer to {$to->name}".($note ? " — {$note}" : ''));
            $this->post($branch, $to, 'in', $amount, 'transfer_in', $date, $t, "Transfer from {$from->name}".($note ? " — {$note}" : ''));
            ActivityLog::record('account.transfer', "৳ {$amount} {$from->name} → {$to->name}", $t);

            return $t;
        });
    }

    public function deleteTransfer(AccountTransfer $t): void
    {
        DB::transaction(function () use ($t) {
            AccountTransaction::where('reference_type', $t->getMorphClass())->where('reference_id', $t->id)->delete();
            $t->delete();
        });
    }
}
