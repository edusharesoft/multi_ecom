<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);
            $table->decimal('price_lifetime', 10, 2)->nullable();   // null = lifetime not offered
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->decimal('commission_percent', 5, 2)->default(0); // platform commission on branch orders
            $table->boolean('is_free')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete(); // custom plan for one branch
            $table->json('limits');     // max_products, max_orders_month, max_customers, max_staff, max_warehouses, storage_mb, monthly_sales_limit  (-1 = unlimited)
            $table->json('features');   // pos, inventory, crm, coupon, reports, advanced_reports, api, custom_domain, whatsapp, sms, email_marketing
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('billing_cycle', 20)->default('monthly');  // monthly | yearly | lifetime | trial
            $table->string('status', 20)->default('pending');         // trial | active | pending | grace | expired | cancelled
            $table->decimal('amount', 10, 2)->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();                 // null = never (free / lifetime)
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('grace_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->foreignId('next_plan_id')->nullable()->constrained('plans')->nullOnDelete(); // scheduled downgrade
            $table->string('next_cycle', 20)->nullable();
            $table->json('reminders_sent')->nullable();               // e.g. {"2026-10-01":[7,3]}
            $table->timestamps();

            $table->index(['status', 'ends_at']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('new');               // new | renewal | upgrade | downgrade | custom
            $table->string('billing_cycle', 20);
            $table->decimal('amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);           // proration credit etc.
            $table->decimal('total', 10, 2);
            $table->string('status', 30)->default('unpaid');          // unpaid | pending_verification | paid | failed | cancelled
            $table->string('payment_method', 20)->nullable();         // shatajpay | bkash | nagad | bank | manual | free
            $table->string('transaction_id')->nullable();
            $table->string('sender_number', 30)->nullable();
            $table->text('notes')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()->after('order_id')->constrained()->cascadeOnDelete();
        });
        // Changing a column that is part of a FK: drop the FK first, change, then re-add it (MySQL/MariaDB safe)
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->timestamp('onboarded_at')->nullable()->after('mobile_verified_at');
            $table->timestamp('trial_used_at')->nullable()->after('onboarded_at');
            $table->unsignedBigInteger('storage_used')->default(0)->after('trial_used_at'); // bytes
            $table->string('favicon')->nullable()->after('logo');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('commission_percent', 5, 2)->default(0)->after('total');
            $table->decimal('commission_amount', 10, 2)->default(0)->after('commission_percent');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['commission_percent', 'commission_amount']));
        Schema::table('branches', fn (Blueprint $t) => $t->dropColumn(['onboarded_at', 'trial_used_at', 'storage_used', 'favicon']));
        Schema::table('payments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('invoice_id');
        });
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
