<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

/**
 * Session cart, kept separately per branch: session("cart.{branch_id}").
 * Line key: "{product_id}-{variant_id|0}" => quantity
 */
class CartService
{
    public function __construct(protected Branch $branch) {}

    protected function key(): string
    {
        return 'cart.'.$this->branch->id;
    }

    public function raw(): array
    {
        return session($this->key(), []);
    }

    protected function save(array $lines): void
    {
        session([$this->key() => $lines]);
    }

    public function add(Product $product, ?ProductVariant $variant, int $qty): void
    {
        $lines = $this->raw();
        $k = $product->id.'-'.($variant?->id ?? 0);
        $lines[$k] = min(99, ($lines[$k] ?? 0) + max(1, $qty));
        $this->save($lines);
    }

    public function update(string $key, int $qty): void
    {
        $lines = $this->raw();
        if (isset($lines[$key])) {
            if ($qty < 1) {
                unset($lines[$key]);
            } else {
                $lines[$key] = min(99, $qty);
            }
        }
        $this->save($lines);
    }

    public function remove(string $key): void
    {
        $lines = $this->raw();
        unset($lines[$key]);
        $this->save($lines);
    }

    public function clear(): void
    {
        session()->forget($this->key());
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /** Resolved lines with fresh prices from the DB; invalid lines are dropped. */
    public function items(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }

        $productIds = collect(array_keys($raw))->map(fn ($k) => (int) explode('-', $k)[0])->unique();
        $products = $this->branch->products()->active()->with('variants')->whereIn('id', $productIds)->get()->keyBy('id');

        $items = collect();
        foreach ($raw as $key => $qty) {
            [$pid, $vid] = array_map('intval', explode('-', $key) + [0, 0]);
            $product = $products->get($pid);
            if (! $product) {
                continue;
            }
            $product->setRelation('branch', $this->branch);
            $variant = $vid ? $product->variants->firstWhere('id', $vid) : null;
            $variant?->setRelation('product', $product);   // effectivePrice() falls back to the product price without a query
            if ($vid && ! $variant) {
                continue;
            }
            $price = $variant ? $variant->effectivePrice() : $product->currentPrice();
            $items->push((object) [
                'key' => $key,
                'product' => $product,
                'variant' => $variant,
                'price' => $price,
                'quantity' => $qty,
                'total' => $price * $qty,
                'stock' => $variant ? $variant->stock : $product->stock,
            ]);
        }

        return $items;
    }

    /* ---------- coupon (kept in session per branch) ---------- */

    public function couponCode(): ?string
    {
        return session('coupon.'.$this->branch->id);
    }

    public function setCoupon(?string $code): void
    {
        $code ? session(['coupon.'.$this->branch->id => strtoupper(trim($code))]) : session()->forget('coupon.'.$this->branch->id);
    }

    /**
     * Full price breakdown.
     * subtotal − coupon discount + shipping + tax (product tax % or store tax %, after discount) − wallet = payable
     */
    public function totals(?Collection $items = null, ?\App\Models\Customer $customer = null, ?string $phone = null, float $walletRequested = 0): array
    {
        $items ??= $this->items();
        $subtotal = (float) $items->sum('total');

        $discount = 0.0;
        $coupon = null;
        $couponError = null;
        if ($code = $this->couponCode()) {
            $r = app(CouponService::class)->evaluate($this->branch, $code, $items, $customer, $phone);
            [$coupon, $discount, $couponError] = [$r['coupon'], $r['discount'], $r['error']];
        }

        // Tax per line (product tax % overrides store tax %), on the discounted value
        $ratio = $subtotal > 0 ? ($subtotal - $discount) / $subtotal : 0;
        $tax = round($items->sum(function ($i) use ($ratio) {
            $rate = (float) $i->product->tax_percent > 0 ? (float) $i->product->tax_percent : (float) $this->branch->tax_percent;

            return $i->total * $ratio * $rate / 100;
        }), 2);

        $needsShipping = $items->contains(fn ($i) => ($i->product->product_type ?? 'physical') === 'physical');
        $shipping = $subtotal > 0 && $needsShipping ? (float) $this->branch->shipping_charge : 0.0;
        $total = round($subtotal - $discount + $shipping + $tax, 2);

        $wallet = 0.0;
        if ($customer && $walletRequested > 0) {
            $wallet = round(min($walletRequested, (float) $customer->wallet_balance, $total), 2);
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'coupon' => $coupon,
            'coupon_error' => $couponError,
            'shipping' => $shipping,
            'tax' => $tax,
            'total' => $total,
            'wallet' => $wallet,
            'payable' => round($total - $wallet, 2),
        ];
    }
}
