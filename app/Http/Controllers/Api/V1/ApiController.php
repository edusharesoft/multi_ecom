<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Support\Tenancy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Shared JSON shapes for API v1. */
abstract class ApiController extends Controller
{
    protected function branch()
    {
        return Tenancy::branch();
    }

    protected function paginated(LengthAwarePaginator $p, callable $map): array
    {
        return [
            'data' => collect($p->items())->map($map)->values(),
            'meta' => ['current_page' => $p->currentPage(), 'per_page' => $p->perPage(), 'total' => $p->total(), 'last_page' => $p->lastPage()],
        ];
    }

    protected function perPage(): int
    {
        return max(1, min(100, (int) request('per_page', 25)));
    }

    public static function product(Product $p): array
    {
        return [
            'id' => $p->id, 'name' => $p->name, 'slug' => $p->slug, 'sku' => $p->sku, 'barcode' => $p->barcode,
            'type' => $p->product_type, 'unit' => $p->unit,
            'price' => (float) $p->price, 'current_price' => (float) $p->currentPrice(), 'purchase_price' => $p->purchase_price !== null ? (float) $p->purchase_price : null,
            'stock' => (int) $p->stock, 'min_stock' => (int) $p->min_stock, 'is_active' => (bool) $p->is_active,
            'category_id' => $p->category_id, 'brand_id' => $p->brand_id,
            'image' => $p->image_url, 'url' => $p->relationLoaded('branch') ? $p->url : null,
            'variants' => $p->relationLoaded('variants') ? $p->variants->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'sku' => $v->sku ?? null, 'price' => (float) $v->effectivePrice(), 'stock' => (int) $v->stock])->values() : null,
            'updated_at' => $p->updated_at?->toIso8601String(),
        ];
    }

    public static function order(Order $o, bool $full = false): array
    {
        $out = [
            'id' => $o->id, 'order_number' => $o->order_number, 'status' => $o->status, 'source' => $o->source,
            'payment_method' => $o->payment_method, 'payment_status' => $o->payment_status,
            'customer' => ['id' => $o->customer_id, 'name' => $o->customer_name, 'phone' => $o->customer_phone, 'email' => $o->customer_email],
            'shipping_address' => $o->shipping_address,
            'subtotal' => (float) $o->subtotal, 'discount' => (float) $o->discount, 'shipping' => (float) $o->shipping, 'tax' => (float) $o->tax,
            'total' => (float) $o->total, 'refunded' => (float) $o->refunded_amount, 'payable' => (float) $o->payable,
            'created_at' => $o->created_at?->toIso8601String(),
        ];
        if ($full) {
            $out['items'] = $o->items->map(fn ($i) => ['product_id' => $i->product_id, 'variant_id' => $i->product_variant_id, 'name' => $i->product_name, 'variant' => $i->variant_name, 'price' => (float) $i->price, 'quantity' => (int) $i->quantity, 'total' => (float) $i->total])->values();
            $out['note'] = $o->note;
            $s = $o->shipment;
            $out['shipment'] = $s ? ['courier' => $s->courierLabel(), 'tracking' => $s->tracking_code ?: $s->consignment_id, 'status' => $s->status_group, 'tracking_url' => $s->publicTrackingUrl()] : null;
        }

        return $out;
    }

    public static function customer(Customer $c): array
    {
        return ['id' => $c->id, 'name' => $c->name, 'phone' => $c->phone, 'email' => $c->email, 'group' => $c->group,
            'wallet_balance' => (float) ($c->wallet_balance ?? 0), 'loyalty_points' => (int) ($c->loyalty_points ?? 0), 'created_at' => $c->created_at?->toIso8601String()];
    }
}
