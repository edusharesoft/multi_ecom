<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index()
    {
        return view('manager.brands', ['brands' => Tenancy::branch()->brands()->withCount('products')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $branch = Tenancy::branch();
        $data = $this->validated($request);
        $data['slug'] = $this->slug($data['name']);
        if ($request->hasFile('logo')) {
            $data['logo'] = $branch->storeUpload($request->file('logo'), "brands/{$branch->id}");
        }
        $branch->brands()->create($data);

        return back()->with('success', 'ব্র্যান্ড যোগ হয়েছে।');
    }

    public function update(Request $request, Brand $brand)
    {
        $this->own($brand);
        $branch = Tenancy::branch();
        $data = $this->validated($request, $brand);
        if ($data['name'] !== $brand->name) {
            $data['slug'] = $this->slug($data['name'], $brand->id);
        }
        if ($request->hasFile('logo')) {
            $branch->deleteUpload($brand->logo);
            $data['logo'] = $branch->storeUpload($request->file('logo'), "brands/{$branch->id}");
        }
        $brand->update($data);

        return back()->with('success', 'ব্র্যান্ড আপডেট হয়েছে।');
    }

    public function destroy(Brand $brand)
    {
        $this->own($brand);
        Tenancy::branch()->deleteUpload($brand->logo);
        $brand->delete();

        return back()->with('success', 'ব্র্যান্ড মুছে ফেলা হয়েছে।');
    }

    protected function validated(Request $request, ?Brand $brand = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('brands')->where('branch_id', Tenancy::branch()->id)->ignore($brand?->id)],
            'logo' => ['nullable', 'image', 'max:12288'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['logo']);

        return $data;
    }

    protected function slug(string $name, ?int $ignore = null): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $i = 2;
        while (Tenancy::branch()->brands()->where('slug', $slug)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    protected function own(Brand $brand): void
    {
        abort_unless($brand->branch_id === Tenancy::branch()->id, 404);
    }
}
