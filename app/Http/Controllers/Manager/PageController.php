<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Page;
use App\Support\HtmlSanitizer;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index()
    {
        $b = Tenancy::branch();

        return view('manager.website.pages', ['pages' => Page::where('branch_id', $b->id)->orderBy('sort')->orderBy('id')->get(), 'b' => $b]);
    }

    public function create(Request $request)
    {
        $tpl = Page::TEMPLATES[(string) $request->query('template')] ?? null;

        return view('manager.website.page-form', ['page' => new Page(['title' => $tpl[0] ?? '', 'slug' => $tpl ? $request->query('template') : '', 'content' => $tpl[1] ?? '', 'is_published' => true, 'show_in_footer' => true])]);
    }

    public function store(Request $request)
    {
        $b = Tenancy::branch();
        $page = Page::create($this->validated($request) + ['branch_id' => $b->id]);
        ActivityLog::record('page.created', "Page: {$page->title}", $page);

        return redirect()->route('manager.pages.edit', $page)->with('success', 'পেজ তৈরি হয়েছে।');
    }

    public function edit(Page $page)
    {
        $this->own($page);

        return view('manager.website.page-form', ['page' => $page]);
    }

    public function update(Request $request, Page $page)
    {
        $this->own($page);
        $page->update($this->validated($request, $page));

        return back()->with('success', 'পেজ আপডেট হয়েছে।');
    }

    public function destroy(Page $page)
    {
        $this->own($page);
        $page->delete();
        ActivityLog::record('page.deleted', "Page deleted: {$page->title}");

        return redirect()->route('manager.pages.index')->with('success', 'পেজ মুছে ফেলা হয়েছে।');
    }

    protected function validated(Request $request, ?Page $page = null): array
    {
        $b = Tenancy::branch();
        $request->merge(['slug' => Str::slug((string) ($request->slug ?: $request->title)) ?: 'page-'.Str::lower(Str::random(5))]);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:160', Rule::unique('pages')->where('branch_id', $b->id)->ignore($page?->id)],
            'content' => ['nullable', 'string', 'max:200000'],
            'is_published' => ['nullable', 'boolean'], 'show_in_header' => ['nullable', 'boolean'], 'show_in_footer' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'meta_title' => ['nullable', 'string', 'max:70'], 'meta_description' => ['nullable', 'string', 'max:170'],
        ]);
        $data['content'] = HtmlSanitizer::clean($data['content'] ?? '');
        foreach (['is_published', 'show_in_header', 'show_in_footer'] as $k) {
            $data[$k] = (bool) ($data[$k] ?? false);
        }
        $data['sort'] = (int) ($data['sort'] ?? 0);

        return $data;
    }

    protected function own(Page $p): void
    {
        abort_unless($p->branch_id === Tenancy::branch()->id, 404);
    }
}
