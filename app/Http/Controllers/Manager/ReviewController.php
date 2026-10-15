<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Support\Tenancy;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $branchId = Tenancy::branch()->id;
        $reviews = Review::with('product.branch')
            ->whereHas('product', fn ($q) => $q->where('branch_id', $branchId))
            ->when($request->input('filter') === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($request->input('filter') === 'reported', fn ($q) => $q->where('reports', '>', 0))
            ->when($request->input('filter') === 'unreplied', fn ($q) => $q->whereNull('reply'))
            ->latest()->paginate(25)->withQueryString();

        return view('manager.reviews', compact('reviews'));
    }

    public function update(Request $request, Review $review)
    {
        $this->own($review);
        $data = $request->validate([
            'is_approved' => ['nullable', 'boolean'],
            'reply' => ['nullable', 'string', 'max:1000'],
            'clear_reports' => ['nullable', 'boolean'],
        ]);
        if ($request->has('is_approved')) {
            $review->is_approved = $request->boolean('is_approved');
        }
        if ($request->has('reply')) {
            $review->reply = $data['reply'] ?: null;
            $review->replied_at = $data['reply'] ? now() : null;
        }
        if ($request->boolean('clear_reports')) {
            $review->reports = 0;
        }
        $review->save();

        return back()->with('success', 'রিভিউ আপডেট হয়েছে।');
    }

    public function destroy(Review $review)
    {
        $this->own($review);
        Tenancy::branch()->deleteUpload($review->image);
        $review->delete();

        return back()->with('success', 'রিভিউ মুছে ফেলা হয়েছে।');
    }

    protected function own(Review $review): void
    {
        abort_unless($review->product && $review->product->branch_id === Tenancy::branch()->id, 404);
    }
}
