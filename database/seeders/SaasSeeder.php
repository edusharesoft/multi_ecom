<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Plan;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Creates the default subscription plans and moves any existing branches onto the SaaS model.
 * Safe to run many times:  php artisan db:seed --class=SaasSeeder
 */
class SaasSeeder extends Seeder
{
    public function run(): void
    {
        $all = array_fill_keys(array_keys(Plan::FEATURES), true);
        $none = array_fill_keys(array_keys(Plan::FEATURES), false);

        $plans = [
            ['Free', 'শুরু করার জন্য', 0, 0, null, 0, 2, true,
                ['max_products' => 10, 'max_orders_month' => 30, 'max_customers' => 100, 'max_staff' => 0, 'max_warehouses' => 1, 'storage_mb' => 100, 'monthly_sales_limit' => 50000, 'sms_month' => 0, 'ai_requests_month' => 0],
                array_merge($none, ['reports' => true])],
            ['Basic', 'ছোট দোকানের জন্য', 499, 4990, null, 14, 1, false,
                ['max_products' => 50, 'max_orders_month' => 300, 'max_customers' => 1000, 'max_staff' => 2, 'max_warehouses' => 1, 'storage_mb' => 500, 'monthly_sales_limit' => -1, 'sms_month' => 500, 'ai_requests_month' => 30],
                array_merge($none, ['pos' => true, 'reports' => true, 'sms' => true, 'courier' => true, 'blog' => true, 'ai' => true])],
            ['Professional', 'বাড়তে থাকা ব্যবসার জন্য', 1499, 14990, null, 14, 0, false,
                ['max_products' => 1000, 'max_orders_month' => -1, 'max_customers' => -1, 'max_staff' => 10, 'max_warehouses' => 3, 'storage_mb' => 5120, 'monthly_sales_limit' => -1, 'sms_month' => 3000, 'ai_requests_month' => 300],
                array_merge($none, ['pos' => true, 'inventory' => true, 'accounting' => true, 'courier' => true, 'blog' => true, 'hrm' => true, 'ai' => true, 'crm' => true, 'coupon' => true, 'reports' => true, 'sms' => true, 'whatsapp' => true, 'email_marketing' => true])],
            ['Enterprise', 'বড় ব্যবসা ও চেইন শপ', 3999, 39990, 99990, 14, 0, false,
                array_fill_keys(array_keys(Plan::LIMITS), -1),
                $all],
        ];

        foreach ($plans as $i => [$name, $desc, $m, $y, $life, $trial, $commission, $free, $limits, $features]) {
            $existing = Plan::where('slug', strtolower($name))->first();
            if ($existing) {
                // keep the Super Admin's edits; only add feature / limit keys introduced by newer phases
                $existing->update([
                    'features' => ($existing->features ?? []) + $features,
                    'limits' => ($existing->limits ?? []) + $limits,
                ]);

                continue;
            }
            Plan::create(['slug' => strtolower($name),
                'name' => $name, 'description' => $desc, 'price_monthly' => $m, 'price_yearly' => $y, 'price_lifetime' => $life,
                'trial_days' => $trial, 'commission_percent' => $commission, 'is_free' => $free, 'is_active' => true,
                'is_featured' => $name === 'Professional', 'limits' => $limits, 'features' => $features, 'sort' => $i,
            ]);
        }

        // Existing branches (created before the SaaS upgrade): 14-day Professional trial, store already set up
        $pro = Plan::where('slug', 'professional')->first();
        Branch::doesntHave('subscription')->whereNotNull('mobile_verified_at')->eachById(function (Branch $branch) use ($pro) {
            $branch->subscription()->create([
                'plan_id' => $pro->id, 'billing_cycle' => 'monthly', 'status' => 'trial', 'amount' => $pro->price_monthly,
                'starts_at' => now(), 'ends_at' => now()->addDays(14), 'trial_ends_at' => now()->addDays(14), 'auto_renew' => true,
            ]);
            $branch->forceFill(['onboarded_at' => $branch->onboarded_at ?? now(), 'trial_used_at' => now()])->save();
        });

        Branch::whereNotNull('onboarded_at')->each(fn (Branch $b) => Role::createDefaultsFor($b));

        $this->command?->info('SaaS plans ready: Free, Basic, Professional, Enterprise');
    }
}
