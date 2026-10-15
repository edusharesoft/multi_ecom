<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 6 — custom pages, blog, custom domains. (Theme & homepage builder live in branches.options JSON.) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['domains', 'posts', 'blog_categories', 'pages'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->string('slug', 160);
            $table->longText('content')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_header')->default(false);
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'slug']);
        });

        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 110);
            $table->timestamps();
            $table->unique(['branch_id', 'slug']);
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 200);
            $table->string('slug', 210);
            $table->string('excerpt', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('cover')->nullable();
            $table->string('tags')->nullable();                 // comma separated
            $table->string('status', 10)->default('draft');     // draft | published
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->string('author_name', 100)->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'slug']);
            $table->index(['branch_id', 'status', 'published_at']);
        });

        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('host', 190)->unique();              // shop.example.com (lower-case, no www handling here)
            $table->string('status', 12)->default('pending');   // pending | verified | active | rejected
            $table->string('verify_token', 40);
            $table->boolean('is_primary')->default(false);
            $table->boolean('redirect_to_domain')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->string('check_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('pages');
    }
};
