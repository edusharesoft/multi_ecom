<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9 — composite indexes for the queries that grow with data
 * (dashboards, reports, order/product lists, storefront listings, monthly plan usage).
 * Idempotent: every index is skipped when its table/columns are missing or it already exists,
 * so the migration can be re-run safely after a failed attempt.
 */
return new class extends Migration
{
    /** table => [index name => columns] */
    protected const INDEXES = [
        'orders' => [
            'perf_orders_branch_created' => ['branch_id', 'created_at'],
            'perf_orders_branch_payment' => ['branch_id', 'payment_status'],
            'perf_orders_branch_phone' => ['branch_id', 'customer_phone'],
            'perf_orders_branch_source' => ['branch_id', 'source', 'created_at'],
            'perf_orders_created' => ['created_at'],                       // platform-wide reports
        ],
        'products' => [
            'perf_products_active_created' => ['branch_id', 'is_active', 'created_at'],
            'perf_products_active_sold' => ['branch_id', 'is_active', 'sold_count'],
            'perf_products_active_price' => ['branch_id', 'is_active', 'price'],
            'perf_products_branch_sku' => ['branch_id', 'sku'],
            'perf_products_branch_code' => ['branch_id', 'product_code'],
        ],
        'reviews' => [
            'perf_reviews_product_approved' => ['product_id', 'is_approved', 'created_at'],
            'perf_reviews_customer' => ['customer_id'],
        ],
        'customers' => [
            'perf_customers_branch_created' => ['branch_id', 'created_at'],
        ],
        'payments' => [
            'perf_payments_txn' => ['transaction_id'],
            'perf_payments_order_status' => ['order_id', 'status'],
        ],
        'purchases' => [
            'perf_purchases_branch_date' => ['branch_id', 'purchase_date'],
        ],
        'return_requests' => [
            'perf_returns_branch_status' => ['branch_id', 'status'],
        ],
        'invoices' => [
            'perf_invoices_status_paid' => ['status', 'paid_at'],
        ],
        'campaigns' => [
            'perf_campaigns_status' => ['status'],
        ],
        'sliders' => [
            'perf_sliders_branch_active' => ['branch_id', 'is_active', 'position'],
        ],
        'pages' => [
            'perf_pages_branch_published' => ['branch_id', 'is_published'],
        ],
        'domains' => [
            'perf_domains_branch_status' => ['branch_id', 'status'],
        ],
        'message_logs' => [
            'perf_msglogs_branch_status' => ['branch_id', 'channel', 'status', 'created_at'],
        ],
        'shipments' => [
            'perf_shipments_status_group' => ['status_group', 'courier'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($indexes as $name => $columns) {
                if (! Schema::hasColumns($table, $columns) || $this->exists($table, $name, $columns)) {
                    continue;
                }
                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (array_keys($indexes) as $name) {
                if (! Schema::hasIndex($table, $name)) {
                    continue;
                }
                try {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                } catch (\Throwable) {
                    // MySQL keeps an index that a foreign key now depends on — harmless to leave it
                }
            }
        }
    }

    /** true when an index with this name, or any index starting with exactly these columns, already exists. */
    protected function exists(string $table, string $name, array $columns): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (strtolower($index['name']) === strtolower($name)) {
                return true;
            }
            if (array_slice(array_map('strtolower', $index['columns']), 0, count($columns)) === array_map('strtolower', $columns)) {
                return true;
            }
        }

        return false;
    }
};
