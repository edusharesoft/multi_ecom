<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Branch $branch, CartService $cart)
    {
        $items = $cart->items();

        $totals = $cart->totals($items, \App\Support\StoreCustomer::current($branch), session('customer.customer_phone'));
        if ($totals['coupon_error']) {
            $cart->setCoupon(null);
            session()->now('warning', 'কুপন সরানো হয়েছে: '.$totals['coupon_error']);
        }

        return view('store.cart', ['items' => $items, 'totals' => $totals]);
    }

    /**
     * Add a product. Supports either a single line (product_id [+ variant_id] + quantity)
     * or multiple variants at once: variants[{id}] = qty (Single Product page checkboxes).
     */
    public function add(Request $request, Branch $branch, CartService $cart)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'selected' => ['nullable', 'array'],
            'variants' => ['nullable', 'array'],
            'variants.*' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        $product = $branch->products()->active()->with('variants')->findOrFail($data['product_id']);
        $added = 0;

        if ($product->variants->isNotEmpty()) {
            $lines = [];
            if (! empty($data['variant_id'])) {
                $lines[$data['variant_id']] = $data['quantity'] ?? 1;
            }
            foreach ($data['selected'] ?? [] as $vid) {
                $lines[(int) $vid] = max(1, (int) ($data['variants'][$vid] ?? 1));
            }
            if (! $lines) {
                return back()->with('warning', 'অন্তত একটি ভ্যারিয়েন্ট (সাইজ/কালার) সিলেক্ট করুন।');
            }
            foreach ($lines as $vid => $qty) {
                $variant = $product->variants->firstWhere('id', (int) $vid);
                if (! $variant) {
                    continue;
                }
                if ($variant->stock < 1) {
                    return back()->with('warning', "{$variant->name} স্টকে নেই।");
                }
                $cart->add($product, $variant, min($qty, $variant->stock));
                $added++;
            }
        } else {
            $physical = ($product->product_type ?? 'physical') === 'physical';
            if ($physical && $product->stock < 1) {
                return back()->with('warning', 'এই পণ্যটি স্টকে নেই।');
            }
            $cart->add($product, null, $physical ? min($data['quantity'] ?? 1, $product->stock) : ($data['quantity'] ?? 1));
            $added = 1;
        }

        if ($request->input('action') === 'buy') {
            return redirect()->route('store.checkout', $branch);
        }

        return back()->with('success', $added ? "{$product->name} কার্টে যোগ হয়েছে।" : 'কিছু যোগ হয়নি।');
    }

    public function update(Request $request, Branch $branch, CartService $cart)
    {
        $data = $request->validate(['key' => ['required', 'string'], 'quantity' => ['required', 'integer', 'min:0', 'max:99']]);
        $cart->update($data['key'], $data['quantity']);

        return back();
    }

    public function remove(Request $request, Branch $branch, CartService $cart)
    {
        $cart->remove((string) $request->input('key'));

        return back()->with('success', 'পণ্যটি কার্ট থেকে সরানো হয়েছে।');
    }

    public function applyCoupon(Request $request, Branch $branch, CartService $cart)
    {
        $code = $request->validate(['code' => ['required', 'string', 'max:40']])['code'];
        $r = app(\App\Services\CouponService::class)->evaluate($branch, $code, $cart->items(), \App\Support\StoreCustomer::current($branch), session('customer.customer_phone'));
        if ($r['error']) {
            return back()->with('warning', $r['error']);
        }
        $cart->setCoupon($code);

        return back()->with('success', "কুপন {$r['coupon']->code} যুক্ত হয়েছে — ৳".number_format($r['discount'], 2).' ছাড়।');
    }

    public function removeCoupon(Branch $branch, CartService $cart)
    {
        $cart->setCoupon(null);

        return back()->with('success', 'কুপন সরানো হয়েছে।');
    }

    public function clear(Branch $branch, CartService $cart)
    {
        $cart->clear();

        return back()->with('success', 'কার্ট খালি করা হয়েছে।');
    }
}
