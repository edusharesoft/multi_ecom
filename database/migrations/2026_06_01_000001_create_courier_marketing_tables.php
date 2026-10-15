<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 5 — courier integration, message log (SMS / email), marketing campaigns. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['campaign_recipients', 'campaigns', 'message_logs', 'shipments', 'courier_accounts'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::enableForeignKeyConstraints();

        if (! Schema::hasColumn('customers', 'marketing_opt_out')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->boolean('marketing_opt_out')->default(false);
            });
        }

        Schema::create('courier_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('courier', 20);                 // steadfast | pathao | redx | manual
            $table->text('credentials')->nullable();       // encrypted JSON
            $table->string('mode', 10)->default('live');   // live | sandbox | demo
            $table->string('webhook_token', 40);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['branch_id', 'courier']);
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('courier', 20);
            $table->string('courier_name', 60)->nullable();          // for manual couriers (Sundarban, own rider…)
            $table->string('consignment_id', 60)->nullable();
            $table->string('tracking_code', 80)->nullable();
            $table->string('tracking_url')->nullable();
            $table->string('status', 60)->default('pending');        // raw courier status
            $table->string('status_group', 20)->default('pending');  // pending | in_transit | delivered | returned | cancelled
            $table->decimal('cod_amount', 12, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->nullable();
            $table->decimal('weight', 6, 2)->default(0.5);
            $table->string('note')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'status_group']);
            $table->index(['courier', 'consignment_id']);
            $table->index('tracking_code');
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('channel', 10);                  // sms | email
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('audience', 30)->default('all'); // all | segment:<x> | group:<x> | ordered_days:<n> | inactive_days:<n> | custom
            $table->text('custom_numbers')->nullable();
            $table->string('status', 15)->default('draft'); // draft | sending | sent | cancelled
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('sent')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('to', 150);
            $table->string('name', 120)->nullable();
            $table->string('status', 10)->default('pending');  // pending | sent | failed
            $table->string('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unique(['campaign_id', 'to']);
            $table->index(['campaign_id', 'status']);
        });

        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('channel', 10);                  // sms | email
            $table->string('to', 150);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('status', 10);                   // sent | failed | skipped
            $table->string('context', 40)->nullable();      // order.placed, campaign, otp…
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('segments')->default(1);
            $table->string('error')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['branch_id', 'channel', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('courier_accounts');
        if (Schema::hasColumn('customers', 'marketing_opt_out')) {
            Schema::table('customers', fn (Blueprint $t) => $t->dropColumn('marketing_opt_out'));
        }
    }
};
