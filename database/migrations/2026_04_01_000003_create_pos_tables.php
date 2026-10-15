<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 3 — POS register sessions (cash drawer), cash in/out, held sales. */
return new class extends Migration
{
    public function up(): void
    {
        // clean leftovers of a previously interrupted run (these tables are new in this migration)
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('pos_holds');
        Schema::dropIfExists('pos_cash_movements');
        Schema::dropIfExists('pos_sessions');
        Schema::enableForeignKeyConstraints();
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('opened_by');
            $table->string('closed_by')->nullable();
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('counted_cash', 12, 2)->nullable();
            $table->decimal('difference', 12, 2)->nullable();
            $table->json('summary')->nullable();          // totals per method at closing
            $table->text('note')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'closed_at']);
        });

        Schema::create('pos_cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_session_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);          // in | out
            $table->decimal('amount', 12, 2);
            $table->string('note')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();
        });

        Schema::create('pos_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100);
            $table->json('cart');
            $table->string('actor_name')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'pos_session_id')) {
                $table->foreignId('pos_session_id')->nullable()->after('warehouse_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'tendered')) {
                $table->decimal('tendered', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('orders', 'change_due')) {
                $table->decimal('change_due', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('orders', 'staff_name')) {
                $table->string('staff_name')->nullable();
            }
        });
        if (! Schema::hasColumn('order_items', 'returned_qty')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->integer('returned_qty')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $t) => $t->dropColumn('returned_qty'));
        Schema::table('orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('pos_session_id');
            $t->dropConstrainedForeignId('warehouse_id');
            $t->dropColumn(['tendered', 'change_due', 'staff_name']);
        });
        Schema::dropIfExists('pos_holds');
        Schema::dropIfExists('pos_cash_movements');
        Schema::dropIfExists('pos_sessions');
    }
};
