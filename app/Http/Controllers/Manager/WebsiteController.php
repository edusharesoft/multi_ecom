<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Page;
use App\Models\Post;
use App\Support\HtmlSanitizer;
use App\Support\StoreTheme;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Website builder: theme, homepage sections, menu, SEO. */
class WebsiteController extends Controller
{
    public function theme()
    {
        $b = Tenancy::branch();

        return view('manager.website.theme', ['b' => $b, 'theme' => StoreTheme::theme($b), 'menu' => StoreTheme::menu($b)]);
    }

    public function saveTheme(Request $request)
    {
        $b = Tenancy::branch();
        $data = $request->validate([
            'primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font' => ['required', Rule::in(array_keys(StoreTheme::FONTS))],
            'radius' => ['required', 'integer', 'min:0', 'max:24'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'announcement_text' => ['nullable', 'string', 'max:200'],
            'announcement_link' => ['nullable', 'url', 'max:250', 'regex:#^https?://#i'],
            'announcement_bg' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'footer_about' => ['nullable', 'string', 'max:400'],
            'footer_video' => ['nullable', 'string', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:200', 'regex:#^https?://#i'], 'youtube' => ['nullable', 'url', 'max:200', 'regex:#^https?://#i'], 'tiktok' => ['nullable', 'url', 'max:200', 'regex:#^https?://#i'],
            'whatsapp' => ['nullable', 'string', 'max:200', 'regex:#^(https?://\S+|\+?[\d\s-]{10,16})$#i'],
            'footer_links' => ['nullable', 'string', 'max:3000'],
            'footer_copyright' => ['nullable', 'string', 'max:150'],
            'footer_show_tiles' => ['nullable', 'boolean'], 'footer_show_pay' => ['nullable', 'boolean'],
            'show_newsletter' => ['nullable', 'boolean'], 'show_all_branches' => ['nullable', 'boolean'],
            'default_locale' => ['nullable', Rule::in(array_keys(\App\Http\Middleware\SetLocale::LOCALES))],
            'menu' => ['nullable', 'array', 'max:10'],
            'menu.*.label' => ['nullable', 'string', 'max:40'],
            'menu.*.url' => ['nullable', 'string', 'max:250', 'regex:#^(https?://|/)#i'],
        ], ['menu.*.url.regex' => 'মেনু লিংক http(s):// বা / দিয়ে শুরু হতে হবে।', 'whatsapp.regex' => 'WhatsApp: মোবাইল নম্বর অথবা https:// লিংক দিন।']);

        $footerLinks = \App\Support\SiteFooter::parseLinks($data['footer_links'] ?? '', $badLinks);
        if ($badLinks) {
            return back()->withInput()->withErrors(['footer_links' => 'এই লাইনগুলো ঠিক নেই (লিখুন: নাম | https://... অথবা নাম | /page): '.implode(' ; ', array_slice($badLinks, 0, 3))]);
        }

        $data['footer_video'] = trim((string) ($data['footer_video'] ?? '')) ?: null;
        if ($data['footer_video'] && ! \App\Support\SiteFooter::youtubeEmbedUrl($data['footer_video'])) {
            return back()->withInput()->withErrors(['footer_video' => 'সঠিক YouTube ভিডিও লিংক দিন।']);
        }

        $opts = $b->options ?? [];
        $oldFooterVideo = $opts['theme']['footer_video'] ?? null;
        if ($oldFooterVideo && ! preg_match('#^https?://#i', $oldFooterVideo)) {
            $b->deleteUpload($oldFooterVideo);
        }

        $opts['default_locale'] = $data['default_locale'] ?? null;
        $opts['theme'] = collect($data)->except(['menu', 'default_locale'])->map(fn ($v) => is_string($v) ? trim($v) : $v)->all()
            + ['show_newsletter' => false, 'show_all_branches' => false];
        $opts['theme']['show_newsletter'] = (bool) ($data['show_newsletter'] ?? false);
        $opts['theme']['show_all_branches'] = (bool) ($data['show_all_branches'] ?? false);
        $opts['theme']['footer_links'] = $footerLinks;
        $opts['theme']['footer_show_tiles'] = (bool) ($data['footer_show_tiles'] ?? false);
        $opts['theme']['footer_show_pay'] = (bool) ($data['footer_show_pay'] ?? false);
        $opts['menu'] = array_values(array_filter(array_map(fn ($m) => ['label' => trim((string) ($m['label'] ?? '')), 'url' => trim((string) ($m['url'] ?? ''))], $data['menu'] ?? []), fn ($m) => $m['label'] !== '' && $m['url'] !== ''));
        $b->forceFill(['options' => $opts])->save();
        ActivityLog::record('website.theme', 'Store theme updated');

        return back()->with('success', 'থিম সংরক্ষিত হয়েছে।');
    }

    public function builder()
    {
        $b = Tenancy::branch();
        $sections = StoreTheme::sections($b);
        // offer section types added in later versions (not yet in the saved list)
        foreach (StoreTheme::SECTIONS as $type => [$label, $repeat]) {
            if (! $repeat && ! collect($sections)->contains('type', $type)) {
                $sections[] = array_merge(StoreTheme::blank($type), ['enabled' => false]);
            }
        }

        return view('manager.website.builder', ['b' => $b, 'sections' => $sections, 'hasBlog' => $b->hasFeature('blog')]);
    }

    public function saveBuilder(Request $request)
    {
        $b = Tenancy::branch();
        $request->validate([
            'sections' => ['required', 'array', 'max:40'],
            'sections.*.type' => ['required', Rule::in(array_keys(StoreTheme::SECTIONS))],
            'sections.*.title' => ['nullable', 'string', 'max:100'],
            'sections.*.limit' => ['nullable', 'integer', 'min:1', 'max:24'],
            'sections.*.content' => ['nullable', 'string', 'max:20000'],
            'sections.*.link' => ['nullable', 'string', 'max:250', 'regex:#^(https?://|/)#i'],
            'sections.*.image_file' => ['nullable', 'image', 'max:12288'],
        ]);
        $old = collect(StoreTheme::sections($b))->keyBy('id');
        $out = [];
        $seen = [];
        foreach ($request->input('sections', []) as $i => $s) {
            $type = $s['type'];
            [, $repeat] = StoreTheme::SECTIONS[$type];
            if (! $repeat && isset($seen[$type])) {
                continue;
            }
            $seen[$type] = true;
            $id = preg_match('/^[a-z_]+-[a-z0-9]{6}$/', (string) ($s['id'] ?? '')) ? $s['id'] : StoreTheme::blank($type)['id'];
            $image = $old[$id]['image'] ?? '';
            if ($file = $request->file("sections.$i.image_file")) {
                if (! $b->hasStorageFor($file)) {
                    return back()->with('warning', 'স্টোরেজ লিমিট শেষ — ছবি আপলোড করা যায়নি।');
                }
                $b->deleteUpload($image ?: null);
                $image = $b->storeUpload($file, "branches/{$b->id}/website");
            }
            $out[] = [
                'id' => $id, 'type' => $type, 'enabled' => (bool) ($s['enabled'] ?? false),
                'title' => trim((string) ($s['title'] ?? '')), 'limit' => (int) ($s['limit'] ?? 8) ?: 8,
                'content' => $type === 'text' ? HtmlSanitizer::clean($s['content'] ?? '') : '',
                'image' => $type === 'image_banner' ? $image : '', 'link' => trim((string) ($s['link'] ?? '')),
            ];
        }
        // images of removed banner blocks
        foreach ($old as $id => $s) {
            if (! empty($s['image']) && ! collect($out)->contains('id', $id)) {
                $b->deleteUpload($s['image']);
            }
        }
        $opts = $b->options ?? [];
        $opts['home_sections'] = $out;
        $b->forceFill(['options' => $opts])->save();
        ActivityLog::record('website.builder', 'Homepage layout updated ('.count($out).' sections)');

        return back()->with('success', 'হোমপেজ লেআউট সংরক্ষিত হয়েছে।');
    }

    public function resetBuilder()
    {
        $b = Tenancy::branch();
        foreach (StoreTheme::sections($b) as $s) {
            if (! empty($s['image'])) {
                $b->deleteUpload($s['image']);
            }
        }
        $opts = $b->options ?? [];
        unset($opts['home_sections']);
        $b->forceFill(['options' => $opts])->save();

        return back()->with('success', 'ডিফল্ট লেআউটে ফেরত আনা হয়েছে।');
    }

    /* ---------------- SEO ---------------- */

    public function seo()
    {
        $b = Tenancy::branch();
        $products = $b->products()->get(['id', 'name', 'slug', 'image', 'meta_title', 'meta_description', 'description', 'is_active']);
        $issues = [
            'no_meta' => $products->filter(fn ($p) => ! $p->meta_description)->values(),
            'no_image' => $products->filter(fn ($p) => ! $p->image)->values(),
            'short_desc' => $products->filter(fn ($p) => mb_strlen(strip_tags((string) $p->description)) < 80)->values(),
            'long_title' => $products->filter(fn ($p) => mb_strlen($p->meta_title ?: $p->name) > 60)->values(),
        ];
        $score = $products->count() ? (int) round(100 - (($issues['no_meta']->count() + $issues['no_image']->count() * 2 + $issues['short_desc']->count()) / ($products->count() * 4)) * 100) : 100;

        return view('manager.website.seo', [
            'b' => $b, 'seo' => StoreTheme::seo($b), 'issues' => $issues, 'total' => $products->count(), 'score' => max(0, $score),
            'pagesCount' => Page::where('branch_id', $b->id)->where('is_published', true)->count(),
            'postsCount' => Post::where('branch_id', $b->id)->published()->count(),
        ]);
    }

    public function saveSeo(Request $request)
    {
        $b = Tenancy::branch();
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:70'],
            'description' => ['nullable', 'string', 'max:170'],
            'keywords' => ['nullable', 'string', 'max:250'],
            'google_verification' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'bing_verification' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'noindex' => ['nullable', 'boolean'],
        ], ['google_verification.regex' => 'শুধু content="..." এর ভিতরের কোডটি দিন।', 'bing_verification.regex' => 'শুধু content="..." এর ভিতরের কোডটি দিন।']);
        $opts = $b->options ?? [];
        $opts['seo'] = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data) + ['noindex' => false];
        $opts['seo']['noindex'] = (bool) ($data['noindex'] ?? false);
        $b->forceFill(['options' => $opts])->save();

        return back()->with('success', 'SEO সেটিংস সংরক্ষিত হয়েছে।');
    }

    /** Image upload for the rich text editor (pages, blog, text blocks). */
    public function uploadImage(Request $request)
    {
        $b = Tenancy::branch();
        abort_unless(Tenancy::can('website.edit') || Tenancy::can('blog.add') || Tenancy::can('blog.edit'), 403);
        $request->validate(['image' => ['required', 'image', 'max:12288']]);
        if (! $b->hasStorageFor($request->file('image'))) {
            return response()->json(['message' => 'Storage limit reached'], 422);
        }
        $path = $b->storeUpload($request->file('image'), "branches/{$b->id}/content");

        return response()->json(['url' => \Illuminate\Support\Facades\Storage::disk('public')->url($path)]);
    }
}
