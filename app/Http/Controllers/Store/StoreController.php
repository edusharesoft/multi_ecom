<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Post;
use App\Support\StoreCache;
use App\Support\StoreTheme;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\Subscriber;
use App\Support\StoreCustomer;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function home(Branch $branch)
    {
        $sections = collect(StoreTheme::sections($branch))->where('enabled', true)->values();

        // All homepage blocks are cached per store for 5 minutes (StoreCache::TTL) and dropped at once
        // when a product / slider / brand / review / post / store setting changes.
        // The store itself is NOT cached inside (no owner data in the cache) — it is re-attached below.
        $shape = md5(json_encode($sections->map(fn ($s) => [$s['type'], (int) $s['limit']])->all()));   // not the random section ids
        $data = StoreCache::remember($branch, 'home:'.$shape, fn () => $this->homeData($branch, $sections));

        foreach (['flashSale', 'newArrivals', 'bestSelling', 'discounted', 'featured', 'posts'] as $k) {
            $data[$k]->each->setRelation('branch', $branch);
        }

        return view('store.home', $data + ['sections' => $sections->all()]);
    }

    protected function homeData(Branch $branch, \Illuminate\Support\Collection $sections): array
    {
        $base = fn () => $branch->products()->active()->withCount('variants');
        $limit = fn (string $type, int $def = 8) => max(1, min(24, (int) ($sections->firstWhere('type', $type)['limit'] ?? $def) ?: $def));
        $on = fn (string $type) => $sections->contains('type', $type);
        $none = collect();

        $data = [
            'sliders' => $on('hero') ? $branch->sliders()->where('is_active', true)->where('position', 'slider')->get() : $none,
            'banners' => $on('banners') ? $branch->sliders()->where('is_active', true)->where('position', 'banner')->take(3)->get() : $none,
            'flashSale' => $on('flash_sale') ? $base()->flashSale()->orderBy('sale_ends_at')->take($limit('flash_sale'))->get() : $none,
            'newArrivals' => $on('new_arrivals') ? $base()->latest()->take($limit('new_arrivals'))->get() : $none,
            'bestSelling' => $on('best_selling') ? $base()->where('sold_count', '>', 0)->orderByDesc('sold_count')->take($limit('best_selling'))->get() : $none,
            'discounted' => $on('discounted') ? $base()->discounted()->latest()->take($limit('discounted'))->get() : $none,
            'featured' => $on('featured') ? $base()->where('is_featured', true)->latest()->take($limit('featured', 3))->get() : $none,
            'brands' => $on('brands') ? $branch->brands()->where('is_active', true)->whereHas('products', fn ($q) => $q->where('is_active', true))->take($limit('brands', 12))->get() : $none,
            'categories' => ($on('categories') || $on('stats')) ? Category::top()->orderBy('sort')
                ->withCount(['products' => fn ($q) => $q->where('branch_id', $branch->id)->where('is_active', true)])
                ->get()->sortByDesc('products_count')->values() : $none,
            'reviews' => $on('reviews') ? Review::where('is_approved', true)->where('rating', '>=', 4)
                ->whereHas('product', fn ($q) => $q->where('branch_id', $branch->id))->latest()->take($limit('reviews', 6))->get() : $none,
            'posts' => $on('blog') && $branch->hasFeature('blog') && \App\Support\Features::ready('posts')
                ? Post::where('branch_id', $branch->id)->published()->with('category')->latest('published_at')->take($limit('blog', 3))->get() : $none,
        ];
        $data['stats'] = $on('stats') ? [
            'products' => $branch->products()->active()->count(),
            'categories' => $data['categories']->where('products_count', '>', 0)->count(),
            'orders' => $branch->orders()->whereIn('status', ['delivered', 'completed'])->count(),
            'reviews' => Review::where('is_approved', true)->whereHas('product', fn ($q) => $q->where('branch_id', $branch->id))->count(),
        ] : [];

        return $data;
    }

    public function products(Request $request, Branch $branch)
    {
        $query = $branch->products()->active()->with('branch')->withCount('variants');

        if ($request->filled('category')) {
            $cat = Category::where('slug', $request->category)->first();
            $ids = $cat ? $cat->children()->pluck('id')->push($cat->id)->all() : [0];
            $query->where(fn ($q) => $q->whereIn('category_id', $ids)->orWhereIn('subcategory_id', $ids));
        }
        if ($request->filled('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $request->brand));
        }
        if ($request->filled('q')) {
            $term = '%'.$request->q.'%';
            $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('sku', 'like', $term)->orWhere('product_code', 'like', $term)->orWhere('barcode', $request->q));
        }
        if ($request->filled('min')) {
            $query->where('price', '>=', (float) $request->min);
        }
        if ($request->filled('max')) {
            $query->where('price', '<=', (float) $request->max);
        }
        if ($request->boolean('sale')) {
            $query->discounted();
        }
        if ($request->boolean('flash')) {
            $query->flashSale();
        }
        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        }

        match ($request->get('sort')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            'best' => $query->orderByDesc('sold_count'),
            default => $query->latest(),
        };

        return view('store.products', [
            'products' => $query->paginate(16)->withQueryString(),
            'categories' => StoreCache::categories(),
            'brands' => StoreCache::remember($branch, 'brands_active', fn () => $branch->brands()->where('is_active', true)->orderBy('name')->get()),
        ]);
    }

    public function product(Branch $branch, string $slug)
    {
        $product = $branch->products()->active()
            ->with(['images', 'variants', 'reviews', 'category', 'subcategory', 'brand', 'branch'])
            ->where('slug', $slug)->firstOrFail();

        $related = $branch->products()->active()->with('branch')->withCount('variants')
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->inRandomOrder()->take(4)->get();

        $rating = [
            'avg' => round((float) $product->reviews->avg('rating'), 1),
            'count' => $product->reviews->count(),
        ];

        return view('store.product', compact('product', 'related', 'rating'));
    }

    public function review(Request $request, Branch $branch, string $slug)
    {
        $product = $branch->products()->active()->where('slug', $slug)->firstOrFail();
        $customer = StoreCustomer::current($branch);

        $data = $request->validate([
            'name' => [$customer ? 'nullable' : 'required', 'string', 'max:100'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        // Verified purchase = logged-in customer with a delivered/completed order containing this product
        $orderId = $customer ? OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q->where('customer_id', $customer->id)->whereIn('status', ['delivered', 'completed']))
            ->value('order_id') : null;

        if ($request->hasFile('image')) {
            if (! $branch->hasStorageFor($request->file('image'))) {
                unset($data['image']);
            } else {
                $data['image'] = $branch->storeUpload($request->file('image'), "reviews/{$branch->id}");
            }
        }

        $approved = (bool) $branch->option('review_auto_approve');
        $product->allReviews()->create([
            'name' => $customer?->name ?? $data['name'],
            'customer_id' => $customer?->id,
            'order_id' => $orderId,
            'is_verified' => (bool) $orderId,
            'rating' => $data['rating'],
            'comment' => $data['comment'],
            'image' => $data['image'] ?? null,
            'is_approved' => $approved,
        ]);

        return back()->with('success', $approved ? 'রিভিউ দেওয়ার জন্য ধন্যবাদ!' : 'ধন্যবাদ! রিভিউটি অনুমোদনের পর দেখা যাবে।');
    }

    public function reportReview(Branch $branch, string $slug, Review $review)
    {
        abort_unless($review->product && $review->product->branch_id === $branch->id, 404);
        $key = 'reported_reviews';
        if (! in_array($review->id, session($key, []), true)) {
            $review->increment('reports');
            session()->push($key, $review->id);
        }

        return back()->with('success', 'রিপোর্ট করার জন্য ধন্যবাদ, আমরা দেখব।');
    }

    public function subscribe(Request $request, Branch $branch)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:150']]);
        Subscriber::firstOrCreate(['branch_id' => $branch->id, 'email' => strtolower($data['email'])]);

        return back()->with('success', 'সাবস্ক্রাইব করার জন্য ধন্যবাদ!');
    }
}
