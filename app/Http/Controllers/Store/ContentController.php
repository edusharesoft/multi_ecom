<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\MessageLog;

/** Storefront pages, blog and per-store sitemap. */
class ContentController extends Controller
{
    public function contact(Branch $branch)
    {
        return view('store.contact', ['branch' => $branch]);
    }

    public function submitContact(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:10000'],
        ]);
        $to = $branch->email;
        $subject = '['.$branch->display_name.'] '.$data['subject'];
        $status = 'sent';
        $error = null;
        try {
            Mail::raw("নাম: {$data['name']}\nইমেইল: {$data['email']}\n\n{$data['message']}", function ($mail) use ($to, $subject, $data) {
                $mail->to($to)->replyTo($data['email'], $data['name'])->subject($subject);
            });
        } catch (\Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
            Log::warning('Store contact message email failed', ['branch_id' => $branch->id, 'error' => $error]);
        }
        MessageLog::create([
            'branch_id' => $branch->id, 'channel' => 'email', 'to' => $to,
            'subject' => $subject, 'body' => $data['message'], 'status' => $status,
            'context' => 'store.contact', 'error' => $error,
        ]);

        return redirect()->route('store.contact', $branch)->with($status === 'sent' ? 'success' : 'warning', $status === 'sent' ? 'আপনার বার্তা পাঠানো হয়েছে।' : 'বার্তা সংরক্ষণ হয়েছে, তবে ইমেইল পাঠানো যায়নি।');
    }

    public function page(Branch $branch, string $slug)
    {
        $page = Page::where('branch_id', $branch->id)->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('store.page', ['page' => $page]);
    }

    public function blog(Request $request, Branch $branch)
    {
        abort_unless($branch->hasFeature('blog'), 404);
        $category = $request->filled('category') ? BlogCategory::where('branch_id', $branch->id)->where('slug', $request->category)->first() : null;
        $posts = Post::where('branch_id', $branch->id)->published()->with(['category', 'branch'])
            ->when($category, fn ($q) => $q->where('blog_category_id', $category->id))
            ->when($request->filled('tag'), fn ($q) => $q->where('tags', 'like', '%'.$request->tag.'%'))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', '%'.$request->q.'%')->orWhere('content', 'like', '%'.$request->q.'%')))
            ->latest('published_at')->paginate(9)->withQueryString();

        return view('store.blog.index', [
            'posts' => $posts, 'category' => $category,
            'categories' => BlogCategory::where('branch_id', $branch->id)->withCount(['posts' => fn ($q) => $q->published()])->orderBy('name')->get(),
        ]);
    }

    public function post(Branch $branch, string $slug)
    {
        abort_unless($branch->hasFeature('blog'), 404);
        $post = Post::where('branch_id', $branch->id)->where('slug', $slug)->with(['category', 'branch'])->firstOrFail();
        // drafts are visible only to the store's own logged-in manager/staff (preview)
        abort_unless($post->isLive() || auth('branch')->id() === $branch->id || auth('staff')->user()?->branch_id === $branch->id, 404);
        if ($post->isLive()) {
            Post::whereKey($post->id)->increment('views');
        }

        return view('store.blog.show', [
            'post' => $post,
            'related' => Post::where('branch_id', $branch->id)->published()->whereKeyNot($post->id)
                ->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))
                ->with('branch')->latest('published_at')->take(3)->get(),
        ]);
    }

    public function sitemap(Branch $branch)
    {
        abort_unless($branch->isOpen(), 404);
        // cached per store and host (links differ on a custom domain) — rebuilt when the catalogue changes.
        // Only a verified host is used as key: a forged Host header is rendered live, never cached.
        $host = strtolower(request()->getHost());
        $trusted = app()->bound('custom_domain_branch') || $host === strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $build = function () use ($branch) {
            $urls = [[route('store.home', $branch), now(), 'daily', '1.0'], [route('store.products', $branch), now(), 'daily', '0.8']];
            foreach (Category::orderBy('sort')->get(['slug', 'updated_at']) as $c) {
                $urls[] = [route('store.products', [$branch, 'category' => $c->slug]), $c->updated_at, 'weekly', '0.6'];
            }
            foreach ($branch->products()->active()->latest('updated_at')->limit(5000)->get(['id', 'slug', 'branch_id', 'updated_at']) as $p) {
                $urls[] = [route('store.product', [$branch, $p->slug]), $p->updated_at, 'weekly', '0.7'];
            }
            foreach (Page::where('branch_id', $branch->id)->where('is_published', true)->get(['slug', 'updated_at']) as $pg) {
                $urls[] = [route('store.page', [$branch, $pg->slug]), $pg->updated_at, 'monthly', '0.4'];
            }
            if ($branch->hasFeature('blog')) {
                $urls[] = [route('store.blog', $branch), now(), 'weekly', '0.6'];
                foreach (Post::where('branch_id', $branch->id)->published()->get(['slug', 'updated_at']) as $po) {
                    $urls[] = [route('store.blog.show', [$branch, $po->slug]), $po->updated_at, 'monthly', '0.5'];
                }
            }

            return view('seo.sitemap', ['urls' => $urls])->render();
        };
        $xml = $trusted ? \App\Support\StoreCache::remember($branch, 'sitemap:'.md5(request()->getSchemeAndHttpHost()), $build, 3600) : $build();

        return response($xml)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
