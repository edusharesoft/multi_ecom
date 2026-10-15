<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Review;
use App\Services\Ai\AiAssistant;
use App\Services\Ai\AiService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/** JSON helpers behind the ✨ AI buttons + the AI insights page. */
class AiController extends Controller
{
    public function __construct(protected AiAssistant $assistant, protected AiService $ai) {}

    protected function fail(RuntimeException $e)
    {
        return response()->json(['message' => $e->getMessage()], 422);
    }

    public function product(Request $request)
    {
        abort_unless(Tenancy::can('products.add') || Tenancy::can('products.edit'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'], 'category_id' => ['nullable', 'integer'], 'brand_id' => ['nullable', 'integer'],
            'price' => ['nullable', 'numeric'], 'features' => ['nullable', 'string', 'max:1500'],
            'lang' => ['required', Rule::in(['bn', 'en'])], 'tone' => ['nullable', Rule::in(['friendly', 'premium', 'simple', 'energetic'])],
        ]);
        $data['category'] = $data['category_id'] ?? null ? Category::find($data['category_id'])?->name : null;
        $data['brand'] = $data['brand_id'] ?? null ? Brand::where('branch_id', Tenancy::branch()->id)->find($data['brand_id'])?->name : null;
        try {
            return response()->json($this->assistant->productCopy(Tenancy::branch(), $data) + $this->quota());
        } catch (RuntimeException $e) {
            return $this->fail($e);
        }
    }

    public function blog(Request $request)
    {
        abort_unless(Tenancy::can('blog.add') || Tenancy::can('blog.edit'), 403);
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:300'], 'keywords' => ['nullable', 'string', 'max:300'],
            'lang' => ['required', Rule::in(['bn', 'en'])], 'length' => ['nullable', Rule::in(['short', 'medium', 'long'])],
        ]);
        try {
            return response()->json($this->assistant->blogPost(Tenancy::branch(), $data) + $this->quota());
        } catch (RuntimeException $e) {
            return $this->fail($e);
        }
    }

    public function campaign(Request $request)
    {
        abort_unless(Tenancy::can('marketing.add'), 403);
        $data = $request->validate(['goal' => ['required', 'string', 'max:500'], 'channel' => ['required', Rule::in(['sms', 'email'])], 'lang' => ['required', Rule::in(['bn', 'en'])]]);
        try {
            return response()->json($this->assistant->campaignCopy(Tenancy::branch(), $data) + $this->quota());
        } catch (RuntimeException $e) {
            return $this->fail($e);
        }
    }

    public function reviewReply(Review $review)
    {
        abort_unless(Tenancy::can('reviews.approve'), 403);
        $review->load('product');
        abort_unless($review->product && $review->product->branch_id === Tenancy::branch()->id, 404);
        try {
            return response()->json(['reply' => $this->assistant->reviewReply(Tenancy::branch(), $review)] + $this->quota());
        } catch (RuntimeException $e) {
            return $this->fail($e);
        }
    }

    public function insights()
    {
        $b = Tenancy::branch();

        return view('manager.ai.insights', [
            'm' => $this->assistant->metrics($b),
            'available' => $this->ai->availableFor($b),
            'configured' => $this->ai->configured(),
            'left' => $b->hasFeature('ai') ? $this->ai->remaining($b) : 0,
        ]);
    }

    public function generateInsights(Request $request)
    {
        $b = Tenancy::branch();
        $lang = $request->input('lang') === 'en' ? 'en' : 'bn';
        try {
            return response()->json(['text' => $this->assistant->insights($b, $this->assistant->metrics($b), $lang)] + $this->quota());
        } catch (RuntimeException $e) {
            return $this->fail($e);
        }
    }

    protected function quota(): array
    {
        $left = $this->ai->remaining(Tenancy::branch());

        return ['remaining' => $left];
    }
}
