<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Categories are global — shared by every branch. */
class CategoryController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'categories' => Category::top()->withCount('products')->with(['children' => fn ($q) => $q->withCount('products')])->orderBy('sort')->get(),
            'parents' => Category::top()->orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->slug($data['name']);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }
        Category::create($data);

        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category);
        if ($data['name'] !== $category->name) {
            $data['slug'] = $this->slug($data['name'], $category->id);
        }
        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }
        $category->update($data);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }
        $category->delete();   // products keep existing with category = null

        return back()->with('success', 'Category deleted.');
    }

    protected function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('categories', 'name')->ignore($category?->id)],
            'tagline' => ['nullable', 'string', 'max:120'],
            'parent_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('parent_id'), Rule::notIn(array_filter([$category?->id]))],
            'sort' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);
        $data['sort'] = (int) ($data['sort'] ?? 0);
        unset($data['image']);

        return $data;
    }

    protected function slug(string $name, ?int $ignore = null): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)->when($ignore, fn ($q) => $q->where('id', '!=', $ignore))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
