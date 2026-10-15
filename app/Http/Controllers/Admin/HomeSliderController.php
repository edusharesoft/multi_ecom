<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\PlatformSlides;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Main website (landing page) banner slideshow — images only. */
class HomeSliderController extends Controller
{
    public function index()
    {
        return view('admin.sliders', ['slides' => PlatformSlides::all(), 'max' => PlatformSlides::MAX]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $rows = PlatformSlides::all();
        if (count($rows) >= PlatformSlides::MAX) {
            return back()->with('warning', 'সর্বোচ্চ '.PlatformSlides::MAX.'টি স্লাইড রাখা যায়। আগে একটি মুছুন।');
        }
        $rows[] = [
            'id' => (string) Str::uuid(),
            'image' => $request->file('image')->store('site/slides', 'public'),
            'link' => $data['link'] ?? null,
            'sort' => $data['sort'],
            'active' => true,
        ];
        PlatformSlides::save($rows);
        ActivityLog::record('home_slider.added', 'Home slider image added');

        return back()->with('success', 'স্লাইড যোগ হয়েছে।');
    }

    public function update(Request $request, string $slide)
    {
        $data = $this->validated($request, false);
        $rows = PlatformSlides::all();
        $i = $this->find($rows, $slide);
        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($rows[$i]['image']);
            $rows[$i]['image'] = $request->file('image')->store('site/slides', 'public');
        }
        $rows[$i]['link'] = $data['link'] ?? null;
        $rows[$i]['sort'] = $data['sort'];
        $rows[$i]['active'] = $request->boolean('active');
        PlatformSlides::save($rows);

        return back()->with('success', 'স্লাইড আপডেট হয়েছে।');
    }

    public function destroy(string $slide)
    {
        $rows = PlatformSlides::all();
        $i = $this->find($rows, $slide);
        Storage::disk('public')->delete($rows[$i]['image']);
        array_splice($rows, $i, 1);
        PlatformSlides::save($rows);
        ActivityLog::record('home_slider.deleted', 'Home slider image deleted');

        return back()->with('success', 'স্লাইড মুছে ফেলা হয়েছে।');
    }

    protected function validated(Request $request, bool $imageRequired): array
    {
        $data = $request->validate([
            'image' => [$imageRequired ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'link' => ['nullable', 'string', 'max:500', 'regex:#^(https?://|/)#i'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], ['link.regex' => 'লিংক http://, https:// অথবা / দিয়ে শুরু হতে হবে।']);
        $data['sort'] = (int) ($data['sort'] ?? 0);

        return $data;
    }

    protected function find(array $rows, string $id): int
    {
        foreach ($rows as $i => $r) {
            if (hash_equals((string) $r['id'], $id)) {
                return $i;
            }
        }
        abort(404);
    }
}
