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

        $categoryImages = [
            "Men's Fashion" => 'demo-categories/men-torch.jpg',
            "Women's Fashion" => 'demo-categories/strawberry-gel.jpg',
            'Kids Fashion' => 'demo-categories/kids-potty.jpg',
            'Accessories' => 'demo-categories/repair-tape.jpg',
            'Lifestyle' => 'demo-categories/stain-remover.jpg',
        ];
        $cats = collect([
            ["Men's Fashion", 'Stylish collection'],
            ["Women's Fashion", 'Latest trends'],
            ['Kids Fashion', 'Cute & comfortable'],
            ['Accessories', 'Complete your look'],
            ['Lifestyle', 'Home & more'],
        ])->map(fn ($c, $i) => Category::updateOrCreate(['slug' => Str::slug($c[0])], ['name' => $c[0], 'tagline' => $c[1], 'sort' => $i, 'image' => $categoryImages[$c[0]] ?? null]));

        $branches = [
            ['Barishal Branch', 'barishal', '01711000001', 'Sadar Road, Barishal', '01811000001'],
            ['Bhola Branch', 'bhola', '01711000002', 'Sadar Road, Bhola', '01811000002'],
            ['Jhalokathi Branch', 'jhalokathi', '01711000003', 'Kalibari Road, Jhalokathi', null],
            ['Apon Hat', 'aponhat', '01711000004', 'House-9/31, Mirpur-13/B, Dhaka 1216', '01811000004'],
            ['Proyjon Food Corner', 'pfc', '01711000005', 'House-9/31, Mirpur-13/B, Dhaka 1216', '01811000005'],
            ['Mosaeb Store', 'edushare', '01711000006', 'Mirpur-13, Dhaka', '01811000006'],
            ['Dhaka Trendy Shop', 'dhaka-trendy', '01711000007', 'Dhanmondi, Dhaka', '01811000007'],
            ['Chattogram Collection', 'chattogram-collection', '01711000008', 'GEC Circle, Chattogram', '01811000008'],
            ['Rajshahi Mart', 'rajshahi-mart', '01711000009', 'Shaheb Bazar, Rajshahi', '01811000009'],
            ['Sylhet Lifestyle', 'sylhet-lifestyle', '01711000010', 'Zindabazar, Sylhet', '01811000010'],
        ];

        // Five genuinely different, image-matched products for every shop (50 total).
        // The shop qualifier is part of the name/slug so no shop reuses another shop's product.
        $themes = [
            ['Accessories', 'Waterproof Repair Tape', 290, 'demo-categories/repair-tape.jpg', 'ছাদ, পাইপ ও দেয়ালের লিকেজ বন্ধ করার জন্য বুটাইল রাবার সিল টেপ। পানি ও UV প্রতিরোধী।'],
            ['Kids Fashion', 'Soft Baby Potty Seat', 550, 'demo-categories/kids-potty.jpg', 'শিশুর টয়লেট প্রশিক্ষণের নিরাপদ নরম সিট। হ্যান্ডেল, স্প্ল্যাশ প্রটেক্টর ও সহজে পরিষ্কারযোগ্য ডিজাইন।'],
            ["Men's Fashion", 'Rechargeable LED Torch', 750, 'demo-categories/men-torch.jpg', 'তিনটি মোডের ওয়াটারপ্রুফ LED টর্চ। দীর্ঘস্থায়ী ব্যাটারি ও অ্যালুমিনিয়াম বডি, ভ্রমণ ও জরুরি ব্যবহারে উপযোগী।'],
            ["Women's Fashion", 'Strawberry Moisture Gel', 499, 'demo-categories/strawberry-gel.jpg', 'স্ট্রবেরি সুগন্ধযুক্ত ময়েশ্চারাইজিং জেল। হালকা টেক্সচার, দৈনন্দিন ব্যক্তিগত যত্নে ব্যবহারযোগ্য।'],
            ['Lifestyle', 'QxClean Stain Remover 500ml', 499, 'demo-categories/stain-remover.jpg', 'কাপড়, রান্নাঘর ও ঘরের শক্ত দাগ পরিষ্কারের জন্য ৫০০ml স্টেইন রিমুভার। কম পরিমাণেই দ্রুত ফল।'],
        ];
        $qualifiers = ['Barishal Choice', 'Bhola Select', 'Jhalokathi Home', 'Apon Hat Special', 'PFC Fresh', 'Mosaeb Smart', 'Dhaka Trendy', 'Chattogram Pick', 'Rajshahi Value', 'Sylhet Premium'];
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
                'status' => 'active',
                'cod_enabled' => true,
                'shatajpay_enabled' => true,
            ]);
            $branch->forceFill(['mobile_verified_at' => now()])->save();
            // Keep the requested demo size deterministic: five live products per shop.
            $branch->products()->update(['is_active' => false]);

            foreach ($themes as $pi => [$cat, $baseName, $price, $image, $description]) {
                $pname = $qualifiers[$bi].' '.$baseName;
                $old = null;
                $product = $branch->products()->updateOrCreate(['slug' => Str::slug($pname)], [
                    'category_id' => $cats->firstWhere('name', $cat)->id,
                    'name' => $pname,
                    'sku' => sprintf('SKU-%02d%03d', $bi + 1, $pi + 1),
                    'description' => $description."\n\n• ছবিসহ বিস্তারিত ডেমো পণ্য\n• মোবাইল-ফ্রেন্ডলি দ্রুত ব্রাউজিং\n• অনলাইন অর্ডারের জন্য প্রস্তুত",
                    'price' => $price,
                    'old_price' => $old,
                    'stock' => 20,
                    'is_featured' => $pi < 3,
                    'is_active' => true,
                    'image' => $image,
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
        $this->command?->info('Demo shops: 10 live sub-folder shops, 5 image-backed products each (50 total) — login: {slug}@example.com / password');
    }
}
