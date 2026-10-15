<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — multi-warehouse inventory.
 * products.stock / product_variants.stock stay as the TOTAL across warehouses;
 * warehouse_stocks holds the per-warehouse split; stock_movements is the ledger.
 * variant_id = 0 means "product without variants".
 */
return new class extends Migration
{
    public function up(): void
    {
        // clean leftovers of a previously interrupted run (these tables are new in this migration)
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('warehouse_stocks');
        Schema::dropIfExists('warehouses');
        Schema::enableForeignKeyConstraints();
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->string('type', 20)->default('main');         // main | branch | outlet
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->default(0);
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->unique(['warehouse_id', 'product_id', 'variant_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->default(0);
            $table->string('type', 30);        // opening|purchase|purchase_return|sale|sale_return|pos_sale|pos_return|adjustment_in|adjustment_out|damage|transfer_in|transfer_out|cancel_restock
            $table->integer('quantity');       // + in / − out
            $table->integer('balance_after');  // warehouse balance after this movement
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->nullableMorphs('reference');
            $table->string('note')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['branch_id', 'product_id', 'variant_id']);
            $table->index(['branch_id', 'type', 'created_at']);
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->string('type', 20);            // in | out | damage
            $table->string('reason', 120)->nullable();
            $table->text('note')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->default(0);
            $table->string('name');
            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2)->nullable();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('number', 30)->unique();
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('variant_id')->default(0);
            $table->string('name');
            $table->integer('quantity');
        });

        // Opening stock: one "Main Warehouse" per branch holding today's stock
        $now = now();
        foreach (DB::table('branches')->pluck('id') as $branchId) {
            $wid = DB::table('warehouses')->insertGetId([
                'branch_id' => $branchId, 'name' => 'Main Warehouse', 'code' => 'MAIN', 'type' => 'main',
                'is_default' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $rows = [];
            foreach (DB::table('products')->where('branch_id', $branchId)->get(['id', 'stock', 'purchase_price']) as $p) {
                $variants = DB::table('product_variants')->where('product_id', $p->id)->get(['id', 'stock']);
                $lines = $variants->isEmpty() ? [[0, (int) $p->stock]] : $variants->map(fn ($v) => [$v->id, (int) $v->stock])->all();
                foreach ($lines as [$vid, $qty]) {
                    DB::table('warehouse_stocks')->insert(['warehouse_id' => $wid, 'product_id' => $p->id, 'variant_id' => $vid, 'quantity' => $qty, 'created_at' => $now, 'updated_at' => $now]);
                    if ($qty != 0) {
                        $rows[] = ['branch_id' => $branchId, 'warehouse_id' => $wid, 'product_id' => $p->id, 'variant_id' => $vid, 'type' => 'opening',
                            'quantity' => $qty, 'balance_after' => $qty, 'unit_cost' => $p->purchase_price, 'note' => 'Opening stock', 'actor_name' => 'System', 'created_at' => $now];
                    }
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('stock_movements')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('warehouse_stocks');
        Schema::dropIfExists('warehouses');
    }
};
