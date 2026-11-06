<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BlogCategory;
use App\Models\Post;
use App\Support\HtmlSanitizer;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $b = Tenancy::branch();

        return view('manager.blog.index', [
            'posts' => Post::where('branch_id', $b->id)->with('category')
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
                ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'))
                ->latest()->paginate(20)->withQueryString(),
            'categories' => BlogCategory::where('branch_id', $b->id)->withCount('posts')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('manager.blog.form', ['post' => new Post(['status' => 'draft', 'author_name' => Tenancy::actorName()]), 'categories' => $this->cats()]);
    }

    public function store(Request $request)
    {
        $b = Tenancy::branch();
        $data = $this->validated($request);
        if ($f = $request->file('cover_file')) {
            if (! $b->hasStorageFor($f)) {
                return back()->withInput()->with('warning', 'স্টোরেজ লিমিট শেষ।');
            }
            $data['cover'] = $b->storeUpload($f, "branches/{$b->id}/blog");
        }
        $post = Post::create($data + ['branch_id' => $b->id]);
        ActivityLog::record('post.created', "Blog post: {$post->title}", $post);

        return redirect()->route('manager.blog.edit', $post)->with('success', 'পোস্ট সংরক্ষিত হয়েছে।');
    }

    public function edit(Post $post)
    {
        $this->own($post);

        return view('manager.blog.form', ['post' => $post, 'categories' => $this->cats()]);
    }

    public function update(Request $request, Post $post)
    {
        $this->own($post);
        $b = Tenancy::branch();
        $data = $this->validated($request, $post);
        if ($f = $request->file('cover_file')) {
            if (! $b->hasStorageFor($f)) {
                return back()->withInput()->with('warning', 'স্টোরেজ লিমিট শেষ।');
            }
            $b->deleteUpload($post->cover);
            $data['cover'] = $b->storeUpload($f, "branches/{$b->id}/blog");
        } elseif ($request->boolean('remove_cover')) {
            $b->deleteUpload($post->cover);
            $data['cover'] = null;
        }
        $post->update($data);

        return back()->with('success', 'পোস্ট আপডেট হয়েছে।');
    }

    public function destroy(Post $post)
    {
        $this->own($post);
        Tenancy::branch()->deleteUpload($post->cover);
        $post->delete();

        return redirect()->route('manager.blog.index')->with('success', 'পোস্ট মুছে ফেলা হয়েছে।');
    }

    public function storeCategory(Request $request)
    {
        $b = Tenancy::branch();
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $slug = Str::slug($data['name']) ?: 'cat-'.Str::lower(Str::random(4));
        BlogCategory::firstOrCreate(['branch_id' => $b->id, 'slug' => $slug], ['name' => $data['name']]);

        return back()->with('success', 'ক্যাটাগরি যোগ হয়েছে।');
    }

    public function destroyCategory(BlogCategory $category)
    {
        abort_unless($category->branch_id === Tenancy::branch()->id, 404);
        $category->delete();

        return back()->with('success', 'ক্যাটাগরি মুছে ফেলা হয়েছে (পোস্টগুলো থাকবে)।');
    }

    protected function validated(Request $request, ?Post $post = null): array
    {
        $b = Tenancy::branch();
        $request->merge(['slug' => Str::slug((string) ($request->slug ?: $request->title)) ?: 'post-'.Str::lower(Str::random(6))]);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:210', Rule::unique('posts')->where('branch_id', $b->id)->ignore($post?->id)],
            'blog_category_id' => ['nullable', Rule::exists('blog_categories', 'id')->where('branch_id', $b->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string', 'max:300000'],
            'tags' => ['nullable', 'string', 'max:250'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'author_name' => ['nullable', 'string', 'max:100'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'cover_file' => ['nullable', 'image', 'max:12288'],
        ]);
        unset($data['cover_file']);
        $data['content'] = HtmlSanitizer::clean($data['content'] ?? '');
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = $post?->published_at ?? now();
        }

        return $data;
    }

    protected function cats()
    {
        return BlogCategory::where('branch_id', Tenancy::branch()->id)->orderBy('name')->get();
    }

    protected function own(Post $p): void
    {
        abort_unless($p->branch_id === Tenancy::branch()->id, 404);
    }
}
