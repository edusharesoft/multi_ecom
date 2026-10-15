<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — simple single-entry accounting.
 * accounts = money accounts (cash, bank, bKash…); account_transactions = cash book (in/out);
 * vouchers = manual expense / income entries; account_transfers = money moved between accounts.
 * Sales/refunds (payments) and supplier payments are auto-posted by App\Services\AccountingService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['account_transactions', 'account_transfers', 'vouchers', 'account_categories', 'accounts'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 20)->default('cash');           // cash | bank | mobile | other
            $table->string('account_number', 60)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->json('methods')->nullable();                   // payment methods auto-posted here: ["cash","cod"]
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('account_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10);                             // expense | income
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['branch_id', 'kind', 'name']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->string('kind', 10);                             // expense | income
            $table->foreignId('account_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('voucher_date');
            $table->string('party', 150)->nullable();               // paid to / received from
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();
            $table->string('attachment')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'kind', 'voucher_date']);
        });

        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_account_id')->constrained('accounts')->restrictOnDelete();
            $table->foreignId('to_account_id')->constrained('accounts')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('transfer_date');
            $table->string('note')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();
        });

        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->date('txn_date');
            $table->string('direction', 3);                         // in | out
            $table->decimal('amount', 14, 2);                       // always positive
            $table->string('category', 30);                         // sale | refund | supplier_payment | expense | income | transfer_in | transfer_out | drawer_in | drawer_out
            $table->nullableMorphs('reference');
            $table->string('description')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'txn_date']);
            $table->index(['account_id', 'txn_date']);
            $table->unique(['reference_type', 'reference_id', 'category', 'account_id'], 'acct_txn_ref_unique');
        });

        $this->backfill();
        $this->grantRolePermissions();
    }

    /** One-time: give the new accounts.* permissions to existing system Manager / Accountant roles. */
    protected function grantRolePermissions(): void
    {
        $grant = [
            'Manager' => ['accounts.view', 'accounts.add', 'accounts.edit', 'accounts.delete'],
            'Accountant' => ['accounts.view', 'accounts.add', 'accounts.edit', 'purchases.view', 'suppliers.view'],
        ];
        foreach (DB::table('roles')->where('is_system', true)->whereIn('name', array_keys($grant))->get(['id', 'name', 'permissions']) as $role) {
            $perms = json_decode($role->permissions ?: '[]', true) ?: [];
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values(array_unique(array_merge($perms, $grant[$role->name]))))]);
        }
    }

    /** Default accounts & categories per branch, plus historical sales/refunds/supplier payments. */
    protected function backfill(): void
    {
        $now = now();
        $accounts = [
            ['Cash', 'cash', ['cash', 'cod'], true],
            ['Bank', 'bank', ['card', 'bank', 'cheque', 'shatajpay'], false],
            ['bKash', 'mobile', ['bkash'], false],
            ['Nagad', 'mobile', ['nagad'], false],
            ['Rocket', 'mobile', ['rocket'], false],
        ];
        $expense = ['Rent', 'Salary', 'Electricity & Utility', 'Internet & Phone', 'Transport & Delivery', 'Marketing & Ads', 'Packaging', 'Repair & Maintenance', 'Office Supplies', 'Bank & Gateway Charges', 'Subscription Fee', 'Tax & VAT Paid', 'Others'];
        $income = ['Service Income', 'Commission Received', 'Interest', 'Others'];

        foreach (DB::table('branches')->pluck('id') as $branchId) {
            $map = [];
            foreach ($accounts as [$name, $type, $methods, $default]) {
                $id = DB::table('accounts')->insertGetId([
                    'branch_id' => $branchId, 'name' => $name, 'type' => $type, 'methods' => json_encode($methods),
                    'opening_balance' => 0, 'is_default' => $default, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
                foreach ($methods as $m) {
                    $map[$m] = $id;
                }
            }
            $cats = [];
            foreach ($expense as $n) {
                $cats[] = ['branch_id' => $branchId, 'kind' => 'expense', 'name' => $n, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach ($income as $n) {
                $cats[] = ['branch_id' => $branchId, 'kind' => 'income', 'name' => $n, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
            }
            DB::table('account_categories')->insert($cats);

            // historical order payments
            $rows = [];
            $payments = DB::table('payments')->join('orders', 'orders.id', '=', 'payments.order_id')
                ->where('orders.branch_id', $branchId)->whereNotNull('payments.order_id')
                ->whereIn('payments.status', ['success', 'refunded'])->where('payments.gateway', '!=', 'wallet')
                ->get(['payments.id', 'payments.gateway', 'payments.amount', 'payments.created_at', 'orders.order_number', 'orders.payment_method']);
            foreach ($payments as $p) {
                // 'original' refunds go back through the order's own payment method (same as AccountingService::postPayment)
                $method = $p->gateway === 'original' ? (string) $p->payment_method : $p->gateway;
                if ((float) $p->amount == 0 || $method === 'wallet') {
                    continue;
                }
                $in = (float) $p->amount > 0;
                $rows[] = [
                    'branch_id' => $branchId, 'account_id' => $map[$method] ?? $map['cash'],
                    'txn_date' => substr((string) $p->created_at, 0, 10), 'direction' => $in ? 'in' : 'out', 'amount' => abs((float) $p->amount),
                    'category' => $in ? 'sale' : 'refund', 'reference_type' => 'App\\Models\\Payment', 'reference_id' => $p->id,
                    'description' => ($in ? 'Sale ' : 'Refund ').$p->order_number, 'actor_name' => 'System', 'created_at' => $now, 'updated_at' => $now,
                ];
            }
            // historical supplier payments
            if (Schema::hasTable('supplier_payments')) {
                $sp = DB::table('supplier_payments')->join('suppliers', 'suppliers.id', '=', 'supplier_payments.supplier_id')
                    ->where('supplier_payments.branch_id', $branchId)
                    ->get(['supplier_payments.id', 'supplier_payments.method', 'supplier_payments.amount', 'supplier_payments.payment_date', 'suppliers.name']);
                foreach ($sp as $p) {
                    $rows[] = [
                        'branch_id' => $branchId, 'account_id' => $map[$p->method] ?? $map['cash'],
                        'txn_date' => substr((string) $p->payment_date, 0, 10), 'direction' => 'out', 'amount' => (float) $p->amount,
                        'category' => 'supplier_payment', 'reference_type' => 'App\\Models\\SupplierPayment', 'reference_id' => $p->id,
                        'description' => 'Paid to supplier '.$p->name, 'actor_name' => 'System', 'created_at' => $now, 'updated_at' => $now,
                    ];
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('account_transactions')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
        Schema::dropIfExists('account_transfers');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('account_categories');
        Schema::dropIfExists('accounts');
    }
};
