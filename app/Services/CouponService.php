<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * Validates a coupon against a cart and calculates the discount.
 * Supports: % / fixed, product- or category-specific, minimum order, first order, customer-specific,
 * total usage limit, per-customer limit, start/expiry date. Branch coupons need the plan's "coupon" feature;
 * global (Super Admin) coupons work in every store.
 */
class CouponService
{
    /**
     * @param  Collection  $items  CartService::items()
     * @return array{coupon: ?Coupon, discount: float, error: ?string}
     */
    public function evaluate(Branch $branch, string $code, Collection $items, ?Customer $customer = null, ?string $phone = null): array
    {
        $fail = fn (string $msg) => ['coupon' => null, 'discount' => 0.0, 'error' => $msg];
        $code = strtoupper(trim($code));

        $coupon = Coupon::where('code', $code)
            ->where(fn ($q) => $q->where('branch_id', $branch->id)->orWhereNull('branch_id'))
            ->orderByRaw('branch_id is null')   // branch coupon wins over global with same code
            ->first();

        if (! $coupon || ! $coupon->is_active) {
            return $fail('কুপন কোডটি সঠিক নয়।');
        }
        if (! $coupon->isGlobal() && ! $branch->hasFeature('coupon')) {
            return $fail('এই স্টোরে বর্তমানে কুপন সুবিধা নেই।');
        }
        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return $fail('এই কুপন এখনো চালু হয়নি।');
        }
        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return $fail('এই কুপনের মেয়াদ শেষ।');
        }
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            return $fail('এই কুপনের ব্যবহারসীমা শেষ।');
        }
        if ($coupon->customer_id && $coupon->customer_id !== $customer?->id) {
            return $fail('এই কুপনটি আপনার অ্যাকাউন্টের জন্য নয় — লগইন করুন।');
        }

        $phone = $phone ?: $customer?->phone;
        if ($coupon->per_customer_limit !== null && ($customer || $phone)) {
            $used = $coupon->usages()->where(fn ($q) => $q->when($customer, fn ($w) => $w->orWhere('customer_id', $customer->id))->when($phone, fn ($w) => $w->orWhere('phone', $phone)))->count();
            if ($used >= $coupon->per_customer_limit) {
                return $fail('আপনি এই কুপনটি সর্বোচ্চ সংখ্যক বার ব্যবহার করেছেন।');
            }
        }
        if ($coupon->first_order_only) {
            if (! $customer && ! $phone) {
                return $fail('প্রথম অর্ডারের কুপন — লগইন করুন অথবা চেকআউটে মোবাইল নম্বর দিন।');
            }
            $hasOrder = Order::where('branch_id', $branch->id)->where('status', '!=', 'cancelled')
                ->where(fn ($q) => $q->when($customer, fn ($w) => $w->orWhere('customer_id', $customer->id))->when($phone, fn ($w) => $w->orWhere('customer_phone', $phone)))
                ->exists();
            if ($hasOrder) {
                return $fail('এই কুপন শুধু প্রথম অর্ডারের জন্য।');
            }
        }

        // Eligible part of the cart
        $eligible = $items->filter(function ($item) use ($coupon) {
            return match ($coupon->scope) {
                'products' => in_array($item->product->id, array_map('intval', $coupon->product_ids ?? []), true),
                'categories' => in_array((int) $item->product->category_id, array_map('intval', $coupon->category_ids ?? []), true)
                    || in_array((int) $item->product->subcategory_id, array_map('intval', $coupon->category_ids ?? []), true),
                default => true,
            };
        });
        $eligibleTotal = (float) $eligible->sum('total');
        $cartTotal = (float) $items->sum('total');

        if ($eligibleTotal <= 0) {
            return $fail('কার্টের কোনো পণ্যে এই কুপন প্রযোজ্য নয়।');
        }
        if ($cartTotal < (float) $coupon->min_order) {
            return $fail('কমপক্ষে ৳'.number_format((float) $coupon->min_order).' এর অর্ডারে এই কুপন প্রযোজ্য।');
        }

        $discount = $coupon->type === 'percent'
            ? $eligibleTotal * (float) $coupon->value / 100
            : (float) $coupon->value;
        if ($coupon->max_discount !== null) {
            $discount = min($discount, (float) $coupon->max_discount);
        }
        $discount = round(min($discount, $eligibleTotal), 2);

        return ['coupon' => $coupon, 'discount' => $discount, 'error' => null];
    }
}
