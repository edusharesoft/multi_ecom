<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Super Admin',
            'password' => 'password',
            'is_super_admin' => true,
        ]);

        $cats = collect([
            ["Men's Fashion", 'Stylish collection'],
            ["Women's Fashion", 'Latest trends'],
            ['Kids Fashion', 'Cute & comfortable'],
            ['Accessories', 'Complete your look'],
            ['Lifestyle', 'Home & more'],
        ])->map(fn ($c, $i) => Category::updateOrCreate(['slug' => Str::slug($c[0])], ['name' => $c[0], 'tagline' => $c[1], 'sort' => $i]));

        $branches = [
            ['Barishal Branch', 'barishal', '01711000001', 'Sadar Road, Barishal', '01811000001'],
            ['Bhola Branch', 'bhola', '01711000002', 'Sadar Road, Bhola', '01811000002'],
            ['Jhalokathi Branch', 'jhalokathi', '01711000003', 'Kalibari Road, Jhalokathi', null],
        ];

        $catalog = [
            ["Women's Fashion", 'Premium Embroidered 3-Piece Salwar Kameez', 3240, 3800],
            ["Women's Fashion", 'Elegant Printed 3-Piece Salwar Kameez', 2520, null],
            ["Women's Fashion", 'Cotton 3pcs', 2880, 3200],
            ["Women's Fashion", 'Turquoise Blue Floral Designer Saree', 1710, 2100],
            ["Men's Fashion", 'Premium Cotton Panjabi', 2350, null],
            ["Men's Fashion", 'Slim Fit Casual Shirt', 1450, 1650],
            ['Kids Fashion', 'Kids Party Frock', 1250, null],
            ['Accessories', 'Leather Hand Bag', 1990, 2400],
        ];
        $sizes = ['S: 34-36', 'M: 38', 'L: 40', 'XL: 42', 'XXL: 44'];

        foreach ($branches as $bi => [$name, $slug, $mobile, $address, $bkash]) {
            $branch = Branch::updateOrCreate(['slug' => $slug], [
                'name' => $name.' Manager',
                'store_name' => $name,
                'mobile' => $mobile,
                'email' => $slug.'@example.com',
                'address' => $address,
                'password' => 'password',
                'bkash_number' => $bkash,
                'nagad_number' => $bkash,
                'store_phone' => $mobile,
                'opening_hours' => '10:00 AM – 10:00 PM',
                'hero_title' => $name,
                'hero_subtitle' => 'Enjoy flat 20% discount on all products',
                'about' => "Welcome to {$name}. Quality fashion for the whole family.",
            ]);
            $branch->forceFill(['mobile_verified_at' => now()])->save();

            foreach ($catalog as $pi => [$cat, $pname, $price, $old]) {
                $product = $branch->products()->updateOrCreate(['slug' => Str::slug($pname)], [
                    'category_id' => $cats->firstWhere('name', $cat)->id,
                    'name' => $pname,
                    'sku' => sprintf('SKU-%02d%03d', $bi + 1, $pi + 1),
                    'description' => "{$pname}\n\n• Premium quality fabric\n• Comfortable fit\n• Easy to wash",
                    'price' => $price,
                    'old_price' => $old,
                    'stock' => 20,
                    'is_featured' => $pi < 3,
                    'is_active' => true,
                ]);

                if ($cat !== 'Accessories' && $product->variants()->doesntExist()) {
                    foreach ($sizes as $s) {
                        $product->variants()->create(['name' => "{$s} - Maroon", 'stock' => rand(0, 8)]);
                    }
                    $product->update(['stock' => $product->variants()->sum('stock')]);
                }

                if ($product->reviews()->doesntExist()) {
                    $product->reviews()->create(['name' => ['Rahim', 'Sadia', 'Nusrat', 'Karim'][$pi % 4], 'rating' => 5 - ($pi % 2), 'comment' => 'Good quality product! Delivery was fast.']);
                }
            }
        }

        $this->call(SaasSeeder::class);        // plans + subscriptions/roles for the demo branches
        $this->call(EcommerceSeeder::class);   // subcategories, brands, flash sale, coupons, demo customer

        $this->command?->info('Super Admin: /admin/login — admin@example.com / password');
        $this->command?->info('Demo branches: barishal / bhola / jhalokathi  —  login: {slug}@example.com / password');
    }
}
