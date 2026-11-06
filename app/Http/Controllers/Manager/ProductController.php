<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Support\Tenancy;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Tenancy::branch()->products()->with(['category', 'variants'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->q.'%')->orWhere('sku', 'like', '%'.$request->q.'%')))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->category))
            ->when($request->filled('brand'), fn ($q) => $q->where('brand_id', $request->brand))
            ->when($request->input('stock') === 'low', fn ($q) => $q->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0))
            ->when($request->input('stock') === 'out', fn ($q) => $q->where('stock', '<=', 0))
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'hidden', fn ($q) => $q->where('is_active', false))
            ->latest()->paginate(15)->withQueryString();

        return view('manager.products.index', ['products' => $products, 'categories' => Category::orderBy('sort')->get(), 'brands' => Tenancy::branch()->brands()->orderBy('name')->get()]);
    }

    public function create()
    {
        if ($redirect = $this->limitReached()) {
            return $redirect;
        }

        return view('manager.products.form', ['product' => new Product(['is_active' => true, 'product_type' => 'physical', 'unit' => 'pcs', 'min_stock' => 5])] + $this->formData());
    }

    public function store(Request $request)
    {
        $branch = Tenancy::branch();
        if ($redirect = $this->limitReached()) {
            return $redirect;
        }
        $data = $this->validated($request);
        if ($storageError = $this->storageError($request)) {
            return $storageError;
        }

        $product = DB::transaction(function () use ($request, $branch, $data) {
            $product = $branch->products()->create($data + ['slug' => $this->uniqueSlug($branch, $data['name'])]);
            $this->saveMedia($request, $product);
            $this->syncVariants($request, $product);
            app(\App\Services\InventoryService::class)->reconcile($product->fresh(), 'Opening stock (new product)');

            return $product;
        });
        \App\Models\ActivityLog::record('product.created', "Product added: {$product->name}", $product);

        return redirect()->route('manager.products.index')->with('success', 'পণ্য যোগ করা হয়েছে।');
    }

    public function edit(Request $request, Product $product)
    {
        $this->own($request, $product);

        return view('manager.products.form', [
            'product' => $product->load(['images', 'variants']),
        ] + $this->formData() + [
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->own($request, $product);
        $data = $this->validated($request);
        if ($storageError = $this->storageError($request)) {
            return $storageError;
        }

        DB::transaction(function () use ($request, $product, $data) {
            if ($product->name !== $data['name']) {
                $data['slug'] = $this->uniqueSlug($product->branch, $data['name'], $product->id);
            }
            $product->update($data);
            $this->saveMedia($request, $product);
            $this->syncVariants($request, $product);
            app(\App\Services\InventoryService::class)->reconcile($product->fresh());
        });

        \App\Models\ActivityLog::record('product.updated', "Product updated: {$product->name}", $product);

        return redirect()->route('manager.products.index')->with('success', 'পণ্য আপডেট হয়েছে।');
    }

    public function destroy(Request $request, Product $product)
    {
        $this->own($request, $product);
        if (\App\Models\PurchaseItem::where('product_id', $product->id)->exists()) {   // FK restrict
            return back()->with('warning', 'এই পণ্যের ক্রয়ের রেকর্ড আছে — মুছা যাবে না, Hidden করুন।');
        }

        $branch = Tenancy::branch();
        foreach ($product->images->pluck('path')->push($product->image)->filter() as $path) {
            $branch->deleteUpload($path);
        }
        $product->delete();
        \App\Models\ActivityLog::record('product.deleted', "Product deleted: {$product->name}", null, ['id' => $product->id]);

        return back()->with('success', 'পণ্য মুছে ফেলা হয়েছে।');
    }

    public function destroyImage(Request $request, ProductImage $image)
    {
        $this->own($request, $image->product);
        Tenancy::branch()->deleteUpload($image->path);
        $image->delete();

        return back()->with('success', 'ছবি মুছে ফেলা হয়েছে।');
    }

    protected function own(Request $request, Product $product): void
    {
        abort_unless($product->branch_id === Tenancy::branch()->id, 403);
    }

    protected function validated(Request $request): array
    {
        $branchId = Tenancy::branch()->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'product_type' => ['required', \Illuminate\Validation\Rule::in(array_keys(Product::TYPES))],
            'category_id' => ['nullable', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', \Illuminate\Validation\Rule::exists('brands', 'id')->where('branch_id', $branchId)],
            'sku' => ['nullable', 'string', 'max:60'],
            'product_code' => ['nullable', 'string', 'max:60'],
            'barcode' => ['nullable', 'string', 'max:80', \Illuminate\Validation\Rule::unique('products', 'barcode')->where('branch_id', $branchId)->ignore($request->route('product')?->id)],
            'unit' => ['required', \Illuminate\Validation\Rule::in(array_keys(Product::UNITS))],
            'description' => ['nullable', 'string', 'max:20000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'old_price' => ['nullable', 'numeric', 'gt:price', 'max:9999999'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_ends_at' => ['nullable', 'date', 'after:sale_starts_at'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'specs' => ['nullable', 'array'],
            'specs.*.key' => ['nullable', 'string', 'max:80'],
            'specs.*.value' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:12288'],
            'gallery' => ['nullable', 'array', 'max:8'],
            'gallery.*' => ['image', 'max:12288'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['nullable', 'string', 'max:120'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
        ], [
            'old_price.gt' => 'পুরনো দাম বর্তমান দামের চেয়ে বেশি হতে হবে।',
            'sale_price.lt' => 'ডিসকাউন্ট/ফ্ল্যাশ সেল দাম মূল দামের চেয়ে কম হতে হবে।',
            'barcode.unique' => 'এই বারকোড অন্য পণ্যে ব্যবহৃত।',
        ]);

        $data['stock'] = (int) ($data['stock'] ?? 0);
        $data['min_stock'] = (int) ($data['min_stock'] ?? 5);
        $data['tax_percent'] = (float) ($data['tax_percent'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['specifications'] = collect($data['specs'] ?? [])->filter(fn ($r) => filled($r['key'] ?? null) && filled($r['value'] ?? null))->values()->all() ?: null;
        if (empty($data['sale_price'])) {
            $data['sale_price'] = $data['sale_starts_at'] = $data['sale_ends_at'] = null;
        }

        return collect($data)->except(['image', 'gallery', 'variants', 'specs'])->all();
    }

    protected function saveMedia(Request $request, Product $product): void
    {
        $branch = Tenancy::branch();
        $dir = "products/{$product->branch_id}";
        if ($request->hasFile('image')) {
            $branch->deleteUpload($product->image);
            $product->update(['image' => $branch->storeUpload($request->file('image'), $dir)]);
        }
        $sort = (int) $product->images()->max('sort');
        foreach ($request->file('gallery', []) as $file) {
            $product->images()->create(['path' => $branch->storeUpload($file, $dir), 'sort' => ++$sort]);
        }
    }

    protected function formData(): array
    {
        return [
            'categories' => Category::top()->with('children')->orderBy('sort')->get(),
            'brands' => Tenancy::branch()->brands()->orderBy('name')->get(),
        ];
    }

    /** Barcode / QR label sheet for selected products. */
    public function labels(Request $request)
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids'))));
        $products = Tenancy::branch()->products()->whereIn('id', $ids)->get()->each->setRelation('branch', Tenancy::branch());
        $copies = max(1, min(100, (int) $request->query('copies', 1)));

        return view('manager.products.labels', compact('products', 'copies'));
    }

    /** Plan limit: maximum products. */
    protected function limitReached()
    {
        $branch = Tenancy::branch();
        if (! $branch->withinLimit('max_products')) {
            return redirect()->route('manager.products.index')->with('warning',
                "আপনার প্ল্যানে সর্বোচ্চ {$branch->planLimit('max_products')}টি পণ্য রাখা যায়। আরও পণ্য যোগ করতে প্ল্যান আপগ্রেড করুন।");
        }

        return null;
    }

    /** Plan limit: storage. */
    protected function storageError(Request $request)
    {
        $files = array_filter(array_merge([$request->file('image')], $request->file('gallery', [])));
        if ($files && ! Tenancy::branch()->hasStorageFor(...$files)) {
            return back()->withInput()->with('warning', 'আপনার প্ল্যানের স্টোরেজ লিমিট শেষ। পুরনো ছবি মুছুন অথবা আপগ্রেড করুন।');
        }

        return null;
    }

    protected function syncVariants(Request $request, Product $product): void
    {
        $keep = [];
        foreach ($request->input('variants', []) as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $attrs = [
                'name' => $name,
                'price' => ($row['price'] ?? '') === '' ? null : $row['price'],
                'stock' => (int) ($row['stock'] ?? 0),
            ];
            $variant = ! empty($row['id']) ? $product->variants()->find($row['id']) : null;
            if ($variant) {
                $variant->update($attrs);
            } else {
                $variant = $product->variants()->create($attrs);
            }
            $keep[] = $variant->id;
        }
        $product->variants()->whereNotIn('id', $keep)->delete();

        if ($keep) {
            $product->update(['stock' => (int) $product->variants()->sum('stock')]);
        }
    }

    protected function uniqueSlug($branch, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 2;
        while ($branch->products()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
