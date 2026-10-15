<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Homepage slider & banners. */
class SliderController extends Controller
{
    public function index()
    {
        return view('manager.sliders', ['sliders' => Tenancy::branch()->sliders()->get()]);
    }

    public function store(Request $request)
    {
        $branch = Tenancy::branch();
        $data = $this->validated($request, true);
        if (! $branch->hasStorageFor($request->file('image'))) {
            return back()->with('warning', 'স্টোরেজ লিমিট শেষ।');
        }
        $data['image'] = $branch->storeUpload($request->file('image'), "sliders/{$branch->id}");
        $branch->sliders()->create($data);

        return back()->with('success', 'যোগ হয়েছে।');
    }

    public function update(Request $request, Slider $slider)
    {
        $this->own($slider);
        $branch = Tenancy::branch();
        $data = $this->validated($request, false);
        if ($request->hasFile('image')) {
            $branch->deleteUpload($slider->image);
            $data['image'] = $branch->storeUpload($request->file('image'), "sliders/{$branch->id}");
        } else {
            unset($data['image']);
        }
        $slider->update($data);

        return back()->with('success', 'আপডেট হয়েছে।');
    }

    public function destroy(Slider $slider)
    {
        $this->own($slider);
        Tenancy::branch()->deleteUpload($slider->image);
        $slider->delete();

        return back()->with('success', 'মুছে ফেলা হয়েছে।');
    }

    protected function validated(Request $request, bool $imageRequired): array
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'link' => ['nullable', 'string', 'max:500', 'regex:#^(https?://|/)#i'],
            'button_text' => ['nullable', 'string', 'max:40'],
            'position' => ['required', Rule::in(['slider', 'banner'])],
            'sort' => ['nullable', 'integer', 'min:0'],
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'max:4096'],
        ]);
        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    protected function own(Slider $slider): void
    {
        abort_unless($slider->branch_id === Tenancy::branch()->id, 404);
    }
}
