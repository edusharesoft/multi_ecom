<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use App\Support\StoreCustomer;

/** Wishlist: saved to the account when logged in, otherwise kept in the session. */
class WishlistController extends Controller
{
    public static function ids(Branch $branch): array
    {
        $customer = StoreCustomer::current($branch);

        return $customer ? $customer->wishlist()->pluck('product_id')->all() : session('wishlist.'.$branch->id, []);
    }

    public function index(Branch $branch)
    {
        $products = $branch->products()->active()->with('branch')->withCount('variants')->whereIn('id', self::ids($branch))->get();

        return view('store.wishlist', compact('products'));
    }

    public function toggle(Branch $branch, Product $product)
    {
        abort_unless($product->branch_id === $branch->id, 404);

        if ($customer = StoreCustomer::current($branch)) {
            $row = $customer->wishlist()->where('product_id', $product->id)->first();
            $row ? $row->delete() : $customer->wishlist()->create(['product_id' => $product->id]);
            $added = ! $row;
        } else {
            $key = 'wishlist.'.$branch->id;
            $ids = session($key, []);
            $added = ! in_array($product->id, $ids, true);
            $ids = $added ? [...$ids, $product->id] : array_values(array_diff($ids, [$product->id]));
            session([$key => $ids]);
        }

        return back()->with('success', $added ? 'Wishlist এ যোগ হয়েছে।' : 'Wishlist থেকে সরানো হয়েছে।');
    }
}
