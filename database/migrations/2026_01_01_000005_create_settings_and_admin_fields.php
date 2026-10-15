<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Super admin controls
        Schema::table('branches', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->string('suspend_reason')->nullable()->after('suspended_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_super_admin'));
        Schema::table('branches', fn (Blueprint $t) => $t->dropColumn(['suspended_at', 'suspend_reason']));
        Schema::dropIfExists('settings');
    }
};
