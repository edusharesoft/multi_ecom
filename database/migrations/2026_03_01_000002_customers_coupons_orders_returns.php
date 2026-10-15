<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 2: customer accounts (per branch), coupons, order upgrade, returns & refunds. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('group', 30)->default('general');     // general | wholesale | vip | ...
            $table->decimal('wallet_balance', 12, 2)->default(0);
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->text('notes')->nullable();                   // internal CRM notes
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->unique(['branch_id', 'phone']);
            $table->unique(['branch_id', 'email']);
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label', 30)->default('Home');
            $table->string('name');
            $table->string('phone', 20);
            $table->string('district', 60)->nullable();
            $table->string('upazila', 60)->nullable();
            $table->string('area', 100)->nullable();
            $table->text('address');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['customer_id', 'product_id']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);               // + credit / − debit
            $table->decimal('balance_after', 12, 2);
            $table->string('type', 30);                     // refund | order_payment | manual | cashback
            $table->string('description')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();   // null = global (Super Admin)
            $table->string('code', 40);
            $table->string('description')->nullable();
            $table->string('type', 10)->default('percent');   // percent | fixed
            $table->decimal('value', 10, 2);
            $table->decimal('min_order', 10, 2)->default(0);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->string('scope', 20)->default('all');      // all | products | categories
            $table->json('product_ids')->nullable();
            $table->json('category_ids')->nullable();
            $table->boolean('first_order_only')->default(false);
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete(); // customer-specific
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['branch_id', 'code']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->text('billing_address')->nullable()->after('shipping_address');
            $table->decimal('discount', 10, 2)->default(0)->after('subtotal');
            $table->string('coupon_code', 40)->nullable()->after('discount');
            $table->decimal('wallet_used', 10, 2)->default(0)->after('total');
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('wallet_used');
            $table->unsignedInteger('points_earned')->default(0)->after('refunded_amount');
            $table->boolean('points_credited')->default(false)->after('points_earned');
            $table->string('source', 20)->default('online')->after('status');   // online | pos
        });

        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20)->nullable();
            $table->decimal('discount', 10, 2);
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20);
            $table->string('note')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 30)->unique();
            $table->string('type', 20)->default('return');      // return | replacement | exchange
            $table->string('reason', 60);
            $table->text('details')->nullable();
            $table->json('items');                               // [{"order_item_id":1,"quantity":1}]
            $table->string('status', 20)->default('requested');  // requested | approved | rejected | pickup | received | inspected | refunded | replaced | exchanged
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->string('refund_method', 20)->nullable();     // wallet | original | cash | bkash | nagad
            $table->boolean('restock')->default(true);
            $table->text('admin_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('coupon_usages');
        Schema::table('orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('customer_id');
            $t->dropColumn(['billing_address', 'discount', 'coupon_code', 'wallet_used', 'refunded_amount', 'points_earned', 'points_credited', 'source']);
        });
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
