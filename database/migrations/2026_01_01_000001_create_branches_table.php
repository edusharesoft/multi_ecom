<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            // Registration fields (চিত্র ১)
            $table->string('name');                       // (a) Manager name
            $table->string('mobile', 20)->unique();       // (b) Mobile
            $table->string('email')->unique();            // (c) Email
            $table->string('slug', 60)->unique();         // (d) USER / Sub Folder: domain.com/{slug}
            $table->text('address');                      // (e) Address
            $table->string('password');                   // (f) Password

            // OTP verification
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('otp_code')->nullable();       // hashed
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('otp_sent_at')->nullable();
            $table->unsignedTinyInteger('otp_attempts')->default(0);

            // Store settings
            $table->string('store_name')->nullable();
            $table->string('status', 20)->default('active');   // active | inactive
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('hero_title')->nullable();
            $table->string('hero_subtitle')->nullable();
            $table->text('about')->nullable();
            $table->string('store_phone', 20)->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('map_url', 500)->nullable();
            $table->string('facebook_url', 500)->nullable();
            $table->string('bkash_number', 20)->nullable();
            $table->string('nagad_number', 20)->nullable();
            $table->boolean('cod_enabled')->default(true);
            $table->boolean('shatajpay_enabled')->default(true);
            $table->decimal('shipping_charge', 10, 2)->default(60);
            $table->decimal('tax_percent', 5, 2)->default(0);

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
