<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 10 — offline POS: every sale made without internet carries a unique id from the device
 * (client_ref). When the sale is sent again after a lost response, the existing order is returned
 * instead of creating a duplicate. Idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'client_ref')) {
            Schema::table('orders', fn (Blueprint $t) => $t->string('client_ref', 40)->nullable()->after('source'));
        }
        if (! Schema::hasIndex('orders', 'orders_branch_client_ref_unique')) {
            Schema::table('orders', fn (Blueprint $t) => $t->unique(['branch_id', 'client_ref'], 'orders_branch_client_ref_unique'));
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('orders', 'orders_branch_client_ref_unique')) {
            try {
                Schema::table('orders', fn (Blueprint $t) => $t->dropUnique('orders_branch_client_ref_unique'));
            } catch (\Throwable) {
                // kept when MySQL uses it for the branch_id foreign key
            }
        }
        if (Schema::hasColumn('orders', 'client_ref')) {
            try {
                Schema::table('orders', fn (Blueprint $t) => $t->dropColumn('client_ref'));
            } catch (\Throwable) {
            }
        }
    }
};
