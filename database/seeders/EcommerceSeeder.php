<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Coupon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Phase 2 demo data: subcategories, brands, flash sale, coupons, a demo customer.
 * Safe to re-run:  php artisan db:seed --class=EcommerceSeeder
 */
class EcommerceSeeder extends Seeder
{
    public function run(): void
    {
        $subs = [
            "Women's Fashion" => ['Saree', 'Salwar Kameez', 'Kurti & Tops'],
            "Men's Fashion" => ['Panjabi', 'Shirt', 'T-Shirt'],
            'Kids Fashion' => ['Boys', 'Girls'],
        ];
        foreach ($subs as $parent => $children) {
            $p = Category::where('name', $parent)->first();
            if (! $p) {
                continue;
            }
            foreach ($children as $i => $name) {
                Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'parent_id' => $p->id, 'sort' => $i]);
            }
        }

        Coupon::updateOrCreate(['branch_id' => null, 'code' => 'WELCOME10'], [
            'description' => 'Platform welcome offer — first order', 'type' => 'percent', 'value' => 10, 'max_discount' => 300,
            'min_order' => 500, 'first_order_only' => true, 'is_active' => true,
        ]);

        Branch::whereNotNull('onboarded_at')->each(function (Branch $branch) {
            foreach (['Chandrabindu', 'Aarong Style', 'Deshi Dosh'] as $name) {
                $branch->brands()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
            }
            $brandIds = $branch->brands()->pluck('id');

            // assign brands + a flash sale on two products
            $branch->products()->whereNull('brand_id')->get()->each(function ($p, $i) use ($brandIds) {
                $p->update(['brand_id' => $brandIds[$i % max(1, $brandIds->count())] ?? null, 'purchase_price' => $p->purchase_price ?? round($p->price * 0.65)]);
            });
            $branch->products()->whereNull('sale_price')->take(2)->get()->each(fn ($p) => $p->update([
                'sale_price' => round($p->price * 0.8), 'sale_starts_at' => now(), 'sale_ends_at' => now()->addDays(3),
            ]));

            $branch->coupons()->updateOrCreate(['code' => 'SAVE100'], [
                'description' => '৳100 off on ৳1500+', 'type' => 'fixed', 'value' => 100, 'min_order' => 1500, 'is_active' => true,
            ]);

            $branch->customers()->firstOrCreate(['phone' => '01900000001'], [
                'name' => 'Demo Customer', 'email' => 'customer@example.com', 'password' => 'password',
            ]);
        });

        $this->command?->info('Demo customer (every store): 01900000001 / password · Coupons: WELCOME10 (global), SAVE100 (store)');
    }
}
