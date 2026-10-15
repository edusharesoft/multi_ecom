<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Coupon;
use App\Support\CouponForm;
use App\Support\Tenancy;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        $branch = Tenancy::branch();

        return view('manager.coupons', [
            'coupons' => $branch->coupons()->with('customer')->latest()->get(),
            'globalCoupons' => Coupon::whereNull('branch_id')->where('is_active', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->get(),
            'products' => $branch->products()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::orderBy('sort')->get(['id', 'name', 'parent_id']),
            'customers' => $branch->customers()->orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    public function store(Request $request)
    {
        $branch = Tenancy::branch();
        $data = CouponForm::validate($request, $branch->id);
        $data['customer_id'] = $this->ownCustomer($data['customer_id'] ?? null);
        $data['product_ids'] = $this->ownProducts($data['product_ids']);
        $coupon = $branch->coupons()->create($data);
        ActivityLog::record('coupon.created', "Coupon {$coupon->code} created", $coupon);

        return back()->with('success', "কুপন {$coupon->code} তৈরি হয়েছে।");
    }

    public function update(Request $request, Coupon $coupon)
    {
        $this->own($coupon);
        $data = CouponForm::validate($request, $coupon->branch_id, $coupon->id);
        $data['customer_id'] = $this->ownCustomer($data['customer_id'] ?? null);
        $data['product_ids'] = $this->ownProducts($data['product_ids']);
        $coupon->update($data);

        return back()->with('success', 'কুপন আপডেট হয়েছে।');
    }

    public function destroy(Coupon $coupon)
    {
        $this->own($coupon);
        $coupon->delete();

        return back()->with('success', 'কুপন মুছে ফেলা হয়েছে।');
    }

    protected function ownCustomer(?int $id): ?int
    {
        return $id && Tenancy::branch()->customers()->whereKey($id)->exists() ? $id : null;
    }

    protected function ownProducts(?array $ids): ?array
    {
        return $ids === null ? null : Tenancy::branch()->products()->whereIn('id', $ids)->pluck('id')->all();
    }

    protected function own(Coupon $coupon): void
    {
        abort_unless($coupon->branch_id === Tenancy::branch()->id, 404);
    }
}
