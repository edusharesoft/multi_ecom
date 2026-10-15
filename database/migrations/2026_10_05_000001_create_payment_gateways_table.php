<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dynamic ShatajPay credentials (same idea as the official "paymentgetways" table),
 * but tenant-aware:
 *   branch_id = NULL  -> platform account (Super Admin). Used for SaaS subscription
 *                        invoices, and for store orders of branches without their own account.
 *   branch_id = X     -> that branch's own ShatajPay merchant account (money goes to the branch).
 * Secrets are stored encrypted (APP_KEY).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_gateways')) {
            return;
        }

        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('gateway', 30)->default('shatajpay');      // getwayname
            $table->string('display_name', 100)->nullable();           // displayname
            $table->string('logo')->nullable();
            $table->string('gateway_id')->nullable();                  // getway_id
            $table->text('app_key')->nullable();                       // getway_storeid   (encrypted)
            $table->text('app_secret')->nullable();                    // getway_appsecret (encrypted)
            $table->text('app_password')->nullable();                  // getway_storepassword (encrypted)
            $table->string('app_username')->nullable();                // getway_username
            $table->string('mode', 20)->default('live');               // live | sandbox
            $table->string('base_url')->nullable();                    // empty = config default
            $table->boolean('status')->default(false);                 // 1 = active
            $table->timestamps();

            $table->unique(['branch_id', 'gateway']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
