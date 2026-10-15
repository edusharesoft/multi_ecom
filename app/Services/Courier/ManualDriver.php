<?php

namespace App\Services\Courier;

use App\Models\Order;
use App\Models\Shipment;

/** Own rider / Sundarban / SA Paribahan / any courier without API — tracking number typed by staff. */
class ManualDriver extends CourierDriver
{
    protected function createLive(Order $order, array $opt): array
    {
        return ['consignment_id' => $opt['tracking'] ?? null, 'tracking_code' => $opt['tracking'] ?? null,
            'status' => 'pending', 'delivery_charge' => isset($opt['charge']) && $opt['charge'] !== '' ? (float) $opt['charge'] : null, 'payload' => []];
    }

    public function create(Order $order, array $opt): array
    {
        return $this->createLive($order, $opt);
    }

    protected function trackLive(Shipment $s): array
    {
        return ['status' => $s->status, 'payload' => []];
    }

    public function track(Shipment $s): array
    {
        return $this->trackLive($s);
    }

    protected function testLive(): string
    {
        return 'Manual courier — API দরকার নেই।';
    }
}
