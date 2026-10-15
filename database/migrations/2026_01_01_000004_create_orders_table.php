<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 40)->unique();
            $table->string('customer_name');
            $table->string('customer_phone', 20);
            $table->string('customer_email')->nullable();
            $table->text('shipping_address');
            $table->text('note')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('payment_method', 20);                  // cod | shatajpay | bkash | nagad
            $table->string('payment_status', 30)->default('unpaid'); // unpaid | pending_verification | paid | failed
            $table->string('status', 20)->default('pending');       // pending | confirmed | processing | shipped | delivered | cancelled
            $table->string('transaction_id')->nullable();
            $table->string('sender_number', 20)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 20);          // shatajpay | bkash | nagad | cod
            $table->string('reference', 64)->unique(); // our reference sent to gateway
            $table->decimal('amount', 12, 2);
            $table->string('currency', 5)->default('BDT');
            $table->string('transaction_id')->nullable(); // gateway transaction id
            $table->string('status', 20)->default('initiated'); // initiated | success | failed | cancelled
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
