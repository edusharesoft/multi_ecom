<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Coupon;
use App\Support\CouponForm;
use Illuminate\Http\Request;

/** Global coupons / promotions — valid in every branch store. */
class CouponController extends Controller
{
    public function index()
    {
        return view('admin.coupons', [
            'coupons' => Coupon::whereNull('branch_id')->withCount('usages')->withSum('usages', 'discount')->latest()->get(),
            'categories' => Category::orderBy('sort')->get(['id', 'name', 'parent_id']),
        ]);
    }

    public function store(Request $request)
    {
        $data = CouponForm::validate($request, null);
        $data['customer_id'] = null;
        $data['product_ids'] = null;          // products are branch-specific
        if ($data['scope'] === 'products') {
            $data['scope'] = 'all';
        }
        $coupon = Coupon::create($data + ['branch_id' => null]);
        ActivityLog::record('coupon.global_created', "Global coupon {$coupon->code}", $coupon);

        return back()->with('success', "Global coupon {$coupon->code} created.");
    }

    public function update(Request $request, Coupon $coupon)
    {
        abort_unless($coupon->isGlobal(), 404);
        $data = CouponForm::validate($request, null, $coupon->id);
        $data['customer_id'] = null;
        $data['product_ids'] = null;
        if ($data['scope'] === 'products') {
            $data['scope'] = 'all';
        }
        $coupon->update($data);

        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        abort_unless($coupon->isGlobal(), 404);
        $coupon->delete();

        return back()->with('success', 'Coupon deleted.');
    }
}
