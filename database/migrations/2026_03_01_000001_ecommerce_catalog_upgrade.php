<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 2: brands, subcategories, richer products, reviews, sliders, branch options. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120);
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['branch_id', 'slug']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('categories')->cascadeOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->after('brand_id')->constrained('categories')->nullOnDelete();
            $table->string('product_type', 20)->default('physical')->after('slug');   // physical | digital | service
            $table->string('product_code', 60)->nullable()->after('sku');
            $table->string('barcode', 80)->nullable()->after('product_code');
            $table->string('unit', 30)->default('pcs')->after('barcode');
            $table->decimal('purchase_price', 10, 2)->nullable()->after('price');
            $table->decimal('sale_price', 10, 2)->nullable()->after('old_price');     // flash sale / discount price
            $table->timestamp('sale_starts_at')->nullable()->after('sale_price');
            $table->timestamp('sale_ends_at')->nullable()->after('sale_starts_at');
            $table->decimal('tax_percent', 5, 2)->default(0)->after('sale_ends_at');
            $table->integer('min_stock')->default(5)->after('stock');
            $table->string('video_url', 500)->nullable()->after('image');
            $table->json('specifications')->nullable()->after('description');       // [{"key":"Fabric","value":"Cotton"}]
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('meta_keywords')->nullable();
            $table->unsignedInteger('sold_count')->default(0);
            $table->index(['branch_id', 'barcode']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('product_id');
            $table->unsignedBigInteger('order_id')->nullable()->after('customer_id');
            $table->boolean('is_verified')->default(false)->after('comment');   // verified purchase
            $table->string('image')->nullable()->after('is_verified');
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->unsignedInteger('reports')->default(0);
        });

        Schema::create('sliders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('image');
            $table->string('link', 500)->nullable();
            $table->string('button_text', 40)->nullable();
            $table->string('position', 20)->default('slider');   // slider | banner
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->json('options')->nullable()->after('tax_percent');   // review_auto_approve, points_per_100, invoice_note ...
        });

        Schema::table('subscribers', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropUnique(['email']);
            $table->unique(['branch_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            // FK first: MySQL may be using the composite unique index for the branch_id foreign key
            $table->dropForeign(['branch_id']);
            $table->dropUnique(['branch_id', 'email']);
            $table->dropColumn('branch_id');
            $table->unique('email');
        });
        Schema::table('branches', fn (Blueprint $t) => $t->dropColumn('options'));
        Schema::dropIfExists('sliders');
        Schema::table('reviews', fn (Blueprint $t) => $t->dropColumn(['customer_id', 'order_id', 'is_verified', 'image', 'reply', 'replied_at', 'reports']));
        Schema::table('products', function (Blueprint $t) {
            $t->dropIndex(['branch_id', 'barcode']);
            $t->dropConstrainedForeignId('brand_id');
            $t->dropConstrainedForeignId('subcategory_id');
            $t->dropColumn(['product_type', 'product_code', 'barcode', 'unit', 'purchase_price', 'sale_price', 'sale_starts_at', 'sale_ends_at',
                'tax_percent', 'min_stock', 'video_url', 'specifications', 'meta_title', 'meta_description', 'meta_keywords', 'sold_count']);
        });
        Schema::table('categories', fn (Blueprint $t) => $t->dropConstrainedForeignId('parent_id'));
        Schema::dropIfExists('brands');
    }
};
