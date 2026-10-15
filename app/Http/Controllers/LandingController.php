<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Review;
use App\Models\Subscriber;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::live()
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('store_name', 'like', '%'.$request->q.'%')
                ->orWhere('address', 'like', '%'.$request->q.'%')))
            ->orderBy('store_name')
            ->get();

        $newArrivals = Product::active()
            ->whereHas('branch', fn ($q) => $q->live())
            ->with('branch')->withCount('variants')
            ->latest()->take(8)->get();

        $reviews = Review::where('is_approved', true)->where('rating', '>=', 4)
            ->whereHas('product.branch', fn ($q) => $q->live())
            ->with('product.branch')->latest()->take(6)->get();

        return view('landing', compact('branches', 'newArrivals', 'reviews'));
    }

    public function subscribe(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:150']]);
        Subscriber::firstOrCreate(['branch_id' => null, 'email' => strtolower($data['email'])]);

        return back()->with('success', 'সাবস্ক্রাইব করার জন্য ধন্যবাদ!');
    }
}
