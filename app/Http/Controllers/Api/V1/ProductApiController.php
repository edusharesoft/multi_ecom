<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductApiController extends ApiController
{
    public function index(Request $request)
    {
        $request->validate(['updated_since' => ['nullable', 'date']]);
        $q =$this->branch()->products()->with(['branch', 'variants'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->q.'%')->orWhere('sku', $request->q)->orWhere('barcode', $request->q)))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('active'), fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->when($request->filled('updated_since'), fn ($q) => $q->where('updated_at', '>=', $request->date('updated_since')))
            ->orderByDesc('id');

        return $this->paginated($q->paginate($this->perPage()), fn ($p) => self::product($p));
    }

    public function show(int $id)
    {
        return ['data' => self::product($this->find($id)->load(['branch', 'variants']))];
    }

    public function store(Request $request, InventoryService $inventory)
    {
        $b = $this->branch();
        if (! $b->withinLimit('max_products')) {
            return response()->json(['message' => 'Product limit of the plan reached.'], 422);
        }
        $data = $this->validated($request);
        $p = DB::transaction(function () use ($b, $data, $inventory) {
            $stock = (int) ($data['stock'] ?? 0);
            unset($data['stock']);
            $p = $b->products()->create($data + ['slug' => $this->slug($data['name']), 'stock' => 0, 'product_type' => $data['product_type'] ?? 'physical']);
            if ($stock !== 0) {
                $inventory->move($b, $b->defaultWarehouse(), $p->id, 0, $stock, 'opening', $p, (float) ($p->purchase_price ?? 0), 'API');
            }

            return $p->fresh(['branch', 'variants']);
        });

        return response()->json(['data' => self::product($p)], 201);
    }

    public function update(Request $request, int $id)
    {
        $p = $this->find($id);
        $data = $this->validated($request, true);
        unset($data['stock']);   // stock changes go through /stock (ledger)
        if (isset($data['name']) && $data['name'] !== $p->name) {
            $data['slug'] = $this->slug($data['name'], $p->id);
        }
        $p->update($data);

        return ['data' => self::product($p->fresh(['branch', 'variants']))];
    }

    /** POST /products/{id}/stock  {quantity: +5 | -2, variant_id?, note?}  or  {set: 20} */
    public function stock(Request $request, int $id, InventoryService $inventory)
    {
        $p = $this->find($id);
        $data = $request->validate([
            'quantity' => ['required_without:set', 'integer', 'not_in:0', 'between:-100000,100000'],
            'set' => ['required_without:quantity', 'integer', 'min:0', 'max:1000000'],
            'variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $p->id)],
            'note' => ['nullable', 'string', 'max:200'],
        ]);
        $b = $this->branch();
        $vid = (int) ($data['variant_id'] ?? 0);
        $wh = $b->defaultWarehouse();
        $qty = isset($data['set']) ? (int) $data['set'] - $inventory->available($wh, $p->id, $vid) : (int) $data['quantity'];
        if ($qty !== 0) {
            $inventory->move($b, $wh, $p->id, $vid, $qty, $qty > 0 ? 'adjustment_in' : 'adjustment_out', $p, null, 'API: '.($data['note'] ?? 'stock update'));
        }

        return ['data' => self::product($p->fresh(['branch', 'variants']))];
    }

    protected function find(int $id): Product
    {
        return $this->branch()->products()->findOrFail($id);
    }

    protected function validated(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        $data = $request->validate([
            'name' => [$req, 'string', 'max:200'],
            'price' => [$req, 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'sku' => ['nullable', 'string', 'max:60'],
            'barcode' => ['nullable', 'string', 'max:60'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', Rule::exists('brands', 'id')->where('branch_id', $this->branch()->id)],
            'description' => ['nullable', 'string', 'max:20000'],
            'unit' => ['nullable', 'string', 'max:20'],
            'product_type' => ['nullable', Rule::in(array_keys(Product::TYPES))],
            'stock' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        // explicit nulls must not overwrite NOT NULL columns
        return array_filter($data, fn ($v, $key) => $v !== null || ! in_array($key, ['is_active', 'is_featured', 'price', 'name', 'min_stock'], true), ARRAY_FILTER_USE_BOTH);
    }

    protected function slug(string $name, ?int $ignore = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 2;
        while ($this->branch()->products()->where('slug', $slug)->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
