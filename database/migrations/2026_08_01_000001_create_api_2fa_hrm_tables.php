<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Phase 7 — REST API tokens + webhooks, two-factor auth columns, HRM (employees, attendance, leave, payroll). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['payroll_items', 'payrolls', 'leave_requests', 'attendances', 'employees', 'webhook_deliveries', 'api_tokens'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::enableForeignKeyConstraints();

        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('token_hash', 64)->unique();       // sha256 of the plain token (plain shown once)
            $table->string('prefix', 12);                       // first chars, to recognise a token in the list
            $table->json('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('event', 40);
            $table->string('url');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->boolean('success')->default(false);
            $table->string('response', 500)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['branch_id', 'created_at']);
        });

        foreach (['branches', 'staff', 'users'] as $t) {
            if (! Schema::hasColumn($t, 'two_factor_secret')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->text('two_factor_secret')->nullable();
                    $table->text('two_factor_recovery_codes')->nullable();
                    $table->timestamp('two_factor_confirmed_at')->nullable();
                });
            }
        }

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();   // optional login account
            $table->string('code', 20)->nullable();
            $table->string('name', 120);
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('designation', 80)->nullable();
            $table->string('department', 80)->nullable();
            $table->date('join_date')->nullable();
            $table->string('salary_type', 10)->default('monthly');   // monthly | daily
            $table->decimal('basic_salary', 12, 2)->default(0);     // per month, or per day for daily
            $table->decimal('allowance', 12, 2)->default(0);        // fixed monthly allowance
            $table->string('address')->nullable();
            $table->string('emergency_contact', 120)->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 10);            // present | late | half_day | absent | leave | holiday
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->string('note')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'date']);
            $table->index(['branch_id', 'date']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 12);              // casual | sick | annual | unpaid
            $table->date('from_date');
            $table->date('to_date');
            $table->unsignedSmallInteger('days');
            $table->string('reason')->nullable();
            $table->string('status', 10)->default('pending');   // pending | approved | rejected
            $table->string('decided_by')->nullable();
            $table->timestamps();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('month', 7);              // 2026-09
            $table->string('status', 10)->default('draft');     // draft | paid
            $table->unsignedTinyInteger('working_days')->default(30);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->string('actor_name')->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'month']);
        });

        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('present_days')->default(0);
            $table->unsignedTinyInteger('absent_days')->default(0);
            $table->unsignedTinyInteger('unpaid_leave_days')->default(0);
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->decimal('basic', 12, 2)->default(0);
            $table->decimal('allowance', 12, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('deduction', 12, 2)->default(0);        // absence / unpaid leave (auto)
            $table->decimal('other_deduction', 12, 2)->default(0);  // advance, fine… (manual)
            $table->decimal('net', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('voucher_number', 30)->nullable();
            $table->unique(['payroll_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        foreach (['payroll_items', 'payrolls', 'leave_requests', 'attendances', 'employees', 'webhook_deliveries', 'api_tokens'] as $t) {
            Schema::dropIfExists($t);
        }
        foreach (['branches', 'staff', 'users'] as $t) {
            if (Schema::hasColumn($t, 'two_factor_secret')) {
                Schema::table($t, fn (Blueprint $table) => $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']));
            }
        }
    }
};
