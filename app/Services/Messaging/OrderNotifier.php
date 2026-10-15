<?php

namespace App\Services\Messaging;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Services\Notifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Customer + manager notifications for order events. Each event × channel can be switched on/off
 * and its text edited by the branch (Settings → Notifications). Never throws.
 */
class OrderNotifier
{
    /** event => [label, default sms, default email, default text] */
    public const EVENTS = [
        'order_placed' => ['অর্ডার গ্রহণ (কাস্টমার)', true, true, 'প্রিয় {name}, আপনার অর্ডার {order} (৳{total}) গ্রহণ করা হয়েছে। ট্র্যাক: {track_url} - {store}'],
        'order_confirmed' => ['অর্ডার কনফার্ম', false, false, '{name}, আপনার অর্ডার {order} কনফার্ম হয়েছে, শীঘ্রই পাঠানো হবে। - {store}'],
        'order_shipped' => ['কুরিয়ারে পাঠানো হয়েছে', true, true, 'আপনার অর্ডার {order} {courier}-এ পাঠানো হয়েছে। ট্র্যাকিং: {tracking} {tracking_url} - {store}'],
        'order_delivered' => ['ডেলিভারি সম্পন্ন', false, false, '{name}, আপনার অর্ডার {order} ডেলিভারি হয়েছে। ধন্যবাদ! রিভিউ দিন: {track_url} - {store}'],
        'order_cancelled' => ['অর্ডার বাতিল', true, true, 'আপনার অর্ডার {order} বাতিল করা হয়েছে। প্রয়োজনে কল করুন {phone} - {store}'],
        'payment_received' => ['পেমেন্ট পাওয়া গেছে', false, true, '{order} এর ৳{amount} পেমেন্ট পাওয়া গেছে। ধন্যবাদ - {store}'],
        'pos_sale' => ['POS বিক্রির পর ধন্যবাদ SMS', false, false, '{store}: কেনাকাটার জন্য ধন্যবাদ! ইনভয়েস {order}, মোট ৳{total}। পয়েন্ট: {points}'],
        'manager_new_order' => ['নতুন অর্ডার (ম্যানেজারকে)', false, true, 'নতুন অর্ডার {order}: {name} {phone}, ৳{total}'],
    ];

    public const PLACEHOLDERS = ['{name}', '{order}', '{total}', '{amount}', '{store}', '{phone}', '{track_url}', '{courier}', '{tracking}', '{tracking_url}', '{points}', '{status}'];

    public function __construct(protected MessageService $messages, protected Notifier $notifier) {}

    public static function config(Branch $branch): array
    {
        $saved = $branch->option('notify', []) ?: [];
        $out = [];
        foreach (self::EVENTS as $key => [$label, $sms, $email, $text]) {
            $s = $saved[$key] ?? [];
            $out[$key] = [
                'label' => $label,
                'sms' => (bool) ($s['sms'] ?? $sms),
                'email' => (bool) ($s['email'] ?? $email),
                'text' => (string) (($s['text'] ?? '') !== '' ? $s['text'] : $text),
            ];
        }

        return $out;
    }

    public function vars(Order $order, array $extra = []): array
    {
        $b = $order->branch;
        $shipment = $order->relationLoaded('shipment') ? $order->shipment : $order->shipment()->first();

        return $extra + [
            'name' => $order->customer_name, 'order' => $order->order_number, 'total' => number_format((float) $order->total, 0),
            'amount' => number_format((float) $order->total, 0), 'store' => $b->store_name ?: $b->name, 'phone' => $b->store_phone ?: $b->mobile,
            'track_url' => route('store.track', $b).'?order_number='.$order->order_number,
            'courier' => $shipment?->courierLabel() ?? '', 'tracking' => $shipment?->tracking_code ?: $shipment?->consignment_id ?: '',
            'tracking_url' => $shipment?->publicTrackingUrl() ?? '', 'points' => (int) $order->points_earned,
            'status' => Order::STATUS_META[$order->status][0] ?? $order->status,
        ];
    }

    public function fire(Order $order, string $event, array $extra = []): void
    {
        if ($event === 'payment_received') {
            $this->webhook($order, 'order.paid');
        }
        if (! MessageService::ready() || ! isset(self::EVENTS[$event])) {
            return;
        }
        try {
            $order->loadMissing('branch');
            $b = $order->branch;
            $cfg = self::config($b)[$event];
            $vars = $this->vars($order, $extra);
            $text = MessageService::render($cfg['text'], $vars);
            $meta = ['order_id' => $order->id];

            if ($event === 'manager_new_order') {
                if ($cfg['sms'] && $b->mobile) {
                    $this->messages->sms($b, $b->mobile, $text, $event, $meta);
                }
                if ($cfg['email'] && $b->email) {
                    $this->messages->email($b, $b->email, "নতুন অর্ডার {$order->order_number}", $text."\n\n".route('manager.orders.show', $order), $event, $meta);
                }

                return;
            }
            if ($cfg['sms'] && $order->customer_phone) {
                $this->messages->sms($b, $order->customer_phone, $text, $event, $meta);
            }
            if ($cfg['email'] && $order->customer_email) {
                $this->messages->email($b, $order->customer_email, $cfg['label'].' — '.$order->order_number, $text, $event, $meta);
            }
        } catch (Throwable $e) {
            Log::warning("[OrderNotifier] {$event} failed: ".$e->getMessage());
        }
    }

    /** Status change → matching customer event. */
    public function statusChanged(Order $order, string $status): void
    {
        $this->webhook($order, 'order.status_changed');
        $event = match ($status) {
            'confirmed' => 'order_confirmed',
            'shipped' => 'order_shipped',
            'delivered' => 'order_delivered',
            'cancelled' => 'order_cancelled',
            default => null,
        };
        if ($event) {
            $this->fire($order, $event);
        }
    }

    public function placed(Order $order): void
    {
        $this->webhook($order, 'order.created');
        $this->fire($order, $order->source === 'pos' ? 'pos_sale' : 'order_placed');
        if ($order->source !== 'pos') {
            $this->fire($order, 'manager_new_order');
        }
        $this->lowStock($order);
    }

    /** One in-app alert per product per day when stock falls to / below its minimum. */
    public function lowStock(Order $order): void
    {
        try {
            $ids = $order->items()->pluck('product_id')->filter()->unique();
            foreach (Product::whereIn('id', $ids)->where('product_type', 'physical')->whereColumn('stock', '<=', 'min_stock')->get(['id', 'name', 'stock', 'min_stock', 'branch_id']) as $p) {
                if (Cache::add("lowstock:{$p->id}:".now()->toDateString(), 1, now()->endOfDay())) {
                    $this->notifier->branch($order->branch, 'stock.low', "স্টক কম: {$p->name}", "বর্তমান স্টক {$p->stock} (সর্বনিম্ন {$p->min_stock})",
                        route('manager.products.edit', $p->id), 'warning', ['app']);
                }
            }
        } catch (Throwable $e) {
            Log::warning('[OrderNotifier] low stock check failed: '.$e->getMessage());
        }
    }

    protected function webhook(Order $order, string $event): void
    {
        try {
            app(\App\Services\WebhookService::class)->orderEvent($order, $event);
        } catch (Throwable $e) {
            Log::warning("[Webhook] {$event} failed: ".$e->getMessage());
        }
    }
}
