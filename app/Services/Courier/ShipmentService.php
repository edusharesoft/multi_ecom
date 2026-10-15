<?php

namespace App\Services\Courier;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\CourierAccount;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Messaging\OrderNotifier;
use App\Services\OrderService;
use App\Support\Tenancy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ShipmentService
{
    public const DRIVERS = [
        'steadfast' => SteadfastDriver::class,
        'pathao' => PathaoDriver::class,
        'redx' => RedxDriver::class,
        'manual' => ManualDriver::class,
    ];

    public function __construct(protected OrderService $orders, protected OrderNotifier $notifier) {}

    public function account(Branch $branch, string $courier): CourierAccount
    {
        abort_unless(isset(self::DRIVERS[$courier]), 404);
        if ($courier === 'manual') {
            return CourierAccount::firstOrCreate(['branch_id' => $branch->id, 'courier' => 'manual'], ['mode' => 'live', 'is_active' => true]);
        }
        if (! $branch->hasFeature('courier')) {
            throw new RuntimeException('Courier API আপনার প্ল্যানে নেই — Manual courier ব্যবহার করুন বা প্ল্যান আপগ্রেড করুন।');
        }
        $acc = CourierAccount::where('branch_id', $branch->id)->where('courier', $courier)->where('is_active', true)->first();
        if (! $acc) {
            throw new RuntimeException(CourierAccount::COURIERS[$courier][0].' চালু করা নেই — Settings → Courier থেকে API তথ্য দিন।');
        }

        return $acc;
    }

    public function driver(CourierAccount $acc): CourierDriver
    {
        $class = self::DRIVERS[$acc->courier];

        return new $class($acc);
    }

    public function canShip(Order $order): bool
    {
        return ! in_array($order->status, ['cancelled', 'failed', 'returned', 'refunded', 'delivered', 'completed'], true)
            && $order->source !== 'pos'
            && ! Shipment::where('order_id', $order->id)->whereIn('status_group', ['pending', 'in_transit', 'delivered'])->exists();
    }

    /** COD to collect: unpaid balance of the order. */
    public function codFor(Order $order): float
    {
        return $order->isPaid() ? 0.0 : round((float) $order->payable, 2);
    }

    public function book(Order $order, string $courier, array $opt = []): Shipment
    {
        // one booking per order at a time (double click / bulk + single) — never create two parcels / two SMS
        $lock = Cache::lock('ship-book:'.$order->id, 60);
        if (! $lock->get()) {
            throw new RuntimeException("{$order->order_number}: বুকিং চলছে, একটু পরে আবার দেখুন।");
        }
        try {
            return $this->doBook($order, $courier, $opt);
        } finally {
            $lock->release();
        }
    }

    protected function doBook(Order $order, string $courier, array $opt): Shipment
    {
        $branch = $order->branch;
        if (! $this->canShip($order)) {
            throw new RuntimeException("{$order->order_number}: এই অর্ডার কুরিয়ারে পাঠানো যাবে না (বাতিল/ডেলিভারড/আগেই বুক করা)।");
        }
        $acc = $this->account($branch, $courier);
        $opt['cod_amount'] = isset($opt['cod_amount']) && $opt['cod_amount'] !== '' ? max(0, (float) $opt['cod_amount']) : $this->codFor($order);
        $res = $this->driver($acc)->create($order, $opt);

        $shipment = DB::transaction(function () use ($order, $branch, $courier, $opt, $res) {
            $s = Shipment::create([
                'branch_id' => $branch->id, 'order_id' => $order->id, 'courier' => $courier,
                'courier_name' => $courier === 'manual' ? ($opt['courier_name'] ?? 'Manual') : null,
                'consignment_id' => $res['consignment_id'] ?? null, 'tracking_code' => $res['tracking_code'] ?? null,
                'tracking_url' => $opt['tracking_url'] ?? null,
                'status' => $res['status'] ?? 'pending', 'status_group' => CourierDriver::group($res['status'] ?? 'pending'),
                'cod_amount' => $opt['cod_amount'], 'delivery_charge' => $res['delivery_charge'] ?? null,
                'weight' => (float) ($opt['weight'] ?? 0.5), 'note' => $opt['note'] ?? null, 'payload' => $res['payload'] ?? [],
                'last_synced_at' => now(), 'actor_name' => Tenancy::branch() ? Tenancy::actorName() : 'System',
            ]);
            $order->addHistory($order->status, 'Courier: '.$s->courierLabel().' '.($s->tracking_code ?: $s->consignment_id));

            return $s;
        });

        ActivityLog::record('shipment.booked', "{$order->order_number} → {$shipment->courierLabel()} {$shipment->tracking_code}", $order);
        // moving the order to "shipped" also sends the customer the tracking SMS
        $order->setRelation('shipment', $shipment);
        if (! in_array($order->status, ['shipped', 'on_the_way'], true)) {
            $this->safeStatus($order, 'shipped', 'Handed to '.$shipment->courierLabel());
        } else {
            $this->notifier->fire($order, 'order_shipped');
        }

        return $shipment;
    }

    /** Pull the latest status from the courier and move the order forward. */
    public function sync(Shipment $s): Shipment
    {
        if (! $s->isOpen() || $s->courier === 'manual') {
            return $s;
        }
        $acc = CourierAccount::where('branch_id', $s->branch_id)->where('courier', $s->courier)->first();
        if (! $acc) {
            return $s;
        }
        $res = $this->driver($acc)->track($s);
        $group = CourierDriver::group($res['status']);
        $s->update(['status' => mb_substr($res['status'], 0, 60), 'status_group' => $group, 'last_synced_at' => now(),
            'delivered_at' => $group === 'delivered' ? ($s->delivered_at ?? now()) : $s->delivered_at]);
        $this->applyToOrder($s);

        return $s;
    }

    /** Manual status update (manual courier, or override). */
    public function setStatus(Shipment $s, string $group, ?string $note = null): void
    {
        abort_unless(isset(Shipment::GROUPS[$group]), 422);
        $s->update(['status' => $note ? mb_substr($note, 0, 60) : $group, 'status_group' => $group, 'last_synced_at' => now(),
            'delivered_at' => $group === 'delivered' ? now() : $s->delivered_at]);
        $this->applyToOrder($s);
    }

    protected function applyToOrder(Shipment $s): void
    {
        $order = $s->order()->first();
        if (! $order) {
            return;
        }
        $order->setRelation('shipment', $s);
        match ($s->status_group) {
            'in_transit' => in_array($order->status, ['new', 'pending', 'confirmed', 'processing', 'packed', 'ready_to_ship', 'shipped'], true)
                ? $this->safeStatus($order, 'on_the_way', 'Courier: '.$s->status) : null,
            'delivered' => in_array($order->status, ['delivered', 'completed', 'returned', 'refunded', 'cancelled', 'failed'], true)
                ? null : $this->safeStatus($order, 'delivered', 'Courier delivered'),
            'returned', 'cancelled' => $this->alertManager($order, $s),
            default => null,
        };
    }

    protected function safeStatus(Order $order, string $status, string $note): void
    {
        try {
            $this->orders->changeStatus($order, $status, $note);
        } catch (Throwable $e) {
            Log::info("[Shipment] status {$status} skipped for {$order->order_number}: ".$e->getMessage());
        }
    }

    protected function alertManager(Order $order, Shipment $s): void
    {
        app(\App\Services\Notifier::class)->branch($order->branch, 'shipment.'.$s->status_group,
            "কুরিয়ার {$s->status_group}: {$order->order_number}", "{$s->courierLabel()} — {$s->status}. অর্ডারটি দেখে Cancel/Return করুন।",
            route('manager.orders.show', $order), 'warning', ['app']);
    }

    /** Sync all open shipments (scheduler / button). */
    public function syncOpen(?Branch $branch = null, int $limit = 200): array
    {
        $ok = $fail = 0;
        Shipment::whereIn('status_group', ['pending', 'in_transit'])->where('courier', '!=', 'manual')
            ->when($branch, fn ($q) => $q->where('branch_id', $branch->id))
            ->orderBy('last_synced_at')->limit($limit)->get()
            ->each(function (Shipment $s) use (&$ok, &$fail) {
                try {
                    $this->sync($s);
                    $ok++;
                } catch (Throwable $e) {
                    $fail++;
                    $s->update(['last_synced_at' => now()]);
                    Log::warning("[Shipment] sync #{$s->id} failed: ".$e->getMessage());
                }
            });

        return [$ok, $fail];
    }
}
